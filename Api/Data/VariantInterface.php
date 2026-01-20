<?php
/**
 * UCP Variant Interface
 *
 * Represents a variant of a configurable product.
 * Used for proper WebAPI serialization (as objects instead of arrays).
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface VariantInterface
{
    /**
     * Get variant product ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set variant product ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get variant SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set variant SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * Get variant price in cents
     *
     * @return int
     */
    public function getPrice(): int;

    /**
     * Set variant price in cents
     *
     * @param int $price
     * @return $this
     */
    public function setPrice(int $price): self;

    /**
     * Get in stock status
     *
     * @return bool
     */
    public function getInStock(): bool;

    /**
     * Set in stock status
     *
     * @param bool $inStock
     * @return $this
     */
    public function setInStock(bool $inStock): self;

    /**
     * Get variant attributes (key-value pairs like color, size)
     *
     * @return \Aeqet\Ucp\Api\Data\VariantAttributeInterface[]|null
     */
    public function getAttributes(): ?array;

    /**
     * Set variant attributes
     *
     * @param \Aeqet\Ucp\Api\Data\VariantAttributeInterface[]|null $attributes
     * @return $this
     */
    public function setAttributes(?array $attributes): self;
}
