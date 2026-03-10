<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Webhook;

use Aeqet\Ucp\Model\ResourceModel\WebhookDelivery as WebhookDeliveryResource;
use Magento\Framework\Model\AbstractModel;

class DeliveryEntity extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'aeqet_ucp_webhook_delivery';

    /**
     * @var string
     */
    protected $_eventObject = 'webhook_delivery';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(WebhookDeliveryResource::class);
    }

    /**
     * Get delivery ID.
     *
     * @return string|null
     */
    public function getDeliveryId(): ?string
    {
        return $this->getData('delivery_id');
    }

    /**
     * Set delivery ID.
     *
     * @param string $deliveryId
     * @return $this
     */
    public function setDeliveryId(string $deliveryId): self
    {
        return $this->setData('delivery_id', $deliveryId);
    }

    /**
     * Get target webhook URL.
     *
     * @return string|null
     */
    public function getTargetUrl(): ?string
    {
        return $this->getData('target_url');
    }

    /**
     * Set target webhook URL.
     *
     * @param string $targetUrl
     * @return $this
     */
    public function setTargetUrl(string $targetUrl): self
    {
        return $this->setData('target_url', $targetUrl);
    }

    /**
     * Get event type.
     *
     * @return string|null
     */
    public function getEventType(): ?string
    {
        return $this->getData('event_type');
    }

    /**
     * Set event type.
     *
     * @param string $eventType
     * @return $this
     */
    public function setEventType(string $eventType): self
    {
        return $this->setData('event_type', $eventType);
    }

    /**
     * Get JSON payload.
     *
     * @return string|null
     */
    public function getPayload(): ?string
    {
        return $this->getData('payload');
    }

    /**
     * Set JSON payload.
     *
     * @param string $payload
     * @return $this
     */
    public function setPayload(string $payload): self
    {
        return $this->setData('payload', $payload);
    }

    /**
     * Get delivery status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    /**
     * Set delivery status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    /**
     * Get number of delivery attempts.
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return (int) $this->getData('attempts');
    }

    /**
     * Set number of delivery attempts.
     *
     * @param int $attempts
     * @return $this
     */
    public function setAttempts(int $attempts): self
    {
        return $this->setData('attempts', $attempts);
    }

    /**
     * Get next retry datetime.
     *
     * @return string|null
     */
    public function getNextRetryAt(): ?string
    {
        return $this->getData('next_retry_at');
    }

    /**
     * Set next retry datetime.
     *
     * @param string|null $nextRetryAt
     * @return $this
     */
    public function setNextRetryAt(?string $nextRetryAt): self
    {
        return $this->setData('next_retry_at', $nextRetryAt);
    }

    /**
     * Get last error message.
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->getData('last_error');
    }

    /**
     * Set last error message.
     *
     * @param string|null $lastError
     * @return $this
     */
    public function setLastError(?string $lastError): self
    {
        return $this->setData('last_error', $lastError);
    }
}
