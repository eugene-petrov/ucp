<?php
/**
 * UCP Address Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface AddressInterface
{
    /**
     * Get first name
     *
     * @return string|null
     */
    public function getFirstName(): ?string;

    /**
     * Set first name
     *
     * @param string|null $firstName
     * @return $this
     */
    public function setFirstName(?string $firstName): self;

    /**
     * Get last name
     *
     * @return string|null
     */
    public function getLastName(): ?string;

    /**
     * Set last name
     *
     * @param string|null $lastName
     * @return $this
     */
    public function setLastName(?string $lastName): self;

    /**
     * Get street line 1
     *
     * @return string|null
     */
    public function getStreetLine1(): ?string;

    /**
     * Set street line 1
     *
     * @param string|null $streetLine1
     * @return $this
     */
    public function setStreetLine1(?string $streetLine1): self;

    /**
     * Get street line 2
     *
     * @return string|null
     */
    public function getStreetLine2(): ?string;

    /**
     * Set street line 2
     *
     * @param string|null $streetLine2
     * @return $this
     */
    public function setStreetLine2(?string $streetLine2): self;

    /**
     * Get city
     *
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * Set city
     *
     * @param string|null $city
     * @return $this
     */
    public function setCity(?string $city): self;

    /**
     * Get state or region
     *
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * Set state or region
     *
     * @param string|null $state
     * @return $this
     */
    public function setState(?string $state): self;

    /**
     * Get postal code
     *
     * @return string|null
     */
    public function getPostalCode(): ?string;

    /**
     * Set postal code
     *
     * @param string|null $postalCode
     * @return $this
     */
    public function setPostalCode(?string $postalCode): self;

    /**
     * Get ISO 3166-1 alpha-2 country code (e.g. "US")
     *
     * @return string|null
     */
    public function getCountryCode(): ?string;

    /**
     * Set ISO 3166-1 alpha-2 country code
     *
     * @param string|null $countryCode
     * @return $this
     */
    public function setCountryCode(?string $countryCode): self;

    /**
     * Get phone number
     *
     * @return string|null
     */
    public function getPhone(): ?string;

    /**
     * Set phone number
     *
     * @param string|null $phone
     * @return $this
     */
    public function setPhone(?string $phone): self;
}
