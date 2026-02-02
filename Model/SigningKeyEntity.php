<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Model\ResourceModel\SigningKey as SigningKeyResource;
use Magento\Framework\Model\AbstractModel;

/**
 * UCP Signing Key Entity Model
 *
 * Represents an ECDSA signing key used for webhook authentication.
 * Public keys are published in the UCP manifest for signature verification.
 */
class SigningKeyEntity extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'aeqet_ucp_signing_key';

    /**
     * @var string
     */
    protected $_eventObject = 'signing_key';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(SigningKeyResource::class);
    }

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        $entityId = $this->getData('entity_id');
        return $entityId !== null ? (int) $entityId : null;
    }

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId): self
    {
        return $this->setData('entity_id', $entityId);
    }

    /**
     * Get key ID (kid)
     *
     * @return string|null
     */
    public function getKid(): ?string
    {
        return $this->getData('kid');
    }

    /**
     * Set key ID (kid)
     *
     * @param string $kid
     * @return $this
     */
    public function setKid(string $kid): self
    {
        return $this->setData('kid', $kid);
    }

    /**
     * Get public key in JWK JSON format
     *
     * @return string|null
     */
    public function getPublicKeyJwk(): ?string
    {
        return $this->getData('public_key_jwk');
    }

    /**
     * Set public key in JWK JSON format
     *
     * @param string $jwk
     * @return $this
     */
    public function setPublicKeyJwk(string $jwk): self
    {
        return $this->setData('public_key_jwk', $jwk);
    }

    /**
     * Get private key in PEM format (encrypted)
     *
     * @return string|null
     */
    public function getPrivateKeyPem(): ?string
    {
        return $this->getData('private_key_pem');
    }

    /**
     * Set private key in PEM format
     *
     * @param string $pem
     * @return $this
     */
    public function setPrivateKeyPem(string $pem): self
    {
        return $this->setData('private_key_pem', $pem);
    }

    /**
     * Check if key is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Set key active status
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData('is_active', $isActive);
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get expiration date
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData('expires_at');
    }

    /**
     * Set expiration date
     *
     * @param string|null $expiresAt
     * @return $this
     */
    public function setExpiresAt(?string $expiresAt): self
    {
        return $this->setData('expires_at', $expiresAt);
    }
}
