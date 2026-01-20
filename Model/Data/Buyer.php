<?php
/**
 * UCP Buyer Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\BuyerInterface;
use Magento\Framework\DataObject;

class Buyer extends DataObject implements BuyerInterface
{
    /**
     * @inheritDoc
     */
    public function getFirstName(): ?string
    {
        return $this->getData('first_name');
    }

    /**
     * @inheritDoc
     */
    public function setFirstName(?string $firstName): BuyerInterface
    {
        return $this->setData('first_name', $firstName);
    }

    /**
     * @inheritDoc
     */
    public function getLastName(): ?string
    {
        return $this->getData('last_name');
    }

    /**
     * @inheritDoc
     */
    public function setLastName(?string $lastName): BuyerInterface
    {
        return $this->setData('last_name', $lastName);
    }

    /**
     * @inheritDoc
     */
    public function getEmail(): ?string
    {
        return $this->getData('email');
    }

    /**
     * @inheritDoc
     */
    public function setEmail(?string $email): BuyerInterface
    {
        return $this->setData('email', $email);
    }

    /**
     * @inheritDoc
     */
    public function getPhoneNumber(): ?string
    {
        return $this->getData('phone_number');
    }

    /**
     * @inheritDoc
     */
    public function setPhoneNumber(?string $phoneNumber): BuyerInterface
    {
        return $this->setData('phone_number', $phoneNumber);
    }
}
