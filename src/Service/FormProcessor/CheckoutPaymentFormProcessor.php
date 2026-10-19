<?php

namespace Wexample\SymfonyShopDs\Service\FormProcessor;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPaymentDs\Form\PaymentMethodForm;
use Wexample\SymfonyPaymentDs\Service\PaymentCheckoutService;
use Wexample\SymfonyShop\Entity\Cart;
use Wexample\SymfonyShop\Exception\OutOfStockException;
use Wexample\SymfonyShop\Service\CartCheckoutService;

/**
 * The method chosen for the cart, and the cart checked out by it — frozen,
 * its stock held, its payment sent — with `$returnUrl` as the page the payer
 * comes back to. What is no longer in stock comes back as an error on the
 * form. The payment, and where the browser goes next, are read afterwards by
 * the step.
 */
class CheckoutPaymentFormProcessor extends AbstractFormProcessor
{
    private ?Cart $cart = null;

    private ?string $returnUrl = null;

    private ?string $nextUrl = null;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        private readonly CartCheckoutService $checkout,
        private readonly PaymentCheckoutService $payments,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    /**
     * The cart and the addresses of the checkout handled, not of the next one.
     */
    public function reset(): void
    {
        parent::reset();
        $this->cart = null;
        $this->nextUrl = null;
        $this->returnUrl = null;
    }

    /**
     * The form is payment-ds's, its words with it.
     */
    public static function getFormClass(): string
    {
        return PaymentMethodForm::class;
    }

    public function forCheckout(Cart $cart, string $returnUrl): static
    {
        $this->cart = $cart;
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        return parent::createForm($data, $options + [
            PaymentMethodForm::OPTION_METHODS => $this->payments->getMethods(),
        ]);
    }

    public function formIsValid(FormInterface $form): bool
    {
        if (! parent::formIsValid($form)) {
            return false;
        }

        try {
            $initiation = $this->checkout->checkout(
                $this->cart,
                (string) $form->get(PaymentMethodForm::FIELD_METHOD)->getData(),
                returnUrl: $this->returnUrl,
            );
        } catch (OutOfStockException) {
            $form->addError(new FormError('WexampleSymfonyShopDsBundle.common.cart::error.out_of_stock'));

            return false;
        }

        $this->nextUrl = $this->payments->next($initiation, $this->returnUrl);

        return true;
    }

    public function getPayment(): ?Payment
    {
        return $this->cart?->getPayment();
    }

    /**
     * The provider's page when it has one; null to stay in the tunnel.
     */
    public function getProviderUrl(): ?string
    {
        return $this->nextUrl !== $this->returnUrl ? $this->nextUrl : null;
    }
}
