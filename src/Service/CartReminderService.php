<?php

namespace Wexample\SymfonyShopDs\Service;

use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyMail\Service\MailSenderService;
use Wexample\SymfonyMoney\Service\MoneyFormatter;
use Wexample\SymfonyShop\Entity\Cart;
use Wexample\SymfonyShop\Enum\CartStatus;
use Wexample\SymfonyShop\Repository\CartRepository;

/**
 * Carts left on the way: filled, an address to write to — the buyer's
 * account, or the email given in the tunnel —, untouched for a while
 * (`reminder.delay`). Their buyer is written to once, with a link bringing the
 * cart back to whatever browser opens it, as long as the link lasts
 * (`reminder.within`). Run by the scheduler; a cart changed since is not
 * written about.
 */
class CartReminderService
{
    public const string FREQUENCY = '15 minutes';

    public const string METADATA_REMINDED_AT = 'reminded_at';

    public function __construct(
        private readonly CartRepository $carts,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailSenderService $mailSender,
        private readonly UriSigner $uriSigner,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MoneyFormatter $money,
        #[Autowire(param: 'wexample_symfony_shop_ds.reminder')]
        private readonly array $config,
    ) {
    }

    /**
     * @return int how many buyers were written to
     */
    public function remind(?DateTimeImmutable $now = null): int
    {
        if (! $this->config['enabled']) {
            return 0;
        }

        $now ??= new DateTimeImmutable();
        $count = 0;

        foreach ($this->findForgotten($now) as $cart) {
            $this->send($cart, $now);
            ++$count;
        }

        return $count;
    }

    /**
     * The address of a cart brought back: signed, lasting as long as a
     * reminder may still be sent about it.
     */
    public function buildResumeUrl(Cart $cart, DateTimeImmutable $now): string
    {
        return $this->uriSigner->sign(
            $this->urlGenerator->generate('cart_resume', ['id' => (string) $cart->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            $now->add(new DateInterval($this->config['within']))
        );
    }

    /**
     * @return Cart[]
     */
    private function findForgotten(DateTimeImmutable $now): array
    {
        $carts = $this->carts->createQueryBuilder('c')
            ->where('c.status = :opened')
            ->andWhere('c.customerEmail IS NOT NULL')
            ->andWhere('c.dateUpdated < :quiet')
            ->andWhere('c.dateUpdated > :oldest')
            ->setParameter('opened', CartStatus::Opened->value)
            ->setParameter('quiet', $now->sub(new DateInterval($this->config['delay'])))
            ->setParameter('oldest', $now->sub(new DateInterval($this->config['within'])))
            ->getQuery()
            ->getResult();

        return array_filter(
            $carts,
            static fn (Cart $cart) => ! $cart->isEmpty() && ! isset($cart->getMetadata()[self::METADATA_REMINDED_AT])
        );
    }

    private function send(Cart $cart, DateTimeImmutable $now): void
    {
        $currency = $cart->getCurrencyCode();

        $this->mailSender->send(
            (new TemplatedEmail())
                ->to(new Address($cart->getCustomerEmail()))
                ->htmlTemplate('@WexampleSymfonyShopDsBundle/mails/cart_reminder.html.twig')
                ->context([
                    'items' => array_map(fn ($item) => [
                        'title' => $item->getTitle(),
                        'quantity' => $item->getQuantity(),
                        'price' => $this->money->format($item->calcPriceFinal(), $currency),
                    ], $cart->getItems()->toArray()),
                    'total' => $this->money->format($cart->calcPriceFinal(), $currency),
                    'url' => $this->buildResumeUrl($cart, $now),
                ])
        );

        // Not touched: the date it changed last is the buyer's.
        $cart->setMetadata($cart->getMetadata() + [self::METADATA_REMINDED_AT => $now->format(DATE_ATOM)]);
        $this->entityManager->flush();
    }
}
