<?php

namespace Wexample\SymfonyShopDs\Service\Tunnel\Checkout;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPaymentDs\Service\PaymentCheckoutService;
use Wexample\SymfonyTunnels\Class\TunnelCursor;
use Wexample\SymfonyTunnels\Service\TunnelRoutingService;

/**
 * Where the order ends, and where the payer comes back to from the provider:
 * sent there first, once, when the provider has a page of its own; then the
 * payment as payment-ds tells it — its provider's form to confirm, the wait,
 * how to pay by hand, the outcome —, and, failed or given up, the way back to
 * choosing how to pay.
 */
class ConfirmationStep extends AbstractCheckoutStep
{
    public const string STEP_NAME = 'confirmation';

    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly PaymentCheckoutService $checkout,
        private readonly TunnelRoutingService $routing,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function needsRedirect(TunnelCursor $cursor): null|RedirectResponse|TunnelCursor
    {
        if ($redirect = parent::needsRedirect($cursor)) {
            return $redirect;
        }

        $providerUrl = $cursor->previous?->getVariableValue(PaymentStep::VARIABLE_PROVIDER_URL);

        if ($providerUrl) {
            $cursor->previous->removeVariable(PaymentStep::VARIABLE_PROVIDER_URL);

            return new RedirectResponse($providerUrl);
        }

        return null;
    }

    public function buildViewParams(TunnelCursor $cursor): array
    {
        $id = $cursor->previous?->getVariableValue(PaymentStep::VARIABLE_PAYMENT);
        $payment = $id ? $this->payments->find($id) : null;

        if (! $payment) {
            return ['payment' => null];
        }

        $request = $this->requestStack->getCurrentRequest();

        return $this->checkout->describeOutcome(
            $payment,
            $this->routing->buildCursorUrl($cursor, referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            (bool) $request?->query->has(PaymentCheckoutService::PARAMETER_CONFIRMED),
        ) + [
            'retry_url' => $cursor->previous ? $this->routing->buildCursorUrl($cursor->previous) : null,
        ];
    }
}
