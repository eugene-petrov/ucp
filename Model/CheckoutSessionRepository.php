<?php
/**
 * UCP Checkout Session Repository
 *
 * Persists checkout sessions to the database with request-level caching.
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Model\ResourceModel\CheckoutSession as CheckoutSessionResource;
use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Psr\Log\LoggerInterface;

class CheckoutSessionRepository
{
    /**
     * Request-level cache for loaded sessions
     * Maps session ID to [session, maskedQuoteId]
     *
     * @var array
     */
    private array $sessionCache = [];

    /**
     * Maps masked quote ID to session ID (cache)
     *
     * @var array
     */
    private array $quoteToSessionCache = [];

    /**
     * @param CheckoutSessionResource $resource
     * @param CheckoutSessionEntityFactory $entityFactory
     * @param CheckoutSessionSerializer $serializer
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CheckoutSessionResource $resource,
        private readonly CheckoutSessionEntityFactory $entityFactory,
        private readonly CheckoutSessionSerializer $serializer,
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Save a checkout session
     *
     * @param CheckoutSessionInterface $session
     * @param string $maskedQuoteId
     * @return CheckoutSessionInterface
     * @throws CouldNotSaveException
     */
    public function save(CheckoutSessionInterface $session, string $maskedQuoteId): CheckoutSessionInterface
    {
        $sessionId = $session->getId();

        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedQuoteId);
            $ucpData = $this->serializer->serialize($session);
            $entity = $this->entityFactory->create();
            $this->resource->load($entity, $sessionId, 'session_id');

            if (!$entity->getSessionId()) {
                $entity->setSessionId($sessionId);
            }

            $entity->setQuoteId($quoteId);
            $entity->setMaskedQuoteId($maskedQuoteId);
            $entity->setStatus($session->getStatus());
            $entity->setUcpData($ucpData);

            $this->resource->save($entity);

            $this->sessionCache[$sessionId] = [
                'session' => $session,
                'maskedQuoteId' => $maskedQuoteId
            ];
            $this->quoteToSessionCache[$maskedQuoteId] = $sessionId;

            $this->logger->debug('Checkout session saved', [
                'session_id' => $sessionId,
                'masked_quote_id' => $maskedQuoteId,
                'quote_id' => $quoteId,
                'status' => $session->getStatus()
            ]);

        } catch (Exception $e) {
            $this->logger->error('Failed to save checkout session', [
                'session_id' => $sessionId,
                'masked_quote_id' => $maskedQuoteId,
                'error' => $e->getMessage()
            ]);
            throw new CouldNotSaveException(
                __('Could not save checkout session: %1', $e->getMessage()),
                $e
            );
        }

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
        if (isset($this->sessionCache[$sessionId])) {
            return $this->sessionCache[$sessionId]['session'];
        }

        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $sessionId, 'session_id');

        if (!$entity->getSessionId()) {
            throw new NoSuchEntityException(
                __('UCP Checkout Session with ID "%1" does not exist.', $sessionId)
            );
        }

        $session = $this->serializer->deserialize($entity->getUcpData());
        $maskedQuoteId = $entity->getMaskedQuoteId();

        $this->sessionCache[$sessionId] = [
            'session' => $session,
            'maskedQuoteId' => $maskedQuoteId
        ];
        $this->quoteToSessionCache[$maskedQuoteId] = $sessionId;

        return $session;
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
        if (isset($this->sessionCache[$sessionId])) {
            return $this->sessionCache[$sessionId]['maskedQuoteId'];
        }

        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $sessionId, 'session_id');

        if (!$entity->getSessionId()) {
            throw new NoSuchEntityException(
                __('UCP Checkout Session with ID "%1" does not exist.', $sessionId)
            );
        }

        return $entity->getMaskedQuoteId();
    }

    /**
     * Get session ID by masked quote ID
     *
     * @param string $maskedQuoteId
     * @return string|null
     */
    public function getSessionIdByQuoteId(string $maskedQuoteId): ?string
    {
        if (isset($this->quoteToSessionCache[$maskedQuoteId])) {
            return $this->quoteToSessionCache[$maskedQuoteId];
        }

        $sessionId = $this->resource->getSessionIdByMaskedQuoteId($maskedQuoteId);

        if ($sessionId) {
            $this->quoteToSessionCache[$maskedQuoteId] = $sessionId;
        }

        return $sessionId;
    }

    /**
     * Check if session exists
     *
     * @param string $sessionId
     * @return bool
     */
    public function exists(string $sessionId): bool
    {
        if (isset($this->sessionCache[$sessionId])) {
            return true;
        }

        return $this->resource->sessionExists($sessionId);
    }

    /**
     * Delete a checkout session
     *
     * @param string $sessionId
     * @return void
     */
    public function delete(string $sessionId): void
    {
        if (isset($this->sessionCache[$sessionId])) {
            $maskedQuoteId = $this->sessionCache[$sessionId]['maskedQuoteId'];
            unset($this->quoteToSessionCache[$maskedQuoteId]);
            unset($this->sessionCache[$sessionId]);
        }

        $rowsDeleted = $this->resource->deleteBySessionId($sessionId);

        $this->logger->debug('Checkout session deleted', [
            'session_id' => $sessionId,
            'rows_deleted' => $rowsDeleted
        ]);
    }

    /**
     * Clear the request-level cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->sessionCache = [];
        $this->quoteToSessionCache = [];
    }
}
