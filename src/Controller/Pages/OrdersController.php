<?php

namespace Wexample\SymfonyCartDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyCart\Entity\Cart;
use Wexample\SymfonyCart\Enum\CartStatus;
use Wexample\SymfonyCart\Repository\CartRepository;
use Wexample\SymfonyCartDs\Service\CartStateService;
use Wexample\SymfonyCartDs\Traits\SymfonyCartDsBundleClassTrait;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyMoney\Service\MoneyFormatter;
use Wexample\SymfonyPayment\Service\PaymentAccessService;

/**
 * What the account ordered: every cart that went to payment — paid, being
 * paid, given up —, the latest first; one of them in full, its lines, its
 * addresses, its payment. A buyer without an account finds theirs through
 * the payment's own page, linked from the mail it received.
 */
#[Route(path: '/orders/', name: 'orders_')]
#[IsGranted('IS_AUTHENTICATED')]
final class OrdersController extends AbstractPagesController
{
    use SymfonyCartDsBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    final public const string ROUTE_SHOW = 'show';

    /**
     * The carts an order was made of: gone to payment, whatever came of it.
     */
    private const array ORDERED = [CartStatus::Paid, CartStatus::WaitingPayment, CartStatus::Canceled];

    #[Route(path: '', name: self::ROUTE_INDEX)]
    public function index(
        Request $request,
        CartRepository $carts,
        PaymentAccessService $access,
        MoneyFormatter $money,
    ): Response {
        $orders = $carts->findBy(
            ['ownerReference' => $access->ownerReferenceOf($this->getUser()), 'status' => self::ORDERED],
            ['dateUpdated' => 'DESC']
        );

        return $this->renderPage(self::ROUTE_INDEX, [
            'orders' => array_map(fn (Cart $cart) => [
                'href' => $this->generateUrl('orders_show', ['id' => (string) $cart->getId()]),
                'date' => $cart->getDatePaid() ?? $cart->getDateUpdated(),
                'items' => implode(', ', array_map(static fn ($item) => $item->getTitle(), $cart->getItems()->toArray())),
                'total' => $money->format($cart->calcPriceFinal(), $cart->getCurrencyCode(), $request->getLocale()),
                'status' => $cart->getStatus()->value,
            ], $orders),
        ]);
    }

    #[Route(path: '{id}', name: self::ROUTE_SHOW, requirements: ['id' => Requirement::UUID])]
    public function show(
        string $id,
        CartRepository $carts,
        PaymentAccessService $access,
        CartStateService $state,
    ): Response {
        $cart = $carts->find($id);

        if (! $cart || ! in_array($cart->getStatus(), self::ORDERED, true)
            || $cart->getOwnerReference() !== $access->ownerReferenceOf($this->getUser())) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage(self::ROUTE_SHOW, [
            'order' => $cart,
            'order_state' => $state->describe($cart),
        ]);
    }
}
