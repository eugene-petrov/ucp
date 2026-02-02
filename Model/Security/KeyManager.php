<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Security;

use Aeqet\Ucp\Model\SigningKeyEntity;
use Aeqet\Ucp\Model\SigningKeyEntityFactory;
use Aeqet\Ucp\Model\ResourceModel\SigningKey as SigningKeyResource;
use Aeqet\Ucp\Model\ResourceModel\SigningKey\CollectionFactory as SigningKeyCollectionFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Manages ECDSA signing keys for UCP webhook authentication
 *
 * Handles key generation, storage, retrieval, and rotation for UCP compliance.
 * Keys are stored with encrypted private keys and JWK-formatted public keys.
 */
class KeyManager
{
    private const EC_CURVE = OPENSSL_KEYTYPE_EC;
    private const EC_CURVE_NAME = 'prime256v1'; // P-256

    /**
     * @param SigningKeyEntityFactory $signingKeyFactory
     * @param SigningKeyResource $signingKeyResource
     * @param SigningKeyCollectionFactory $collectionFactory
     * @param JwkGenerator $jwkGenerator
     * @param EncryptorInterface $encryptor
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SigningKeyEntityFactory $signingKeyFactory,
        private readonly SigningKeyResource $signingKeyResource,
        private readonly SigningKeyCollectionFactory $collectionFactory,
        private readonly JwkGenerator $jwkGenerator,
        private readonly EncryptorInterface $encryptor,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Generate a new ECDSA P-256 key pair and store it
     *
     * @param string|null $kid Optional key ID (auto-generated if null)
     * @param string|null $expiresAt Optional expiration date
     * @return SigningKeyEntity The created signing key entity
     * @throws LocalizedException
     */
    public function generateKey(?string $kid = null, ?string $expiresAt = null): SigningKeyEntity
    {
        $kid = $kid ?? $this->jwkGenerator->generateKid();
        if ($this->signingKeyResource->keyExistsByKid($kid)) {
            throw new LocalizedException(__('A key with ID "%1" already exists.', $kid));
        }
        $keyPair = $this->generateEcKeyPair();
        $publicKey = openssl_pkey_get_public($keyPair['public']);
        if ($publicKey === false) {
            throw new LocalizedException(__('Failed to extract public key.'));
        }

        $jwk = $this->jwkGenerator->publicKeyToJwk($publicKey, $kid);
        $jwkJson = $this->jwkGenerator->jwkToJson($jwk);

        $encryptedPrivateKey = $this->encryptor->encrypt($keyPair['private']);

        $signingKey = $this->signingKeyFactory->create();
        $signingKey->setKid($kid);
        $signingKey->setPublicKeyJwk($jwkJson);
        $signingKey->setPrivateKeyPem($encryptedPrivateKey);
        $signingKey->setIsActive(true);

        if ($expiresAt !== null) {
            $signingKey->setExpiresAt($expiresAt);
        }

        $this->signingKeyResource->save($signingKey);

        $this->logger->info(sprintf('Generated new UCP signing key: %s', $kid));

        return $signingKey;
    }

    /**
     * Get all active signing keys as JWK array (for manifest)
     *
     * @return array Array of JWK objects
     */
    public function getActivePublicKeysAsJwk(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter(true);

        $jwks = [];
        foreach ($collection as $key) {
            /** @var SigningKeyEntity $key */
            try {
                $jwk = $this->jwkGenerator->jsonToJwk($key->getPublicKeyJwk());
                if ($this->jwkGenerator->isValidUcpJwk($jwk)) {
                    $jwks[] = $jwk;
                }
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf('Invalid JWK for key %s: %s', $key->getKid(), $e->getMessage())
                );
            }
        }

        return $jwks;
    }

    /**
     * Get decrypted private key PEM by kid
     *
     * @param string $kid Key ID
     * @return string|null Decrypted PEM string or null if not found
     */
    public function getPrivateKeyPem(string $kid): ?string
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('kid', $kid);
        $collection->addActiveFilter(true);

        /** @var SigningKeyEntity|null $key */
        $key = $collection->getFirstItem();

        if (!$key || !$key->getEntityId()) {
            return null;
        }

        return $this->encryptor->decrypt($key->getPrivateKeyPem());
    }

    /**
     * Get the most recently created active key
     *
     * @return SigningKeyEntity|null
     */
    public function getCurrentKey(): ?SigningKeyEntity
    {
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter(true);
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(1);

        /** @var SigningKeyEntity|null $key */
        $key = $collection->getFirstItem();

        if (!$key || !$key->getEntityId()) {
            return null;
        }

        return $key;
    }

    /**
     * Deactivate a key (for rotation)
     *
     * @param string $kid Key ID to deactivate
     * @return bool True if key was deactivated
     */
    public function deactivateKey(string $kid): bool
    {
        $affected = $this->signingKeyResource->deactivateByKid($kid);

        if ($affected > 0) {
            $this->logger->info(sprintf('Deactivated UCP signing key: %s', $kid));
            return true;
        }

        return false;
    }

    /**
     * Delete a key permanently
     *
     * @param string $kid Key ID to delete
     * @return bool True if key was deleted
     */
    public function deleteKey(string $kid): bool
    {
        $affected = $this->signingKeyResource->deleteByKid($kid);

        if ($affected > 0) {
            $this->logger->info(sprintf('Deleted UCP signing key: %s', $kid));
            return true;
        }

        return false;
    }

    /**
     * Check if any active keys exist
     *
     * @return bool
     */
    public function hasActiveKeys(): bool
    {
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter(true);
        return $collection->getSize() > 0;
    }

    /**
     * Get count of active keys
     *
     * @return int
     */
    public function getActiveKeyCount(): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter(true);
        return $collection->getSize();
    }

    /**
     * Generate ECDSA P-256 key pair using OpenSSL
     *
     * @return array{private: string, public: string} PEM-encoded keys
     * @throws LocalizedException
     */
    private function generateEcKeyPair(): array
    {
        $config = [
            'private_key_type' => self::EC_CURVE,
            'curve_name' => self::EC_CURVE_NAME,
        ];

        $privateKey = openssl_pkey_new($config);

        if ($privateKey === false) {
            $error = openssl_error_string();
            throw new LocalizedException(
                __('Failed to generate EC key pair: %1', $error ?: 'Unknown error')
            );
        }

        $privateKeyPem = '';
        if (!openssl_pkey_export($privateKey, $privateKeyPem)) {
            throw new LocalizedException(__('Failed to export private key.'));
        }

        $keyDetails = openssl_pkey_get_details($privateKey);
        if ($keyDetails === false || !isset($keyDetails['key'])) {
            throw new LocalizedException(__('Failed to extract public key details.'));
        }

        return [
            'private' => $privateKeyPem,
            'public' => $keyDetails['key'],
        ];
    }
}
