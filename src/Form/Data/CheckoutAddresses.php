<?php

namespace Wexample\SymfonyShopDs\Form\Data;

/**
 * What the address step asks: who to tell, who buys, where to bill, where to ship.
 */
class CheckoutAddresses
{
    public ?string $email = null;

    public ?string $phone = null;

    /** Left empty by a private person. */
    public ?string $companyName = null;

    /** With its country prefix: a business from another EU country pays no VAT once VIES confirms it. */
    public ?string $vatNumber = null;

    public CheckoutAddress $billing;

    public CheckoutAddress $shipping;

    public function __construct()
    {
        $this->billing = new CheckoutAddress();
        $this->shipping = new CheckoutAddress();
    }
}
