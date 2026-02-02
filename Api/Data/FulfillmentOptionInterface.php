<?php
/**
 * UCP Fulfillment Option Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface FulfillmentOptionInterface
{
    /**
     * Get fulfillment option ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set fulfillment option ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get fulfillment type (e.g. shipping, pickup)
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set fulfillment type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * Get display name
     *
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Set display name
     *
     * @param string $displayName
     * @return $this
     */
    public function setDisplayName(string $displayName): self;

    /**
     * Get price in cents
     *
     * @return int
     */
    public function getPrice(): int;

    /**
     * Set price in cents
     *
     * @param int $price
     * @return $this
     */
    public function setPrice(int $price): self;

    /**
     * Check if this option is selected
     *
     * @return bool
     */
    public function getSelected(): bool;

    /**
     * Set selected
     *
     * @param bool $selected
     * @return $this
     */
    public function setSelected(bool $selected): self;
}
