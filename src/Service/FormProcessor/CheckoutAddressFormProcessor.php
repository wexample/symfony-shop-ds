<?php

namespace Wexample\SymfonyCartDs\Service\FormProcessor;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyCart\Entity\Cart;
use Wexample\SymfonyCartDs\Form\CheckoutAddressForm;
use Wexample\SymfonyCartDs\Form\Data\CheckoutAddresses;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;

/**
 * The addresses of the visitor's cart, written down on it as typed: the
 * billing one, the shipping one — the billing one again when none was
 * given —, and who to tell when no one is signed in.
 */
class CheckoutAddressFormProcessor extends AbstractFormProcessor
{
    private ?Cart $cart = null;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    public function forCart(Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        return parent::createForm($data, $options + [
            CheckoutAddressForm::OPTION_ASK_EMAIL => null === $this->cart->getOwnerReference(),
            CheckoutAddressForm::OPTION_SHIPPING => $this->needsShipping(),
        ]);
    }

    public function onValid(FormInterface $form)
    {
        /** @var CheckoutAddresses $data */
        $data = $form->getData();

        $this->cart
            ->setBillingAddress($data->billing)
            ->setShippingAddress($this->needsShipping() ? ($data->shipping->hasPostalAddress() ? $data->shipping : $data->billing) : null);

        if ($data->email) {
            $this->cart->setCustomerEmail($data->email);
        }

        $this->entityManager->flush();
    }

    public function needsShipping(): bool
    {
        foreach ($this->cart->getItems() as $item) {
            if ($item->getProduct()->hasShipping()) {
                return true;
            }
        }

        return false;
    }
}
