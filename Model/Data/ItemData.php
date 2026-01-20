<?php
/**
 * UCP Item Data Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\ItemDataInterface;
use Magento\Framework\DataObject;

class ItemData extends DataObject implements ItemDataInterface
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
    public function setId(string $id): ItemDataInterface
    {
        return $this->setData('id', $id);
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
    public function setTitle(string $title): ItemDataInterface
    {
        return $this->setData('title', $title);
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
    public function setPrice(int $price): ItemDataInterface
    {
        return $this->setData('price', $price);
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
    public function setImageUrl(?string $imageUrl): ItemDataInterface
    {
        return $this->setData('image_url', $imageUrl);
    }
}
