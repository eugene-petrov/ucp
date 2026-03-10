<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data\Webhook;

use Aeqet\Ucp\Api\Data\Webhook\DeliveryInterface;
use Magento\Framework\DataObject;

class Delivery extends DataObject implements DeliveryInterface
{
    /**
     * @inheritDoc
     */
    public function getDeliveryId(): ?string
    {
        return $this->getData('delivery_id');
    }

    /**
     * @inheritDoc
     */
    public function setDeliveryId(string $deliveryId): self
    {
        return $this->setData('delivery_id', $deliveryId);
    }

    /**
     * @inheritDoc
     */
    public function getTargetUrl(): ?string
    {
        return $this->getData('target_url');
    }

    /**
     * @inheritDoc
     */
    public function setTargetUrl(string $targetUrl): self
    {
        return $this->setData('target_url', $targetUrl);
    }

    /**
     * @inheritDoc
     */
    public function getEventType(): ?string
    {
        return $this->getData('event_type');
    }

    /**
     * @inheritDoc
     */
    public function setEventType(string $eventType): self
    {
        return $this->setData('event_type', $eventType);
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): ?string
    {
        return $this->getData('payload');
    }

    /**
     * @inheritDoc
     */
    public function setPayload(string $payload): self
    {
        return $this->setData('payload', $payload);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }

    /**
     * @inheritDoc
     */
    public function getAttempts(): int
    {
        return (int) $this->getData('attempts');
    }

    /**
     * @inheritDoc
     */
    public function setAttempts(int $attempts): self
    {
        return $this->setData('attempts', $attempts);
    }

    /**
     * @inheritDoc
     */
    public function getNextRetryAt(): ?string
    {
        return $this->getData('next_retry_at');
    }

    /**
     * @inheritDoc
     */
    public function setNextRetryAt(?string $nextRetryAt): self
    {
        return $this->setData('next_retry_at', $nextRetryAt);
    }

    /**
     * @inheritDoc
     */
    public function getLastError(): ?string
    {
        return $this->getData('last_error');
    }

    /**
     * @inheritDoc
     */
    public function setLastError(?string $lastError): self
    {
        return $this->setData('last_error', $lastError);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }
}
