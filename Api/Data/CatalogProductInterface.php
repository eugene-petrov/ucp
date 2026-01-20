<?php
/**
 * UCP Catalog Product Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface CatalogProductInterface
{
    /**
     * Get product ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set product ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * Get product title
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Set product title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self;

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): self;

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
     * Get currency (ISO 4217)
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Set currency
     *
     * @param string $currency
     * @return $this
     */
    public function setCurrency(string $currency): self;

    /**
     * Get images
     *
     * @return \Aeqet\Ucp\Api\Data\CatalogImageInterface[]
     */
    public function getImages(): array;

    /**
     * Set images
     *
     * @param \Aeqet\Ucp\Api\Data\CatalogImageInterface[] $images
     * @return $this
     */
    public function setImages(array $images): self;

    /**
     * Get product URL
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set product URL
     *
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self;

    /**
     * Get in stock status
     *
     * @return bool
     */
    public function getInStock(): bool;

    /**
     * Set in stock status
     *
     * @param bool $inStock
     * @return $this
     */
    public function setInStock(bool $inStock): self;

    /**
     * Get attributes (key-value pairs)
     *
     * @return array|null
     */
    public function getAttributes(): ?array;

    /**
     * Set attributes
     *
     * @param array|null $attributes
     * @return $this
     */
    public function setAttributes(?array $attributes): self;

    /**
     * Get variants (for configurable products)
     *
     * @return \Aeqet\Ucp\Api\Data\VariantInterface[]|null
     */
    public function getVariants(): ?array;

    /**
     * Set variants
     *
     * @param \Aeqet\Ucp\Api\Data\VariantInterface[]|null $variants
     * @return $this
     */
    public function setVariants(?array $variants): self;
}
