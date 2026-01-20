<?php
/**
 * UCP Cart Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

/**
 * Interface for UCP Cart data
 *
 * @api
 */
interface CartInterface
{
    /**
     * Get cart ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set cart ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get currency code
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
     * Get cart items
     *
     * @return \Aeqet\Ucp\Api\Data\CartItemInterface[]
     */
    public function getItems(): array;

    /**
     * Set cart items
     *
     * @param \Aeqet\Ucp\Api\Data\CartItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items): self;

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
     * Get item count
     *
     * @return int
     */
    public function getItemCount(): int;

    /**
     * Set item count
     *
     * @param int $count
     * @return $this
     */
    public function setItemCount(int $count): self;
}
