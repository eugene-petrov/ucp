<?php
/**
 * UCP Catalog Category Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface CatalogCategoryInterface
{
    /**
     * Get category ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set category ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get category name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set category name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Get category description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set category description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): self;

    /**
     * Get category URL
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set category URL
     *
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self;

    /**
     * Get parent category ID
     *
     * @return string|null
     */
    public function getParentId(): ?string;

    /**
     * Set parent category ID
     *
     * @param string|null $parentId
     * @return $this
     */
    public function setParentId(?string $parentId): self;

    /**
     * Get category level
     *
     * @return int
     */
    public function getLevel(): int;

    /**
     * Set category level
     *
     * @param int $level
     * @return $this
     */
    public function setLevel(int $level): self;

    /**
     * Get image URL
     *
     * @return string|null
     */
    public function getImageUrl(): ?string;

    /**
     * Set image URL
     *
     * @param string|null $imageUrl
     * @return $this
     */
    public function setImageUrl(?string $imageUrl): self;

    /**
     * Get children categories
     *
     * @return \Aeqet\Ucp\Api\Data\CatalogCategoryInterface[]|null
     */
    public function getChildren(): ?array;

    /**
     * Set children categories
     *
     * @param \Aeqet\Ucp\Api\Data\CatalogCategoryInterface[]|null $children
     * @return $this
     */
    public function setChildren(?array $children): self;

    /**
     * Get product count in category
     *
     * @return int
     */
    public function getProductCount(): int;

    /**
     * Set product count
     *
     * @param int $productCount
     * @return $this
     */
    public function setProductCount(int $productCount): self;
}
