<?php

namespace Wexample\SymfonyShopDs\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\TwigFunction;
use Wexample\SymfonyShop\Entity\Product;
use Wexample\SymfonyShopDs\Controller\CartStateController;
use Wexample\SymfonyShopDs\Service\CartStateService;
use Wexample\SymfonyShopDs\Service\CurrentCartService;
use Wexample\SymfonyShopDs\Service\FormProcessor\AddToCartFormProcessor;
use Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension;
use Wexample\SymfonyLoader\Service\VueService;
use Wexample\SymfonyLoader\Twig\ComponentsExtension;

/**
 * The visitor's cart where a layout or a page shows it: a count, a button
 * opening it, a summary of its lines, a product put in it. All of them follow
 * the cart as it changes on the page.
 */
class CartExtension extends AbstractTemplateExtension
{
    public function __construct(
        ComponentsExtension $componentsExtension,
        private readonly CurrentCartService $currentCart,
        private readonly CartStateService $state,
        private readonly VueService $vueService,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly AddToCartFormProcessor $addToCart,
    ) {
        parent::__construct($componentsExtension);
    }

    public function getFunctions(): array
    {
        return [
            // How many things the visitor's cart holds.
            new TwigFunction('cart_item_count', $this->currentCart->countItems(...)),
            new TwigFunction(
                'cart_button',
                function (
                    Environment $twig,
                    $context,
                    array $options = []
                ): string {
                    return $this->renderComponent(
                        $twig,
                        $context,
                        '@WexampleSymfonyShopDsBundle/components/cart-button',
                        [
                            'count' => $this->currentCart->countItems(),
                            'href' => $this->urlGenerator->generate('cart_index'),
                            'label' => $this->translator->trans('WexampleSymfonyShopDsBundle.common.cart::title'),
                            // Where it stands: `header--menu-item header--menu-item--icon`
                            // in a header.
                            'class' => $options['class'] ?? null,
                        ]
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
            new TwigFunction(
                'cart_add',
                function (
                    Environment $twig,
                    $context,
                    Product $product,
                ): string {
                    return $this->renderComponent(
                        $twig,
                        $context,
                        '@WexampleSymfonyShopDsBundle/components/cart-add',
                        [
                            'form' => $this->addToCart->forProduct($product)->createForm()->createView(),
                            'render_pass' => $context['render_pass'],
                        ]
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
            new TwigFunction(
                'cart_summary',
                function (
                    Environment $twig,
                    $context,
                    array $options = []
                ): string {
                    $editable = $options['editable'] ?? false;

                    return $this->vueService->vueRender(
                        $twig,
                        $context['render_pass'],
                        '@WexampleSymfonyShopDsBundle/components/cart-summary',
                        [
                            // Another cart than the visitor's own — an order —,
                            // shown as it is and not followed.
                            'state' => $options['state'] ?? $this->state->describeCurrent(),
                            'live' => ! isset($options['state']),
                            // Lines changed or taken out, where the cart is the
                            // page's subject; read only elsewhere.
                            'editable' => $editable,
                            'csrfToken' => $editable ? $this->csrf->getToken(CartStateController::CSRF_TOKEN_ID)->getValue() : null,
                            // Its own title, where nothing above says it is the cart.
                            'title' => $options['title'] ?? false,
                            // Narrow: the quantity under the product.
                            'compact' => $options['compact'] ?? false,
                        ]
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }
}
