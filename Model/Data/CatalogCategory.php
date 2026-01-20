<?php
/**
 * UCP Catalog Category Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\CatalogCategoryInterface;
use Magento\Framework\DataObject;

class CatalogCategory extends DataObject implements CatalogCategoryInterface
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
    public function setId(string $id): CatalogCategoryInterface
    {
        return $this->setData('id', $id);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return (string) $this->getData('name');
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): CatalogCategoryInterface
    {
        return $this->setData('name', $name);
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
    public function setDescription(?string $description): CatalogCategoryInterface
    {
        return $this->setData('description', $description);
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
    public function setUrl(string $url): CatalogCategoryInterface
    {
        return $this->setData('url', $url);
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
    public function setParentId(?string $parentId): CatalogCategoryInterface
    {
        return $this->setData('parent_id', $parentId);
    }

    /**
     * @inheritDoc
     */
    public function getLevel(): int
    {
        return (int) $this->getData('level');
    }

    /**
     * @inheritDoc
     */
    public function setLevel(int $level): CatalogCategoryInterface
    {
        return $this->setData('level', $level);
    }

    /**
     * @inheritDoc
     */
    public function getImageUrl(): ?string
    {
        return $this->getData('image_url');
    }

    /**
     * @inheritDoc
     */
    public function setImageUrl(?string $imageUrl): CatalogCategoryInterface
    {
        return $this->setData('image_url', $imageUrl);
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): ?array
    {
        return $this->getData('children');
    }

    /**
     * @inheritDoc
     */
    public function setChildren(?array $children): CatalogCategoryInterface
    {
        return $this->setData('children', $children);
    }

    /**
     * @inheritDoc
     */
    public function getProductCount(): int
    {
        return (int) $this->getData('product_count');
    }

    /**
     * @inheritDoc
     */
    public function setProductCount(int $productCount): CatalogCategoryInterface
    {
        return $this->setData('product_count', $productCount);
    }
}
