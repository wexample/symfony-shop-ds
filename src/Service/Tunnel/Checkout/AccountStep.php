<?php

namespace Wexample\SymfonyCartDs\Service\Tunnel\Checkout;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Wexample\SymfonyCart\Service\CartService;
use Wexample\SymfonyCartDs\Service\CurrentCartService;
use Wexample\SymfonyPayment\Service\PaymentAccessService;
use Wexample\SymfonyTunnels\Class\TunnelCursor;
use Wexample\SymfonyTunnels\Service\Step\AbstractTunnelStep;
use Wexample\SymfonyUser\Entity\AbstractUser;
use Wexample\SymfonyUser\Service\AccountDirectoryService;
use Wexample\SymfonyUser\Service\FormProcessor\Tunnel\UserMailFormProcessor;
use Wexample\SymfonyUser\Service\Tunnel\Step\AbstractUserMailStep;
use Wexample\SymfonyUser\Service\Tunnel\Step\LoginStep;

/**
 * Who buys, where the application sells to accounts only (`checkout.account:
 * account`): their email; an address with an account signs in, any other
 * gets one, not activated. The cart is then theirs, and so is the order.
 */
class AccountStep extends AbstractUserMailStep
{
    public function __construct(
        UserMailFormProcessor $formProcessor,
        LoginStep $loginStep,
        Security $security,
        UserProviderInterface $userProvider,
        EntityManagerInterface $entityManager,
        private readonly AccountDirectoryService $accounts,
        private readonly CurrentCartService $currentCart,
        private readonly CartService $carts,
        private readonly PaymentAccessService $access,
        private readonly AddressStep $addressStep,
    ) {
        parent::__construct($formProcessor, $loginStep, $security, $userProvider, $entityManager);
    }

    /**
     * Signed in, the buyer is known: their cart, then the addresses.
     */
    public function needsRedirect(TunnelCursor $cursor): null|RedirectResponse|TunnelCursor
    {
        if ($redirect = parent::needsRedirect($cursor)) {
            return $redirect;
        }

        $user = $this->security->getUser();

        if (! $user instanceof AbstractUser) {
            return null;
        }

        $this->onUserResolved($user, $cursor);
        $next = $cursor->findFirstNextByStep($this->addressStep);
        $cursor->setComplete($next);

        return $next;
    }

    protected function getStepAfter(TunnelCursor $cursor): AbstractTunnelStep
    {
        return $this->addressStep;
    }

    protected function createUser(string $email): AbstractUser
    {
        $class = $this->accounts->getUserClass();

        return new $class();
    }

    /**
     * The cart is the account's at once when it is the visitor's own — signed
     * in, or just created for them. An account they still have to sign into
     * gets it then, as any cart filled before signing in: typing an address
     * proves nothing.
     */
    protected function onUserResolved(AbstractUser $user, TunnelCursor $cursor): void
    {
        if ($user->isEnabled() && $this->security->getUser() !== $user) {
            return;
        }

        $this->carts->attachOwner(
            $this->currentCart->getOrCreate(),
            $this->access->ownerReferenceOf($user),
            $user->getEmail()
        );
    }
}
