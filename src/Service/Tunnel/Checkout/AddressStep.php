<?php

namespace Wexample\SymfonyShopDs\Service\Tunnel\Checkout;

use Wexample\SymfonyShopDs\Form\Data\CheckoutAddress;
use Wexample\SymfonyShopDs\Form\Data\CheckoutAddresses;
use Wexample\SymfonyShopDs\Service\CurrentCartService;
use Wexample\SymfonyShopDs\Service\FormProcessor\CheckoutAddressFormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyGeo\Repository\CountryRepository;
use Wexample\SymfonyTunnels\Class\TunnelCursor;
use Wexample\SymfonyTunnels\Service\Step\AbstractFormTunnelStep;

/**
 * Where to bill and where to ship, written down on the cart.
 */
class AddressStep extends AbstractFormTunnelStep
{
    public const string STEP_NAME = 'address';

    public function __construct(
        private readonly CheckoutAddressFormProcessor $processor,
        private readonly CurrentCartService $currentCart,
        private readonly CountryRepository $countries,
        private readonly PaymentStep $paymentStep,
    ) {
    }

    public static function getName(): string
    {
        return self::STEP_NAME;
    }

    public function getFormProcessor(TunnelCursor $cursor): AbstractFormProcessor
    {
        return $this->processor->forCart($this->currentCart->getOrCreate());
    }

    public function buildFormData(TunnelCursor $cursor): mixed
    {
        $cart = $this->currentCart->getOrCreate();
        $data = new CheckoutAddresses();
        $data->email = $cart->getCustomerEmail();
        $data->billing = CheckoutAddress::fromSnapshot($cart->getBillingAddress(), $this->countries);
        // Shipped where billed, the shipping address is left empty again.
        $data->shipping = (string) $cart->getShippingAddress() === (string) $cart->getBillingAddress()
            ? new CheckoutAddress()
            : CheckoutAddress::fromSnapshot($cart->getShippingAddress(), $this->countries);

        return $data;
    }

    public function getAllowedNextSteps(TunnelCursor $cursor): array
    {
        return [$this->paymentStep];
    }
}
