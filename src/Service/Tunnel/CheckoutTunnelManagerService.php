<?php

namespace Wexample\SymfonyCartDs\Service\Tunnel;

use Wexample\SymfonyCartDs\Service\Tunnel\Checkout\CartStep;
use Wexample\SymfonyTunnels\Interface\TunnelSessionStorageInterface;
use Wexample\SymfonyTunnels\Service\AbstractTunnelManagerService;
use Wexample\SymfonyTunnels\Service\Step\AbstractTunnelStep;

/**
 * Buying what the cart holds: the cart read again, the addresses, how to pay,
 * the outcome.
 */
class CheckoutTunnelManagerService extends AbstractTunnelManagerService
{
    public function __construct(
        TunnelSessionStorageInterface $sessionStorage,
        private readonly CartStep $cartStep,
    ) {
        parent::__construct($sessionStorage);
    }

    public static function getName(): string
    {
        return 'checkout';
    }

    public function getEntrypointStep(): AbstractTunnelStep
    {
        return $this->cartStep;
    }
}
