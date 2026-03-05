<?php
/**
 * UCP Checkout Session Completer — validates, places order, marks session completed
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Checkout;

use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Api\Data\OrderConfirmationInterface;
use Aeqet\Ucp\Model\Config\Config;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;

class CheckoutSessionCompleter
{
    /**
     * @param OrderPlacer $orderPlacer
     * @param CartRepositoryInterface $cartRepository
     * @param CheckoutSessionRepository $sessionRepository
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly OrderPlacer $orderPlacer,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CheckoutSessionRepository $sessionRepository,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Validate the quote, place the order, and mark the session completed.
     *
     * @param CheckoutSessionInterface $session
     * @param CartInterface $quote
     * @param string $maskedId
     * @param string $sessionId
     * @return CheckoutSessionInterface
     * @throws LocalizedException
     */
    public function complete(
        CheckoutSessionInterface $session,
        CartInterface $quote,
        string $maskedId,
        string $sessionId
    ): CheckoutSessionInterface {
        if ($session->getStatus() !== CheckoutSessionInterface::STATUS_READY_FOR_COMPLETE) {
            $this->assertQuoteReadyForComplete($quote); // always throws
        }
        $quote->getPayment()->setMethod($this->config->getDefaultPaymentMethod());
        $this->cartRepository->save($quote);
        try {
            $confirmation = $this->orderPlacer->place((int) $quote->getId());
            return $this->markSessionCompleted($session, $confirmation, $maskedId);
        } catch (Exception $e) {
            $this->logger->error('Failed to complete checkout', [
                'session_id' => $sessionId,
                'quote_id' => $quote->getId(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new LocalizedException(__('Failed to complete checkout. Please try again.'));
        }
    }

    /**
     * Throw a descriptive exception listing what is missing on the quote.
     *
     * @param CartInterface $quote
     * @return never
     * @throws LocalizedException
     */
    private function assertQuoteReadyForComplete(CartInterface $quote): never
    {
        $missing = [];
        if (empty($quote->getCustomerEmail())) {
            $missing[] = 'email';
        }
        if (!$quote->getBillingAddress()?->getStreetLine(1)) {
            $missing[] = 'billing address';
        }
        if (!$quote->isVirtual()) {
            $shipping = $quote->getShippingAddress();
            if (!$shipping?->getStreetLine(1)) {
                $missing[] = 'shipping address';
            }
            if (!$shipping?->getShippingMethod()) {
                $missing[] = 'shipping method';
            }
        }
        throw new LocalizedException(
            __('The checkout session is not ready to be completed. Missing: %1.', implode(', ', $missing))
        );
    }

    /**
     * Update session status to completed and persist.
     *
     * @param CheckoutSessionInterface $session
     * @param OrderConfirmationInterface $confirmation
     * @param string $maskedId
     * @return CheckoutSessionInterface
     */
    private function markSessionCompleted(
        CheckoutSessionInterface $session,
        OrderConfirmationInterface $confirmation,
        string $maskedId
    ): CheckoutSessionInterface {
        $session->setStatus(CheckoutSessionInterface::STATUS_COMPLETED);
        $session->setOrder($confirmation);
        $this->sessionRepository->save($session, $maskedId);
        return $session;
    }
}
