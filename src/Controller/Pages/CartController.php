<?php

namespace Wexample\SymfonyCartDs\Controller\Pages;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Wexample\SymfonyCart\Repository\CartRepository;
use Wexample\SymfonyCart\Enum\CartStatus;
use Wexample\SymfonyCart\Service\CartCheckoutService;
use Wexample\SymfonyCartDs\Controller\CartStateController;
use Wexample\SymfonyCartDs\Service\CurrentCartService;
use Wexample\SymfonyCartDs\Traits\SymfonyCartDsBundleClassTrait;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;

/**
 * The visitor's cart: what it holds, how many of each, the total — its lines
 * changed through CartStateController —; the way to the purchase tunnel. A
 * cart whose payment runs is frozen: going back to editing it gives the
 * payment up.
 */
#[Route(path: '/cart/', name: 'cart_')]
final class CartController extends AbstractPagesController
{
    use SymfonyCartDsBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    final public const string ROUTE_REOPEN = 'reopen';

    final public const string ROUTE_RESUME = 'resume';

    final public const string CSRF_TOKEN_ID = CartStateController::CSRF_TOKEN_ID;

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
     * The link of a reminder: the cart brought back to whatever browser
     * opens it, while the link lasts and the cart can still be ordered.
     */
    #[Route(path: 'resume/{id}', name: self::ROUTE_RESUME, requirements: ['id' => Requirement::UUID])]
    public function resume(
        string $id,
        Request $request,
        UriSigner $uriSigner,
        CartRepository $carts,
        CurrentCartService $currentCart,
    ): RedirectResponse {
        $cart = $carts->find($id);

        if ($cart && $uriSigner->checkRequest($request)) {
            $currentCart->resume($cart);
        }

        return $this->redirectToRoute('cart_index');
    }
}
