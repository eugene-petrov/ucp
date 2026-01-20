<?php
/**
 * UCP Buyer Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface BuyerInterface
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
     * Get email
     *
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * Set email
     *
     * @param string|null $email
     * @return $this
     */
    public function setEmail(?string $email): self;

    /**
     * Get phone number
     *
     * @return string|null
     */
    public function getPhoneNumber(): ?string;

    /**
     * Set phone number
     *
     * @param string|null $phoneNumber
     * @return $this
     */
    public function setPhoneNumber(?string $phoneNumber): self;
}
