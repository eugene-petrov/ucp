<?php
/**
 * UCP Fulfillment Option Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\FulfillmentOptionInterface;
use Magento\Framework\DataObject;

class FulfillmentOption extends DataObject implements FulfillmentOptionInterface
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
    public function setId(string $id): FulfillmentOptionInterface
    {
        return $this->setData('id', $id);
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return (string) $this->getData('type');
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type): FulfillmentOptionInterface
    {
        return $this->setData('type', $type);
    }

    /**
     * @inheritDoc
     */
    public function getDisplayName(): string
    {
        return (string) $this->getData('display_name');
    }

    /**
     * @inheritDoc
     */
    public function setDisplayName(string $displayName): FulfillmentOptionInterface
    {
        return $this->setData('display_name', $displayName);
    }

    /**
     * @inheritDoc
     */
    public function getPrice(): int
    {
        return (int) $this->getData('price');
    }

    /**
     * @inheritDoc
     */
    public function setPrice(int $price): FulfillmentOptionInterface
    {
        return $this->setData('price', $price);
    }

    /**
     * @inheritDoc
     */
    public function getSelected(): bool
    {
        return (bool) $this->getData('selected');
    }

    /**
     * @inheritDoc
     */
    public function setSelected(bool $selected): FulfillmentOptionInterface
    {
        return $this->setData('selected', $selected);
    }
}
