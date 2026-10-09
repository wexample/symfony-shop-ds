<?php

namespace Wexample\SymfonyCartDs\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\SymfonyCart\Entity\Cart;
use Wexample\SymfonyCart\Enum\CartStatus;
use Wexample\SymfonyCart\Repository\CartRepository;
use Wexample\SymfonyCart\Service\CartService;
use Wexample\SymfonyPayment\Service\PaymentAccessService;

/**
 * The visitor's cart: the one being filled or paid — `opened`, or
 * `waiting_payment` while a payment runs —, of the user signed in, known by
 * the same owner reference as their payments; of a visitor not signed in,
 * kept in their session. None until something is put in it.
 */
class CurrentCartService
{
    private const string SESSION_KEY = 'cart_ds.cart';

    private const array CURRENT = [CartStatus::Opened, CartStatus::WaitingPayment];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly CartRepository $carts,
        private readonly CartService $cartService,
        private readonly PaymentAccessService $access,
    ) {
    }

    public function get(): ?Cart
    {
        $owner = $this->access->ownerReferenceOf($this->security->getUser());

        if (null !== $owner) {
            return $this->carts->findOneBy(['ownerReference' => $owner, 'status' => self::CURRENT], ['dateUpdated' => 'DESC']);
        }

        $id = $this->requestStack->getSession()->get(self::SESSION_KEY);
        $cart = $id ? $this->carts->find($id) : null;

        return $cart && in_array($cart->getStatus(), self::CURRENT, true) ? $cart : null;
    }

    /**
     * The cart to put something in: the current one, or a new one.
     */
    public function getOrCreate(): Cart
    {
        if ($cart = $this->get()) {
            return $cart;
        }

        $user = $this->security->getUser();
        $cart = $this->cartService->create($this->access->ownerReferenceOf($user));

        if ($user && method_exists($user, 'getEmail')) {
            $cart->setCustomerEmail($user->getEmail());
        } elseif (! $user) {
            $this->requestStack->getSession()->set(self::SESSION_KEY, (string) $cart->getId());
        }

        return $cart;
    }

    /**
     * How many things the cart holds, the quantities summed.
     */
    public function countItems(): int
    {
        $count = 0;

        foreach ($this->get()?->getItems() ?? [] as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }
}
