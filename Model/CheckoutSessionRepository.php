<?php
/**
 * UCP Checkout Session Repository (In-Memory Storage for MVP)
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CheckoutSessionRepository
{
    /**
     * In-memory storage for checkout sessions
     * Maps session ID to [session, maskedQuoteId]
     *
     * @var array
     */
    private array $sessions = [];

    /**
     * Maps masked quote ID to session ID
     *
     * @var array
     */
    private array $quoteToSession = [];

    /**
     * Save a checkout session
     *
     * @param CheckoutSessionInterface $session
     * @param string $maskedQuoteId
     * @return CheckoutSessionInterface
     */
    public function save(CheckoutSessionInterface $session, string $maskedQuoteId): CheckoutSessionInterface
    {
        $sessionId = $session->getId();
        $this->sessions[$sessionId] = [
            'session' => $session,
            'maskedQuoteId' => $maskedQuoteId
        ];
        $this->quoteToSession[$maskedQuoteId] = $sessionId;

        return $session;
    }

    /**
     * Get a checkout session by ID
     *
     * @param string $sessionId
     * @return CheckoutSessionInterface
     * @throws NoSuchEntityException
     */
    public function get(string $sessionId): CheckoutSessionInterface
    {
        if (!isset($this->sessions[$sessionId])) {
            throw new NoSuchEntityException(
                __('UCP Checkout Session with ID "%1" does not exist.', $sessionId)
            );
        }

        return $this->sessions[$sessionId]['session'];
    }

    /**
     * Get masked quote ID for a session
     *
     * @param string $sessionId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMaskedQuoteId(string $sessionId): string
    {
        if (!isset($this->sessions[$sessionId])) {
            throw new NoSuchEntityException(
                __('UCP Checkout Session with ID "%1" does not exist.', $sessionId)
            );
        }

        return $this->sessions[$sessionId]['maskedQuoteId'];
    }

    /**
     * Get session ID by masked quote ID
     *
     * @param string $maskedQuoteId
     * @return string|null
     */
    public function getSessionIdByQuoteId(string $maskedQuoteId): ?string
    {
        return $this->quoteToSession[$maskedQuoteId] ?? null;
    }

    /**
     * Check if session exists
     *
     * @param string $sessionId
     * @return bool
     */
    public function exists(string $sessionId): bool
    {
        return isset($this->sessions[$sessionId]);
    }

    /**
     * Delete a checkout session
     *
     * @param string $sessionId
     * @return void
     */
    public function delete(string $sessionId): void
    {
        if (isset($this->sessions[$sessionId])) {
            $maskedQuoteId = $this->sessions[$sessionId]['maskedQuoteId'];
            unset($this->quoteToSession[$maskedQuoteId]);
            unset($this->sessions[$sessionId]);
        }
    }
}
