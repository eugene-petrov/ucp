<?php
/**
 * UCP Catalog Product Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\CatalogProductInterface;
use Magento\Framework\DataObject;

class CatalogProduct extends DataObject implements CatalogProductInterface
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
    public function setId(string $id): CatalogProductInterface
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
    public function setSku(string $sku): CatalogProductInterface
    {
        return $this->setData('sku', $sku);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return (string) $this->getData('title');
    }

    /**
     * @inheritDoc
     */
    public function setTitle(string $title): CatalogProductInterface
    {
        return $this->setData('title', $title);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->getData('description');
    }

    /**
     * @inheritDoc
     */
    public function setDescription(?string $description): CatalogProductInterface
    {
        return $this->setData('description', $description);
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
    public function setPrice(int $price): CatalogProductInterface
    {
        return $this->setData('price', $price);
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return (string) $this->getData('currency');
    }

    /**
     * @inheritDoc
     */
    public function setCurrency(string $currency): CatalogProductInterface
    {
        return $this->setData('currency', $currency);
    }

    /**
     * @inheritDoc
     */
    public function getImages(): array
    {
        return $this->getData('images') ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setImages(array $images): CatalogProductInterface
    {
        return $this->setData('images', $images);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        return (string) $this->getData('url');
    }

    /**
     * @inheritDoc
     */
    public function setUrl(string $url): CatalogProductInterface
    {
        return $this->setData('url', $url);
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
    public function setInStock(bool $inStock): CatalogProductInterface
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
    public function setAttributes(?array $attributes): CatalogProductInterface
    {
        return $this->setData('attributes', $attributes);
    }

    /**
     * @inheritDoc
     */
    public function getVariants(): ?array
    {
        return $this->getData('variants');
    }

    /**
     * @inheritDoc
     */
    public function setVariants(?array $variants): CatalogProductInterface
    {
        return $this->setData('variants', $variants);
    }
}
