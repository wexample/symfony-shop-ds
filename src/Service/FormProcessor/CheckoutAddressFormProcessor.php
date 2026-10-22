<?php

namespace Wexample\SymfonyShopDs\Service\FormProcessor;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyShop\Entity\Cart;
use Wexample\SymfonyShop\Service\CartService;
use Wexample\SymfonyShopDs\Form\CheckoutAddressForm;
use Wexample\SymfonyShopDs\Form\Data\CheckoutAddresses;

/**
 * The addresses of the visitor's cart, written down on it as typed: the
 * billing one, the shipping one — the billing one again when none was
 * given —, who to tell when no one is signed in, and who buys. The VAT of
 * each line is then charged for that buyer, so the payment step shows what
 * they will pay.
 */
class CheckoutAddressFormProcessor extends AbstractFormProcessor
{
    private ?Cart $cart = null;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly CartService $cartService,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    /**
     * The cart of the checkout handled, not of the next one.
     */
    public function reset(): void
    {
        parent::reset();
        $this->cart = null;
    }

    public function forCart(Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        // Given its cart by the checkout step: sent from anywhere else, there
        // is none to address.
        if (null === $this->cart) {
            throw new NotFoundHttpException('No cart to check out.');
        }

        return parent::createForm($data, $options + [
            CheckoutAddressForm::OPTION_ASK_EMAIL => null === $this->cart->getOwnerReference(),
            CheckoutAddressForm::OPTION_SHIPPING => $this->needsShipping(),
        ]);
    }

    public function onValid(FormInterface $form)
    {
        /** @var CheckoutAddresses $data */
        $data = $form->getData();

        $this->cart
            ->setBillingAddress($data->billing)
            ->setShippingAddress($this->needsShipping() ? ($data->shipping->hasPostalAddress() ? $data->shipping : $data->billing) : null);

        if ($data->email) {
            $this->cart->setCustomerEmail($data->email);
        }

        $this->cart
            ->setCustomerName($data->billing->getFullName() ?? $this->cart->getCustomerName())
            ->setCustomerPhone($data->phone)
            ->setCustomerCompanyName($data->companyName)
            ->setCustomerVatNumber($data->vatNumber);

        $this->entityManager->flush();
        $this->cartService->applyVatRates($this->cart);
    }

    public function needsShipping(): bool
    {
        foreach ($this->cart->getItems() as $item) {
            if ($item->getProduct()->hasShipping()) {
                return true;
            }
        }

        return false;
    }
}
