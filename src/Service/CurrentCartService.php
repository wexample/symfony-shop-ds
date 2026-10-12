<?php

namespace Wexample\SymfonyShopDs\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\SymfonyShop\Entity\Cart;
use Wexample\SymfonyShop\Enum\CartStatus;
use Wexample\SymfonyShop\Repository\CartRepository;
use Wexample\SymfonyShop\Service\CartService;
use Wexample\SymfonyPayment\Service\PaymentAccessService;

/**
 * The visitor's cart: the one being filled or paid — `opened`, or
 * `waiting_payment` while a payment runs —, of the user signed in, known by
 * the same owner reference as their payments; of a visitor not signed in,
 * kept in their session. None until something is put in it. Filled before
 * signing in, it is the account's once signed in: taken as it is, or added
 * to the cart the account already had.
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
            $this->adoptSessionCart($owner);

            return $this->findOwned($owner);
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
     * A cart found again — the link of a reminder —, the visitor's from now
     * on, while it can still be ordered: kept in the session of a visitor not
     * signed in; given to an account signed in as a cart filled before.
     */
    public function resume(Cart $cart): void
    {
        if (CartStatus::Opened !== $cart->getStatus()) {
            return;
        }

        $this->requestStack->getSession()->set(self::SESSION_KEY, (string) $cart->getId());
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

    private function findOwned(string $owner): ?Cart
    {
        return $this->carts->findOneBy(['ownerReference' => $owner, 'status' => self::CURRENT], ['dateUpdated' => 'DESC']);
    }

    /**
     * The cart filled before signing in, given to the account: it becomes
     * the account's cart, or its lines are added to the one it had. Once.
     */
    private function adoptSessionCart(string $owner): void
    {
        $session = $this->requestStack->getSession();
        $id = $session->get(self::SESSION_KEY);

        if (! $id) {
            return;
        }

        $session->remove(self::SESSION_KEY);
        $cart = $this->carts->find($id);

        if (! $cart || ! $cart->isEditable() || $cart->isEmpty()
            || (null !== $cart->getOwnerReference() && $owner !== $cart->getOwnerReference())) {
            return;
        }

        $current = $this->findOwned($owner);
        $user = $this->security->getUser();
        $email = $user && method_exists($user, 'getEmail') ? $user->getEmail() : null;

        if (! $current || $current === $cart || ! $current->isEditable()) {
            $this->cartService->attachOwner($cart, $owner, $email);

            return;
        }

        foreach ($cart->getItems() as $item) {
            $this->cartService->addProduct(
                $current,
                $item->getProduct(),
                quantity: $item->getQuantity(),
                amount: $item->getProduct()->isFreeAmount() ? $item->getPriceBase() : null,
                schedule: $item->getSchedule(),
                variations: $item->getVariations()->toArray(),
                comment: $item->getComment(),
            );
        }

        $this->cartService->clear($cart);
    }
}
