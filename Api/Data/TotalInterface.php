<?php
/**
 * UCP Total Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface TotalInterface
{
    public const TYPE_SUBTOTAL = 'subtotal';
    public const TYPE_DISCOUNT = 'discount';
    public const TYPE_FULFILLMENT = 'fulfillment';
    public const TYPE_TAX = 'tax';
    public const TYPE_TOTAL = 'total';

    /**
     * Get type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * Get amount in cents
     *
     * @return int
     */
    public function getAmount(): int;

    /**
     * Set amount in cents
     *
     * @param int $amount
     * @return $this
     */
    public function setAmount(int $amount): self;

    /**
     * Get display text
     *
     * @return string|null
     */
    public function getDisplayText(): ?string;

    /**
     * Set display text
     *
     * @param string|null $displayText
     * @return $this
     */
    public function setDisplayText(?string $displayText): self;
}
