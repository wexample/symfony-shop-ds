<?php

namespace Wexample\SymfonyCartDs\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Wexample\SymfonyCart\Entity\CartItem;
use Wexample\SymfonyCart\Service\CartService;
use Wexample\SymfonyCartDs\Service\CartStateService;
use Wexample\SymfonyCartDs\Service\CurrentCartService;

/**
 * Machine endpoints of the visitor's cart: read, a line changed or taken out.
 * Each answers with the whole cart again, which every component showing it
 * redraws from — a count in a header, a summary beside a tunnel.
 */
#[Route(path: '/_cart', name: 'cart_state_')]
final class CartStateController
{
    final public const string CSRF_TOKEN_ID = 'cart-edit';

    public function __construct(
        private readonly CurrentCartService $currentCart,
        private readonly CartStateService $state,
        private readonly CartService $carts,
        private readonly CsrfTokenManagerInterface $csrf,
    ) {
    }

    #[Route(path: '/state', name: 'read', methods: [Request::METHOD_GET])]
    public function read(): JsonResponse
    {
        return new JsonResponse($this->state->describeCurrent());
    }

    /**
     * Within what is left of its stock and the product's limit: asked more,
     * the line takes what it may, and says so.
     */
    #[Route(path: '/item/{id}/quantity', name: 'quantity', requirements: ['id' => Requirement::UUID], methods: [Request::METHOD_POST])]
    public function quantity(
        string $id,
        Request $request,
    ): JsonResponse {
        $item = $this->findItem($id, $request);

        if (! $item) {
            return $this->refuse();
        }

        $asked = max(1, $request->request->getInt('quantity', 1));
        $max = $this->state->getMaxQuantity($item);
        $quantity = null === $max ? $asked : min($asked, max(1, $max));

        $this->carts->setQuantity($item, $quantity);

        return new JsonResponse($this->state->describeCurrent() + [
            'limited' => $quantity < $asked,
        ]);
    }

    #[Route(path: '/item/{id}/remove', name: 'remove', requirements: ['id' => Requirement::UUID], methods: [Request::METHOD_POST])]
    public function remove(
        string $id,
        Request $request,
    ): JsonResponse {
        $item = $this->findItem($id, $request);

        if (! $item) {
            return $this->refuse();
        }

        $this->carts->removeItem($item);

        return new JsonResponse($this->state->describeCurrent());
    }

    /**
     * A line of the visitor's own cart, posted with its token, while the cart
     * may still change.
     */
    private function findItem(
        string $id,
        Request $request,
    ): ?CartItem {
        $cart = $this->currentCart->get();

        if (! $cart || ! $cart->isEditable()
            || ! $this->csrf->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $request->request->getString('_token')))) {
            return null;
        }

        foreach ($cart->getItems() as $item) {
            if ((string) $item->getId() === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Nothing changed — a line gone meanwhile, a cart frozen by its payment:
     * the cart as it is, for the component to show what is true.
     */
    private function refuse(): JsonResponse
    {
        return new JsonResponse($this->state->describeCurrent(), Response::HTTP_CONFLICT);
    }
}
