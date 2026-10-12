<?php

namespace Wexample\SymfonyShopDs\Form\Data;

/**
 * What the address step asks: who to tell, where to bill, where to ship.
 */
class CheckoutAddresses
{
    public ?string $email = null;

    public CheckoutAddress $billing;

    public CheckoutAddress $shipping;

    public function __construct()
    {
        $this->billing = new CheckoutAddress();
        $this->shipping = new CheckoutAddress();
    }
}
