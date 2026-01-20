<?php
/**
 * UCP Catalog Image Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface CatalogImageInterface
{
    /**
     * Get image ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set image ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get image URL
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set image URL
     *
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self;

    /**
     * Get thumbnail URL
     *
     * @return string|null
     */
    public function getThumbnailUrl(): ?string;

    /**
     * Set thumbnail URL
     *
     * @param string|null $thumbnailUrl
     * @return $this
     */
    public function setThumbnailUrl(?string $thumbnailUrl): self;

    /**
     * Get alt text
     *
     * @return string|null
     */
    public function getAltText(): ?string;

    /**
     * Set alt text
     *
     * @param string|null $altText
     * @return $this
     */
    public function setAltText(?string $altText): self;

    /**
     * Get position
     *
     * @return int
     */
    public function getPosition(): int;

    /**
     * Set position
     *
     * @param int $position
     * @return $this
     */
    public function setPosition(int $position): self;
}
