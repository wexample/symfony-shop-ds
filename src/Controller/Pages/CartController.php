<?php

namespace Wexample\SymfonyCartDs\Controller\Pages;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Wexample\SymfonyCart\Entity\CartItem;
use Wexample\SymfonyCart\Enum\CartStatus;
use Wexample\SymfonyCart\Service\CartCheckoutService;
use Wexample\SymfonyCart\Service\CartService;
use Wexample\SymfonyCartDs\Service\CurrentCartService;
use Wexample\SymfonyCartDs\Traits\SymfonyCartDsBundleClassTrait;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;

/**
 * The visitor's cart: what it holds, how many of each, the total; a line
 * changed or taken out; the way to the purchase tunnel. A cart whose payment
 * runs is frozen: going back to editing it gives the payment up.
 */
#[Route(path: '/cart/', name: 'cart_')]
final class CartController extends AbstractPagesController
{
    use SymfonyCartDsBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    final public const string ROUTE_QUANTITY = 'quantity';

    final public const string ROUTE_REMOVE = 'remove';

    final public const string ROUTE_REOPEN = 'reopen';

    final public const string CSRF_TOKEN_ID = 'cart-edit';

    #[Route(path: '', name: self::ROUTE_INDEX)]
    public function index(CurrentCartService $currentCart): Response
    {
        $cart = $currentCart->get();

        return $this->renderPage(self::ROUTE_INDEX, [
            'cart' => $cart,
            'waiting_payment' => CartStatus::WaitingPayment === $cart?->getStatus(),
            'csrf_token_id' => self::CSRF_TOKEN_ID,
        ]);
    }

    #[Route(path: 'item/{id}/quantity', name: self::ROUTE_QUANTITY, requirements: ['id' => Requirement::UUID], methods: [Request::METHOD_POST])]
    public function quantity(
        string $id,
        Request $request,
        CurrentCartService $currentCart,
        CartService $carts,
    ): RedirectResponse {
        if ($item = $this->findItem($id, $request, $currentCart)) {
            $carts->setQuantity($item, max(1, $request->request->getInt('quantity', 1)));
        }

        return $this->redirectToRoute('cart_index');
    }

    #[Route(path: 'item/{id}/remove', name: self::ROUTE_REMOVE, requirements: ['id' => Requirement::UUID], methods: [Request::METHOD_POST])]
    public function remove(
        string $id,
        Request $request,
        CurrentCartService $currentCart,
        CartService $carts,
    ): RedirectResponse {
        if ($item = $this->findItem($id, $request, $currentCart)) {
            $carts->removeItem($item);
        }

        return $this->redirectToRoute('cart_index');
    }

    #[Route(path: 'reopen', name: self::ROUTE_REOPEN, methods: [Request::METHOD_POST])]
    public function reopen(
        Request $request,
        CurrentCartService $currentCart,
        CartCheckoutService $checkout,
    ): RedirectResponse {
        $cart = $currentCart->get();

        if ($cart && CartStatus::WaitingPayment === $cart->getStatus()
            && $this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            $checkout->reopen($cart);
        }

        return $this->redirectToRoute('cart_index');
    }

    /**
     * A line of the visitor's own cart, posted with the page's token, while
     * the cart may still change.
     */
    private function findItem(
        string $id,
        Request $request,
        CurrentCartService $currentCart,
    ): ?CartItem {
        $cart = $currentCart->get();

        if (! $cart || ! $cart->isEditable()
            || ! $this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            return null;
        }

        foreach ($cart->getItems() as $item) {
            if ((string) $item->getId() === $id) {
                return $item;
            }
        }

        return null;
    }
}
