<?php

namespace Wexample\SymfonyCartDs\Form\Data;

use Wexample\SymfonyGeo\Entity\AbstractAddress;
use Wexample\SymfonyGeo\Entity\AddressSnapshot;
use Wexample\SymfonyGeo\Repository\CountryRepository;

/**
 * An address typed in the purchase tunnel: kept nowhere as such, written
 * down on the cart (AddressSnapshot) once the step is valid.
 */
class CheckoutAddress extends AbstractAddress
{
    /**
     * What the cart holds already, typed again: the step shown a second time
     * starts from it.
     */
    public static function fromSnapshot(
        ?AddressSnapshot $snapshot,
        CountryRepository $countries,
    ): self {
        $address = new self();

        if (! $snapshot) {
            return $address;
        }

        return $address
            ->setFirstName($snapshot->getFirstName())
            ->setLastName($snapshot->getLastName())
            ->setPostalAddress($snapshot->getPostalAddress())
            ->setPostCode($snapshot->getPostCode())
            ->setCity($snapshot->getCity())
            ->setCountry($snapshot->getCountryCode() ? $countries->findByIsoAlpha2Code($snapshot->getCountryCode()) : null);
    }
}
