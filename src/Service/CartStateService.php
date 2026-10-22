<?php

namespace Wexample\SymfonyShopDs\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyMoney\Service\MoneyFormatter;
use Wexample\SymfonyShop\Entity\Cart;
use Wexample\SymfonyShop\Entity\CartItem;
use Wexample\SymfonyShop\Enum\CartStatus;
use Wexample\SymfonyShop\Repository\StockReservationRepository;
use Wexample\SymfonyShop\Service\ProductOfferService;

/**
 * The visitor's cart as the components showing it read it: its lines, what
 * each may still become, the total — the prices written for the locale of the
 * page. One shape, whether it is drawn with the page or read again after a
 * change, so every place showing the cart shows the same thing.
 */
class CartStateService
{
    public function __construct(
        private readonly CurrentCartService $currentCart,
        private readonly ProductOfferService $offers,
        private readonly MoneyFormatter $money,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly StockReservationRepository $reservations,
    ) {
    }

    public function describeCurrent(): array
    {
        return $this->describe($this->currentCart->get());
    }

    public function describe(?Cart $cart): array
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale();
        $items = $cart ? $cart->getItems()->toArray() : [];
        $currency = $cart?->getCurrencyCode();

        return [
            'count' => array_sum(array_map(static fn (CartItem $item) => $item->getQuantity(), $items)),
            // A cart whose payment runs is frozen: shown, not changed.
            'editable' => (bool) $cart?->isEditable(),
            'waiting_payment' => CartStatus::WaitingPayment === $cart?->getStatus(),
            'items' => array_map(fn (CartItem $item) => [
                'id' => (string) $item->getId(),
                'title' => $item->getTitle(),
                'detail' => $this->describeChoice($item),
                'quantity' => $item->getQuantity(),
                // A free amount is given once: no quantity to change.
                'fixed' => $item->getProduct()->isFreeAmount(),
                'max' => $this->getMaxQuantity($item),
                'price' => $this->money->format($item->calcPriceFinal(), $currency, $locale),
            ], $items),
            'total' => $this->money->format($cart?->calcPriceFinal() ?? 0, $currency, $locale),
            'vat' => $cart && $cart->calcPriceVat() ? $this->money->format($cart->calcPriceVat(), $currency, $locale) : null,
            'reserved_until' => $this->getReservedUntil($cart)?->format(DATE_ATOM),
            'urls' => [
                'cart' => $this->urlGenerator->generate('cart_index'),
                'shop' => $this->urlGenerator->generate('shop_index'),
            ],
        ];
    }

    /**
     * How many of a line the cart may hold: what is left of its stock, within
     * the product's own limit per cart.
     */
    public function getMaxQuantity(CartItem $item): ?int
    {
        $remaining = $this->offers->getRemaining(
            $item->getProduct(),
            $item->getSchedule(),
            $item->getVariations()->first() ?: null,
        );
        $limit = $item->getProduct()->getMaxPerCart();

        return match (true) {
            null === $remaining => $limit,
            null === $limit => $remaining,
            default => min($remaining, $limit),
        };
    }

    /**
     * Until when the stock of a cart being paid is held for it: the first of
     * its holds to end. None while it is being filled, or without a limited
     * stock.
     */
    public function getReservedUntil(?Cart $cart): ?\DateTimeImmutable
    {
        if (! $cart || CartStatus::WaitingPayment !== $cart->getStatus()) {
            return null;
        }

        $until = null;

        foreach ($this->reservations->findActiveForCart($cart) as $reservation) {
            if (null === $until || $reservation->getExpiresAt() < $until) {
                $until = $reservation->getExpiresAt();
            }
        }

        return $until;
    }

    /**
     * What was chosen with the product: its date, its variations.
     */
    private function describeChoice(CartItem $item): ?string
    {
        $parts = [];

        if ($schedule = $item->getSchedule()) {
            $parts[] = $schedule->getLabel() ?: $schedule->getDateStart()->format('Y-m-d H:i');
        }

        foreach ($item->getVariations() as $variation) {
            $parts[] = $variation->getName().' — '.$variation->getValue();
        }

        return $parts ? implode(' · ', $parts) : null;
    }
}
