<?php

namespace Wexample\SymfonyCartDs\Service\Tunnel\Checkout;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyCartDs\Service\CurrentCartService;
use Wexample\SymfonyTunnels\Class\TunnelCursor;

/**
 * The entrypoint: the cart read once more before ordering. Nothing in it,
 * there is nothing to order: back to the cart.
 */
class CartStep extends AbstractCheckoutStep
{
    public const string STEP_NAME = 'cart';

    public function __construct(
        private readonly CurrentCartService $currentCart,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AddressStep $addressStep,
    ) {
    }

    public function needsRedirect(TunnelCursor $cursor): null|RedirectResponse|TunnelCursor
    {
        $cart = $this->currentCart->get();

        return ! $cart || $cart->isEmpty()
            ? new RedirectResponse($this->urlGenerator->generate('cart_index'))
            : parent::needsRedirect($cursor);
    }

    public function buildViewParams(TunnelCursor $cursor): array
    {
        return ['cart' => $this->currentCart->get()];
    }

    public function getAllowedNextSteps(TunnelCursor $cursor): array
    {
        return [$this->addressStep];
    }
}
