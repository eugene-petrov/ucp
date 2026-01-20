<?php
/**
 * UCP Variant Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\VariantInterface;
use Magento\Framework\DataObject;

class Variant extends DataObject implements VariantInterface
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
    public function setId(string $id): VariantInterface
    {
        return $this->setData('id', $id);
    }

    /**
     * @inheritDoc
     */
    public function getSku(): string
    {
        return (string) $this->getData('sku');
    }

    /**
     * @inheritDoc
     */
    public function setSku(string $sku): VariantInterface
    {
        return $this->setData('sku', $sku);
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
    public function setPrice(int $price): VariantInterface
    {
        return $this->setData('price', $price);
    }

    /**
     * @inheritDoc
     */
    public function getInStock(): bool
    {
        return (bool) $this->getData('in_stock');
    }

    /**
     * @inheritDoc
     */
    public function setInStock(bool $inStock): VariantInterface
    {
        return $this->setData('in_stock', $inStock);
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): ?array
    {
        return $this->getData('attributes');
    }

    /**
     * @inheritDoc
     */
    public function setAttributes(?array $attributes): VariantInterface
    {
        return $this->setData('attributes', $attributes);
    }
}
