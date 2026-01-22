<?php
/**
 * UCP Checkout Session Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface CheckoutSessionInterface
{
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_READY_FOR_COMPLETE = 'ready_for_complete';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELED = 'canceled';

    /**
     * Get session ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set session ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Get currency code (ISO 4217)
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Set currency code
     *
     * @param string $currency
     * @return $this
     */
    public function setCurrency(string $currency): self;

    /**
     * Get expiration datetime (RFC 3339)
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string;

    /**
     * Set expiration datetime
     *
     * @param string|null $expiresAt
     * @return $this
     */
    public function setExpiresAt(?string $expiresAt): self;

    /**
     * Get UCP meta information
     *
     * @return \Aeqet\Ucp\Api\Data\UcpMetaInterface|null
     */
    public function getUcp(): ?UcpMetaInterface;

    /**
     * Set UCP meta
     *
     * @param \Aeqet\Ucp\Api\Data\UcpMetaInterface $ucp
     * @return $this
     */
    public function setUcp(UcpMetaInterface $ucp): self;

    /**
     * Get line items
     *
     * @return \Aeqet\Ucp\Api\Data\LineItemInterface[]
     */
    public function getLineItems(): array;

    /**
     * Set line items
     *
     * @param \Aeqet\Ucp\Api\Data\LineItemInterface[] $lineItems
     * @return $this
     */
    public function setLineItems(array $lineItems): self;

    /**
     * Get totals
     *
     * @return \Aeqet\Ucp\Api\Data\TotalInterface[]
     */
    public function getTotals(): array;

    /**
     * Set totals
     *
     * @param \Aeqet\Ucp\Api\Data\TotalInterface[] $totals
     * @return $this
     */
    public function setTotals(array $totals): self;

    /**
     * Get buyer information
     *
     * @return \Aeqet\Ucp\Api\Data\BuyerInterface|null
     */
    public function getBuyer(): ?BuyerInterface;

    /**
     * Set buyer
     *
     * @param \Aeqet\Ucp\Api\Data\BuyerInterface|null $buyer
     * @return $this
     */
    public function setBuyer(?BuyerInterface $buyer): self;

    /**
     * Get payment information
     *
     * @return \Aeqet\Ucp\Api\Data\PaymentInterface|null
     */
    public function getPayment(): ?PaymentInterface;

    /**
     * Set payment
     *
     * @param \Aeqet\Ucp\Api\Data\PaymentInterface $payment
     * @return $this
     */
    public function setPayment(PaymentInterface $payment): self;

    /**
     * Get links
     *
     * @return \Aeqet\Ucp\Api\Data\LinkInterface[]
     */
    public function getLinks(): array;

    /**
     * Set links
     *
     * @param \Aeqet\Ucp\Api\Data\LinkInterface[] $links
     * @return $this
     */
    public function setLinks(array $links): self;

    /**
     * Get messages
     *
     * @return \Aeqet\Ucp\Api\Data\MessageInterface[]|null
     */
    public function getMessages(): ?array;

    /**
     * Set messages
     *
     * @param \Aeqet\Ucp\Api\Data\MessageInterface[]|null $messages
     * @return $this
     */
    public function setMessages(?array $messages): self;

    /**
     * Get order confirmation (only after complete)
     *
     * @return \Aeqet\Ucp\Api\Data\OrderConfirmationInterface|null
     */
    public function getOrder(): ?OrderConfirmationInterface;

    /**
     * Set order confirmation
     *
     * @param \Aeqet\Ucp\Api\Data\OrderConfirmationInterface|null $order
     * @return $this
     */
    public function setOrder(?OrderConfirmationInterface $order): self;

    /**
     * Get fulfillment options
     *
     * @return \Aeqet\Ucp\Api\Data\FulfillmentOptionInterface[]
     */
    public function getFulfillmentOptions(): array;

    /**
     * Set fulfillment options
     *
     * @param \Aeqet\Ucp\Api\Data\FulfillmentOptionInterface[] $fulfillmentOptions
     * @return $this
     */
    public function setFulfillmentOptions(array $fulfillmentOptions): self;
}
