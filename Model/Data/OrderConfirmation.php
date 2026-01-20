<?php
/**
 * UCP Order Confirmation Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\OrderConfirmationInterface;
use Magento\Framework\DataObject;

class OrderConfirmation extends DataObject implements OrderConfirmationInterface
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
    public function setId(string $id): OrderConfirmationInterface
    {
        return $this->setData('id', $id);
    }

    /**
     * @inheritDoc
     */
    public function getPermalinkUrl(): string
    {
        return (string) $this->getData('permalink_url');
    }

    /**
     * @inheritDoc
     */
    public function setPermalinkUrl(string $permalinkUrl): OrderConfirmationInterface
    {
        return $this->setData('permalink_url', $permalinkUrl);
    }
}
