<?php

namespace Wexample\SymfonyCartDs\Service\Tunnel\Checkout;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyCartDs\DependencyInjection\Configuration;
use Wexample\SymfonyCartDs\Service\CurrentCartService;
use Wexample\SymfonyTunnels\Class\TunnelCursor;

/**
 * The entrypoint: the cart read once more before ordering. Nothing in it,
 * there is nothing to order: back to the cart. Then who buys, where the
 * application sells to accounts; the addresses otherwise.
 */
class CartStep extends AbstractCheckoutStep
{
    public const string STEP_NAME = 'cart';

    public function __construct(
        private readonly CurrentCartService $currentCart,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AddressStep $addressStep,
        private readonly AccountStep $accountStep,
        #[Autowire(param: 'wexample_symfony_cart_ds.checkout.account')]
        private readonly string $account,
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
        // The same road whoever is signed in, and whenever they sign in:
        // the account step lets a signed-in buyer straight through.
        return Configuration::ACCOUNT_REQUIRED === $this->account
            ? [$this->accountStep]
            : [$this->addressStep];
    }
}
