<?php
/**
 * UCP Address Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\AddressInterface;
use Magento\Framework\DataObject;

class Address extends DataObject implements AddressInterface
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
    public function setFirstName(?string $firstName): AddressInterface
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
    public function setLastName(?string $lastName): AddressInterface
    {
        return $this->setData('last_name', $lastName);
    }

    /**
     * @inheritDoc
     */
    public function getStreetLine1(): ?string
    {
        return $this->getData('street_line1');
    }

    /**
     * @inheritDoc
     */
    public function setStreetLine1(?string $streetLine1): AddressInterface
    {
        return $this->setData('street_line1', $streetLine1);
    }

    /**
     * @inheritDoc
     */
    public function getStreetLine2(): ?string
    {
        return $this->getData('street_line2');
    }

    /**
     * @inheritDoc
     */
    public function setStreetLine2(?string $streetLine2): AddressInterface
    {
        return $this->setData('street_line2', $streetLine2);
    }

    /**
     * @inheritDoc
     */
    public function getCity(): ?string
    {
        return $this->getData('city');
    }

    /**
     * @inheritDoc
     */
    public function setCity(?string $city): AddressInterface
    {
        return $this->setData('city', $city);
    }

    /**
     * @inheritDoc
     */
    public function getState(): ?string
    {
        return $this->getData('state');
    }

    /**
     * @inheritDoc
     */
    public function setState(?string $state): AddressInterface
    {
        return $this->setData('state', $state);
    }

    /**
     * @inheritDoc
     */
    public function getPostalCode(): ?string
    {
        return $this->getData('postal_code');
    }

    /**
     * @inheritDoc
     */
    public function setPostalCode(?string $postalCode): AddressInterface
    {
        return $this->setData('postal_code', $postalCode);
    }

    /**
     * @inheritDoc
     */
    public function getCountryCode(): ?string
    {
        return $this->getData('country_code');
    }

    /**
     * @inheritDoc
     */
    public function setCountryCode(?string $countryCode): AddressInterface
    {
        return $this->setData('country_code', $countryCode);
    }

    /**
     * @inheritDoc
     */
    public function getPhone(): ?string
    {
        return $this->getData('phone');
    }

    /**
     * @inheritDoc
     */
    public function setPhone(?string $phone): AddressInterface
    {
        return $this->setData('phone', $phone);
    }
}
