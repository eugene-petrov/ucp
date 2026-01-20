<?php
/**
 * UCP Item Data Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface ItemDataInterface
{
    /**
     * Get item ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set item ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self;

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
}
