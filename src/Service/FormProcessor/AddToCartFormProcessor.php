<?php

namespace Wexample\SymfonyShopDs\Service\FormProcessor;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyForms\Controller\FormController;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyHelpers\Helper\RoleHelper;
use Wexample\SymfonyMoney\Helper\MoneyHelper;
use Wexample\SymfonyShop\Entity\Product;
use Wexample\SymfonyShop\Repository\ProductRepository;
use Wexample\SymfonyShop\Service\CartService;
use Wexample\SymfonyShopDs\Form\AddToCartForm;
use Wexample\SymfonyShopDs\Service\CurrentCartService;
use Wexample\SymfonyShopDs\Service\ShopService;

/**
 * A product put in the visitor's cart, as many as are left: more than that
 * comes back as an error on the form. Sent without leaving the page, by
 * anyone — a visitor has a cart too —, to the product's own address: the
 * page then says it was added, and the cart's count follows.
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
        private readonly ProductRepository $products,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    /**
     * The product of the submission handled, not of the next one.
     */
    public function reset(): void
    {
        parent::reset();
        $this->product = null;
    }

    public function forProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getRequiredRoles(): array
    {
        return [RoleHelper::PUBLIC_ACCESS];
    }

    public function getFormActionRoute(): string
    {
        return 'form_'.FormController::ROUTE_ENTITY_FORM_PROCESSOR_SUBMIT;
    }

    public function getFormActionArgs($data): array
    {
        return parent::getFormActionArgs($data) + ['id' => (string) $this->product?->getId()];
    }

    /**
     * Sent to the form endpoint, the product is the one of its address.
     */
    public function handleSubmittedRequest(Request $request)
    {
        $id = $request->attributes->get('id');
        $this->product ??= $id ? $this->products->find($id) : null;

        return null;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        // Sent without a product, or with one that is gone: nothing to add.
        if (null === $this->product) {
            throw new NotFoundHttpException('No product to add to the cart.');
        }

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
        // The cart opens beside the page: the visitor goes on shopping.
        $this->setNotification('@form::added', action: [
            'icon' => 'ph:bold/shopping-cart',
            'label' => '@form::open_cart',
            'href' => $this->urlGenerator->generate('cart_index'),
            'target' => 'panel',
        ]);
    }
}
