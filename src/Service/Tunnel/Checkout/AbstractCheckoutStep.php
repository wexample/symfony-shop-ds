<?php

namespace Wexample\SymfonyShopDs\Service\Tunnel\Checkout;

use Wexample\SymfonyTunnels\Service\Step\AbstractTunnelStep;

abstract class AbstractCheckoutStep extends AbstractTunnelStep
{
    public static function getName(): string
    {
        return static::STEP_NAME;
    }
}
