<?php
/**
 * UCP Checkout Session Synchronizer — syncs CheckoutSession ↔ Magento Quote
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Checkout;

use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;

class CheckoutSessionSynchronizer
{
    /**
     * @param QuoteResolver $quoteResolver
     * @param QuoteToUcpConverter $quoteToUcpConverter
     * @param CheckoutSessionRepository $sessionRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly QuoteResolver $quoteResolver,
        private readonly QuoteToUcpConverter $quoteToUcpConverter,
        private readonly CheckoutSessionRepository $sessionRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Refresh an existing session from the DB, re-syncing from quote if not terminal.
     *
     * @param string $sessionId
     * @return CheckoutSessionInterface
     * @throws NoSuchEntityException
     */
    public function refresh(string $sessionId): CheckoutSessionInterface
    {
        $session = $this->sessionRepository->get($sessionId);
        $terminal = [CheckoutSessionInterface::STATUS_COMPLETED, CheckoutSessionInterface::STATUS_CANCELED];
        if (in_array($session->getStatus(), $terminal, true)) {
            return $session;
        }
        $maskedId = $this->sessionRepository->getMaskedQuoteId($sessionId);
        $quote = $this->quoteResolver->resolveByMaskedId($maskedId);
        return $this->syncFromQuote($quote, $maskedId);
    }

    /**
     * Reconstruct a session that is not in DB — build from the quote.
     *
     * @param string $sessionId
     * @return CheckoutSessionInterface
     * @throws NoSuchEntityException
     */
    public function reconstruct(string $sessionId): CheckoutSessionInterface
    {
        $maskedId = $this->extractMaskedId($sessionId);
        try {
            $quote = $this->quoteResolver->resolveByMaskedId($maskedId);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Checkout session not found', [
                'session_id' => $sessionId,
                'masked_id' => $maskedId,
                'exception' => $e->getMessage(),
            ]);
            throw new NoSuchEntityException(
                __('UCP Checkout Session with ID "%1" does not exist.', $sessionId)
            );
        }
        return $this->syncFromQuote($quote, $maskedId);
    }

    /**
     * Convert quote to UCP session and persist it.
     *
     * Used by create() and update() in CheckoutSessionService.
     *
     * @param CartInterface $quote
     * @param string $maskedId
     * @return CheckoutSessionInterface
     */
    public function syncFromQuote(CartInterface $quote, string $maskedId): CheckoutSessionInterface
    {
        $session = $this->quoteToUcpConverter->convert($quote, $maskedId);
        $this->sessionRepository->save($session, $maskedId);
        return $session;
    }

    /**
     * Extract the masked quote ID from a session ID.
     *
     * @param string $sessionId
     * @return string
     */
    private function extractMaskedId(string $sessionId): string
    {
        if (str_starts_with($sessionId, 'ucp_')) {
            return substr($sessionId, 4);
        }
        return $sessionId;
    }
}
