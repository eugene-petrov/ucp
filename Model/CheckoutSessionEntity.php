<?php
/**
 * UCP Checkout Session Entity Model
 *
 * This model represents the database entity for checkout session storage.
 * It's separate from the CheckoutSession DataObject which is used for API responses.
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Model\ResourceModel\CheckoutSession as CheckoutSessionResource;
use Magento\Framework\Model\AbstractModel;

class CheckoutSessionEntity extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'aeqet_ucp_checkout_session';

    /**
     * @var string
     */
    protected $_eventObject = 'checkout_session';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(CheckoutSessionResource::class);
    }

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        $entityId = $this->getData('entity_id');
        return $entityId !== null ? (int) $entityId : null;
    }

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId): self
    {
        return $this->setData('entity_id', $entityId);
    }

    /**
     * Get session ID
     *
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->getData('session_id');
    }

    /**
     * Set session ID
     *
     * @param string $sessionId
     * @return $this
     */
    public function setSessionId(string $sessionId): self
    {
        return $this->setData('session_id', $sessionId);
    }

    /**
     * Get quote ID
     *
     * @return int|null
     */
    public function getQuoteId(): ?int
    {
        $quoteId = $this->getData('quote_id');
        return $quoteId !== null ? (int) $quoteId : null;
    }

    /**
     * Set quote ID
     *
     * @param int $quoteId
     * @return $this
     */
    public function setQuoteId(int $quoteId): self
    {
        return $this->setData('quote_id', $quoteId);
    }

    /**
     * Get masked quote ID
     *
     * @return string|null
     */
    public function getMaskedQuoteId(): ?string
    {
        return $this->getData('masked_quote_id');
    }

    /**
     * Set masked quote ID
     *
     * @param string $maskedQuoteId
     * @return $this
     */
    public function setMaskedQuoteId(string $maskedQuoteId): self
    {
        return $this->setData('masked_quote_id', $maskedQuoteId);
    }

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    /**
     * Get UCP data (JSON string)
     *
     * @return string|null
     */
    public function getUcpData(): ?string
    {
        return $this->getData('ucp_data');
    }

    /**
     * Set UCP data (JSON string)
     *
     * @param string $ucpData
     * @return $this
     */
    public function setUcpData(string $ucpData): self
    {
        return $this->setData('ucp_data', $ucpData);
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }
}
