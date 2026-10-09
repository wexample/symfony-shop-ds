<?php

namespace Wexample\SymfonyCartDs\Service\Tunnel\Checkout;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyCartDs\Service\CurrentCartService;
use Wexample\SymfonyCartDs\Service\FormProcessor\CheckoutPaymentFormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyTunnels\Class\TunnelCursor;
use Wexample\SymfonyTunnels\Enum\TunnelStepPreviousLoadingStrategy;
use Wexample\SymfonyTunnels\Service\Step\AbstractFormTunnelStep;
use Wexample\SymfonyTunnels\Service\TunnelRoutingService;

/**
 * How to pay, and the cart checked out by it: the payment sent, its id kept
 * for the confirmation, and the provider's page — when there is one — kept
 * for the confirmation to send the payer to once. A payment failed or given
 * up brings the payer back here to choose again.
 */
class PaymentStep extends AbstractFormTunnelStep
{
    public const string STEP_NAME = 'payment';

    public const string VARIABLE_PAYMENT = 'payment';

    public const string VARIABLE_PROVIDER_URL = 'provider-url';

    /** As long as the stock is held for the payer. */
    public const string SESSION_EXPIRATION = '30 minutes';

    public function __construct(
        private readonly CheckoutPaymentFormProcessor $processor,
        private readonly CurrentCartService $currentCart,
        private readonly TunnelRoutingService $routing,
        private readonly ConfirmationStep $confirmationStep,
    ) {
    }

    public static function getName(): string
    {
        return self::STEP_NAME;
    }

    public function getFormProcessor(TunnelCursor $cursor): AbstractFormProcessor
    {
        $confirmation = $cursor->manager->selectNextCursor($cursor) ?? $cursor->findFirstNext();

        return $this->processor->forCheckout(
            $this->currentCart->getOrCreate(),
            $this->routing->buildCursorUrl($confirmation, referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
        );
    }

    public function buildSubmitLabel(TunnelCursor $cursor): string
    {
        return 'WexampleSymfonyCartDsBundle.common.cart::button.pay';
    }

    public function getSessionExpiration(TunnelCursor $cursor): string
    {
        return self::SESSION_EXPIRATION;
    }

    public function onFormValid(FormInterface $form, TunnelCursor $cursor): ?TunnelCursor
    {
        $cursor->setVariableValue(self::VARIABLE_PAYMENT, (string) $this->processor->getPayment()?->getId());
        $cursor->setVariableValue(self::VARIABLE_PROVIDER_URL, $this->processor->getProviderUrl());

        return parent::onFormValid($form, $cursor);
    }

    /**
     * The payment survives the payer looking back at the earlier steps.
     */
    public function previousStepLoadingStrategy(): TunnelStepPreviousLoadingStrategy
    {
        return TunnelStepPreviousLoadingStrategy::KEEP;
    }

    public function getAllowedNextSteps(TunnelCursor $cursor): array
    {
        return [$this->confirmationStep];
    }
}
