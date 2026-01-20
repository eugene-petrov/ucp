<?php
/**
 * UCP Cart Item Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

/**
 * Interface for UCP Cart Item data
 *
 * @api
 */
interface CartItemInterface
{
    /**
     * Get item ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set item ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQuantity(): int;

    /**
     * Set quantity
     *
     * @param int $quantity
     * @return $this
     */
    public function setQuantity(int $quantity): self;

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
     * Get subtotal in cents
     *
     * @return int
     */
    public function getSubtotal(): int;

    /**
     * Set subtotal in cents
     *
     * @param int $subtotal
     * @return $this
     */
    public function setSubtotal(int $subtotal): self;

    /**
     * Get product
     *
     * @return \Aeqet\Ucp\Api\Data\CatalogProductInterface
     */
    public function getProduct(): CatalogProductInterface;

    /**
     * Set product
     *
     * @param \Aeqet\Ucp\Api\Data\CatalogProductInterface $product
     * @return $this
     */
    public function setProduct(CatalogProductInterface $product): self;
}
