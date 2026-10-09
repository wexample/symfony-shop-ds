<?php

namespace Wexample\SymfonyCartDs\Service\FormProcessor;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyCart\Entity\Product;
use Wexample\SymfonyCart\Service\CartService;
use Wexample\SymfonyCartDs\Form\AddToCartForm;
use Wexample\SymfonyCartDs\Service\CurrentCartService;
use Wexample\SymfonyCartDs\Service\ShopService;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyMoney\Helper\MoneyHelper;

/**
 * A product put in the visitor's cart, as many as are left: more than that
 * comes back as an error on the form. Then the cart is shown.
 */
class AddToCartFormProcessor extends AbstractFormProcessor
{
    private ?Product $product = null;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        private readonly CurrentCartService $currentCart,
        private readonly CartService $carts,
        private readonly ShopService $shop,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    public function forProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        return parent::createForm($data, $options + [
            AddToCartForm::OPTION_PRODUCT => $this->product,
            AddToCartForm::OPTION_SCHEDULES => $this->shop->getSchedules($this->product),
        ]);
    }

    public function formIsValid(FormInterface $form): bool
    {
        if (! parent::formIsValid($form)) {
            return false;
        }

        $variation = $form->has(AddToCartForm::FIELD_VARIATION) ? $form->get(AddToCartForm::FIELD_VARIATION)->getData() : null;
        $schedule = $form->has(AddToCartForm::FIELD_SCHEDULE) ? $form->get(AddToCartForm::FIELD_SCHEDULE)->getData() : null;
        $quantity = $form->has(AddToCartForm::FIELD_QUANTITY) ? (int) $form->get(AddToCartForm::FIELD_QUANTITY)->getData() : 1;
        $remaining = $this->shop->getRemaining($this->product, $schedule, $variation);

        if (null !== $remaining && $quantity > $remaining) {
            $form->addError(new FormError(0 === $remaining ? '@form::error.sold_out' : '@form::error.only_left'));

            return false;
        }

        $amount = $form->has(AddToCartForm::FIELD_AMOUNT)
            ? (int) round((float) $form->get(AddToCartForm::FIELD_AMOUNT)->getData() * 10 ** MoneyHelper::getDecimals($this->product->getCurrencyCode()))
            : null;

        $this->carts->addProduct(
            $this->currentCart->getOrCreate(),
            $this->product,
            quantity: $quantity,
            amount: $amount,
            schedule: $schedule,
            variations: array_filter([$variation]),
        );

        return true;
    }

    public function onValid(FormInterface $form)
    {
        $this->setNotification('@form::added');
        $this->redirectToRoute('cart_index');
    }
}
