<?php
/**
 * UCP Cart Data Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\CartInterface;

class Cart implements CartInterface
{
    /**
     * @var string
     */
    private string $id = '';

    /**
     * @var string
     */
    private string $currency = 'USD';

    /**
     * @var array
     */
    private array $items = [];

    /**
     * @var array
     */
    private array $totals = [];

    /**
     * @var int
     */
    private int $itemCount = 0;

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function setId(string $id): CartInterface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @inheritDoc
     */
    public function setCurrency(string $currency): CartInterface
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $items): CartInterface
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTotals(): array
    {
        return $this->totals;
    }

    /**
     * @inheritDoc
     */
    public function setTotals(array $totals): CartInterface
    {
        $this->totals = $totals;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    /**
     * @inheritDoc
     */
    public function setItemCount(int $count): CartInterface
    {
        $this->itemCount = $count;
        return $this;
    }
}
