<?php
/**
 * UCP Catalog Image Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\CatalogImageInterface;
use Magento\Framework\DataObject;

class CatalogImage extends DataObject implements CatalogImageInterface
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
    public function setId(string $id): CatalogImageInterface
    {
        return $this->setData('id', $id);
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
    public function setUrl(string $url): CatalogImageInterface
    {
        return $this->setData('url', $url);
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailUrl(): ?string
    {
        return $this->getData('thumbnail_url');
    }

    /**
     * @inheritDoc
     */
    public function setThumbnailUrl(?string $thumbnailUrl): CatalogImageInterface
    {
        return $this->setData('thumbnail_url', $thumbnailUrl);
    }

    /**
     * @inheritDoc
     */
    public function getAltText(): ?string
    {
        return $this->getData('alt_text');
    }

    /**
     * @inheritDoc
     */
    public function setAltText(?string $altText): CatalogImageInterface
    {
        return $this->setData('alt_text', $altText);
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int
    {
        return (int) $this->getData('position');
    }

    /**
     * @inheritDoc
     */
    public function setPosition(int $position): CatalogImageInterface
    {
        return $this->setData('position', $position);
    }
}
