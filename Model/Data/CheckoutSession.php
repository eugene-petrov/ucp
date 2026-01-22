<?php
/**
 * UCP Checkout Session Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\BuyerInterface;
use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Api\Data\OrderConfirmationInterface;
use Aeqet\Ucp\Api\Data\PaymentInterface;
use Aeqet\Ucp\Api\Data\UcpMetaInterface;
use Magento\Framework\DataObject;

class CheckoutSession extends DataObject implements CheckoutSessionInterface
{
    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return (string) $this->getData('id');
    }

    /**
     * @inheritDoc
     */
    public function setId(string $id): CheckoutSessionInterface
    {
        return $this->setData('id', $id);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return (string) $this->getData('status');
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): CheckoutSessionInterface
    {
        return $this->setData('status', $status);
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return (string) $this->getData('currency');
    }

    /**
     * @inheritDoc
     */
    public function setCurrency(string $currency): CheckoutSessionInterface
    {
        return $this->setData('currency', $currency);
    }

    /**
     * @inheritDoc
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData('expires_at');
    }

    /**
     * @inheritDoc
     */
    public function setExpiresAt(?string $expiresAt): CheckoutSessionInterface
    {
        return $this->setData('expires_at', $expiresAt);
    }

    /**
     * @inheritDoc
     */
    public function getUcp(): ?UcpMetaInterface
    {
        return $this->getData('ucp');
    }

    /**
     * @inheritDoc
     */
    public function setUcp(UcpMetaInterface $ucp): CheckoutSessionInterface
    {
        return $this->setData('ucp', $ucp);
    }

    /**
     * @inheritDoc
     */
    public function getLineItems(): array
    {
        return $this->getData('line_items') ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setLineItems(array $lineItems): CheckoutSessionInterface
    {
        return $this->setData('line_items', $lineItems);
    }

    /**
     * @inheritDoc
     */
    public function getTotals(): array
    {
        return $this->getData('totals') ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setTotals(array $totals): CheckoutSessionInterface
    {
        return $this->setData('totals', $totals);
    }

    /**
     * @inheritDoc
     */
    public function getBuyer(): ?BuyerInterface
    {
        return $this->getData('buyer');
    }

    /**
     * @inheritDoc
     */
    public function setBuyer(?BuyerInterface $buyer): CheckoutSessionInterface
    {
        return $this->setData('buyer', $buyer);
    }

    /**
     * @inheritDoc
     */
    public function getPayment(): ?PaymentInterface
    {
        return $this->getData('payment');
    }

    /**
     * @inheritDoc
     */
    public function setPayment(PaymentInterface $payment): CheckoutSessionInterface
    {
        return $this->setData('payment', $payment);
    }

    /**
     * @inheritDoc
     */
    public function getLinks(): array
    {
        return $this->getData('links') ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setLinks(array $links): CheckoutSessionInterface
    {
        return $this->setData('links', $links);
    }

    /**
     * @inheritDoc
     */
    public function getMessages(): ?array
    {
        return $this->getData('messages');
    }

    /**
     * @inheritDoc
     */
    public function setMessages(?array $messages): CheckoutSessionInterface
    {
        return $this->setData('messages', $messages);
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): ?OrderConfirmationInterface
    {
        return $this->getData('order');
    }

    /**
     * @inheritDoc
     */
    public function setOrder(?OrderConfirmationInterface $order): CheckoutSessionInterface
    {
        return $this->setData('order', $order);
    }

    /**
     * @inheritDoc
     */
    public function getFulfillmentOptions(): array
    {
        return $this->getData('fulfillment_options') ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setFulfillmentOptions(array $fulfillmentOptions): CheckoutSessionInterface
    {
        return $this->setData('fulfillment_options', $fulfillmentOptions);
    }
}
