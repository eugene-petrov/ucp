<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data\Webhook;

interface DeliveryInterface
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED    = 'failed';

    /**
     * Get delivery ID.
     *
     * @return string|null
     */
    public function getDeliveryId(): ?string;

    /**
     * Set delivery ID.
     *
     * @param string $deliveryId
     * @return $this
     */
    public function setDeliveryId(string $deliveryId): self;

    /**
     * Get target webhook URL.
     *
     * @return string|null
     */
    public function getTargetUrl(): ?string;

    /**
     * Set target webhook URL.
     *
     * @param string $targetUrl
     * @return $this
     */
    public function setTargetUrl(string $targetUrl): self;

    /**
     * Get event type.
     *
     * @return string|null
     */
    public function getEventType(): ?string;

    /**
     * Set event type.
     *
     * @param string $eventType
     * @return $this
     */
    public function setEventType(string $eventType): self;

    /**
     * Get JSON payload.
     *
     * @return string|null
     */
    public function getPayload(): ?string;

    /**
     * Set JSON payload.
     *
     * @param string $payload
     * @return $this
     */
    public function setPayload(string $payload): self;

    /**
     * Get delivery status.
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set delivery status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Get number of delivery attempts.
     *
     * @return int
     */
    public function getAttempts(): int;

    /**
     * Set number of delivery attempts.
     *
     * @param int $attempts
     * @return $this
     */
    public function setAttempts(int $attempts): self;

    /**
     * Get next retry datetime.
     *
     * @return string|null
     */
    public function getNextRetryAt(): ?string;

    /**
     * Set next retry datetime.
     *
     * @param string|null $nextRetryAt
     * @return $this
     */
    public function setNextRetryAt(?string $nextRetryAt): self;

    /**
     * Get last error message.
     *
     * @return string|null
     */
    public function getLastError(): ?string;

    /**
     * Set last error message.
     *
     * @param string|null $lastError
     * @return $this
     */
    public function setLastError(?string $lastError): self;

    /**
     * Get creation datetime.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Get last update datetime.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
