<?php
/**
 * UCP Checkout Session Service Implementation
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Checkout;

use Aeqet\Ucp\Api\CheckoutSessionServiceInterface;
use Aeqet\Ucp\Api\Data\AddressInterface;
use Aeqet\Ucp\Api\Data\BuyerInterface;
use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;

class CheckoutSessionService implements CheckoutSessionServiceInterface
{
    /**
     * @param QuoteResolver $quoteResolver
     * @param CartRepositoryInterface $cartRepository
     * @param CheckoutSessionRepository $sessionRepository
     * @param CheckoutSessionSynchronizer $sessionSynchronizer
     * @param QuoteUpdater $quoteUpdater
     * @param CheckoutSessionCompleter $sessionCompleter
     */
    public function __construct(
        private readonly QuoteResolver $quoteResolver,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CheckoutSessionRepository $sessionRepository,
        private readonly CheckoutSessionSynchronizer $sessionSynchronizer,
        private readonly QuoteUpdater $quoteUpdater,
        private readonly CheckoutSessionCompleter $sessionCompleter,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(string $cartId): CheckoutSessionInterface
    {
        if (empty($cartId)) {
            throw new NoSuchEntityException(__('Cart ID is required to create a UCP checkout session.'));
        }
        $existingSessionId = $this->sessionRepository->getSessionIdByQuoteId($cartId);
        if ($existingSessionId) {
            return $this->sessionRepository->get($existingSessionId);
        }
        [$quote, $maskedId] = $this->quoteResolver->resolveByCartId($cartId);
        return $this->sessionSynchronizer->syncFromQuote($quote, $maskedId);
    }

    /**
     * @inheritDoc
     */
    public function get(string $sessionId): CheckoutSessionInterface
    {
        if ($this->sessionRepository->exists($sessionId)) {
            return $this->sessionSynchronizer->refresh($sessionId);
        }
        return $this->sessionSynchronizer->reconstruct($sessionId);
    }

    /**
     * @inheritDoc
     */
    public function update(
        string $sessionId,
        ?BuyerInterface $buyer = null,
        ?AddressInterface $fulfillmentAddress = null,
        ?string $selectedFulfillmentId = null
    ): CheckoutSessionInterface {
        $session = $this->get($sessionId);
        if ($session->getStatus() === CheckoutSessionInterface::STATUS_COMPLETED) {
            throw new LocalizedException(__('Cannot update a completed checkout session.'));
        }
        if ($session->getStatus() === CheckoutSessionInterface::STATUS_CANCELED) {
            throw new LocalizedException(__('Cannot update a canceled checkout session.'));
        }
        $maskedId = $this->sessionRepository->getMaskedQuoteId($sessionId);
        $quote = $this->quoteResolver->resolveByMaskedId($maskedId);
        $this->quoteUpdater->apply($quote, $buyer, $fulfillmentAddress, $selectedFulfillmentId);
        $this->cartRepository->save($quote);
        return $this->sessionSynchronizer->syncFromQuote($quote, $maskedId);
    }

    /**
     * @inheritDoc
     */
    public function complete(string $sessionId): CheckoutSessionInterface
    {
        $session = $this->get($sessionId);
        if ($session->getStatus() === CheckoutSessionInterface::STATUS_COMPLETED) {
            return $session;
        }
        if ($session->getStatus() === CheckoutSessionInterface::STATUS_CANCELED) {
            throw new LocalizedException(__('Cannot complete a canceled checkout session.'));
        }
        $maskedId = $this->sessionRepository->getMaskedQuoteId($sessionId);
        $quote = $this->quoteResolver->resolveByMaskedId($maskedId);
        return $this->sessionCompleter->complete($session, $quote, $maskedId, $sessionId);
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $sessionId): CheckoutSessionInterface
    {
        $session = $this->get($sessionId);
        if ($session->getStatus() === CheckoutSessionInterface::STATUS_COMPLETED) {
            throw new LocalizedException(__('Cannot cancel a completed checkout session.'));
        }
        $maskedId = $this->sessionRepository->getMaskedQuoteId($sessionId);
        $session->setStatus(CheckoutSessionInterface::STATUS_CANCELED);
        $this->sessionRepository->save($session, $maskedId);
        return $session;
    }
}
