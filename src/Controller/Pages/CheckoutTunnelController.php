<?php

namespace Wexample\SymfonyCartDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyCartDs\Service\Tunnel\CheckoutTunnelManagerService;
use Wexample\SymfonyCartDs\Traits\SymfonyCartDsBundleClassTrait;
use Wexample\SymfonyTunnels\Attribute\TunnelRoute;
use Wexample\SymfonyTunnels\Controller\AbstractTunnelController;

/**
 * Mounts the purchase tunnel below the cart, cart/checkout: each step at
 * cart/checkout/<step>.
 */
#[Route(path: '/cart/checkout/', name: 'cart_checkout_')]
final class CheckoutTunnelController extends AbstractTunnelController
{
    use SymfonyCartDsBundleClassTrait;

    public static function getTunnelManagerClass(): string
    {
        return CheckoutTunnelManagerService::class;
    }

    #[TunnelRoute(cursorPlaceholder: '{step}')]
    public function index(Request $request): Response
    {
        return $this->handleTunnelRequest($request);
    }
}
