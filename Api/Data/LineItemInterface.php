<?php
/**
 * UCP Line Item Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface LineItemInterface
{
    /**
     * Get line item ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set line item ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get item data
     *
     * @return \Aeqet\Ucp\Api\Data\ItemDataInterface
     */
    public function getItem(): ItemDataInterface;

    /**
     * Set item data
     *
     * @param \Aeqet\Ucp\Api\Data\ItemDataInterface $item
     * @return $this
     */
    public function setItem(ItemDataInterface $item): self;

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
     * Get totals
     *
     * @return \Aeqet\Ucp\Api\Data\TotalInterface[]|null
     */
    public function getTotals(): ?array;

    /**
     * Set totals
     *
     * @param \Aeqet\Ucp\Api\Data\TotalInterface[]|null $totals
     * @return $this
     */
    public function setTotals(?array $totals): self;

    /**
     * Get parent ID (for nested items)
     *
     * @return string|null
     */
    public function getParentId(): ?string;

    /**
     * Set parent ID
     *
     * @param string|null $parentId
     * @return $this
     */
    public function setParentId(?string $parentId): self;
}
