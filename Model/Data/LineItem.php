<?php
/**
 * UCP Line Item Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\ItemDataInterface;
use Aeqet\Ucp\Api\Data\LineItemInterface;
use Magento\Framework\DataObject;

class LineItem extends DataObject implements LineItemInterface
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
    public function setId(string $id): LineItemInterface
    {
        return $this->setData('id', $id);
    }

    /**
     * @inheritDoc
     */
    public function getItem(): ItemDataInterface
    {
        return $this->getData('item');
    }

    /**
     * @inheritDoc
     */
    public function setItem(ItemDataInterface $item): LineItemInterface
    {
        return $this->setData('item', $item);
    }

    /**
     * @inheritDoc
     */
    public function getQuantity(): int
    {
        return (int) $this->getData('quantity');
    }

    /**
     * @inheritDoc
     */
    public function setQuantity(int $quantity): LineItemInterface
    {
        return $this->setData('quantity', $quantity);
    }

    /**
     * @inheritDoc
     */
    public function getTotals(): ?array
    {
        return $this->getData('totals');
    }

    /**
     * @inheritDoc
     */
    public function setTotals(?array $totals): LineItemInterface
    {
        return $this->setData('totals', $totals);
    }

    /**
     * @inheritDoc
     */
    public function getParentId(): ?string
    {
        return $this->getData('parent_id');
    }

    /**
     * @inheritDoc
     */
    public function setParentId(?string $parentId): LineItemInterface
    {
        return $this->setData('parent_id', $parentId);
    }
}
