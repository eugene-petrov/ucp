<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Security;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use OpenSSLAsymmetricKey;

/**
 * Generates JWK (JSON Web Key) from OpenSSL EC keys
 *
 * Converts ECDSA P-256 keys to JWK format as required by UCP specification
 * for webhook signature verification.
 */
class JwkGenerator
{
    private const CURVE = 'P-256';
    private const ALGORITHM = 'ES256';
    private const KEY_TYPE = 'EC';
    private const USE = 'sig';

    /**
     * @param Json $jsonSerializer
     */
    public function __construct(
        private readonly Json $jsonSerializer
    ) {
    }

    /**
     * Generate a unique key ID
     *
     * @param string|null $prefix Optional prefix for the kid
     * @return string
     */
    public function generateKid(?string $prefix = null): string
    {
        $timestamp = date('Y');
        $random = bin2hex(random_bytes(4));

        if ($prefix) {
            return sprintf('%s_%s_%s', $prefix, $timestamp, $random);
        }

        return sprintf('ucp_%s_%s', $timestamp, $random);
    }

    /**
     * Convert an OpenSSL EC public key to JWK format
     *
     * @param OpenSSLAsymmetricKey $publicKey OpenSSL public key resource
     * @param string $kid Key ID for the JWK
     * @return array JWK representation of the public key
     * @throws LocalizedException
     */
    public function publicKeyToJwk(OpenSSLAsymmetricKey $publicKey, string $kid): array
    {
        $keyDetails = openssl_pkey_get_details($publicKey);

        if ($keyDetails === false) {
            throw new LocalizedException(__('Failed to get key details from OpenSSL key.'));
        }

        if (!isset($keyDetails['ec'])) {
            throw new LocalizedException(__('The provided key is not an EC key.'));
        }

        $ec = $keyDetails['ec'];

        if (!isset($ec['x']) || !isset($ec['y'])) {
            throw new LocalizedException(__('EC key is missing x or y coordinates.'));
        }

        return [
            'kid' => $kid,
            'kty' => self::KEY_TYPE,
            'crv' => self::CURVE,
            'x' => $this->base64UrlEncode($ec['x']),
            'y' => $this->base64UrlEncode($ec['y']),
            'use' => self::USE,
            'alg' => self::ALGORITHM,
        ];
    }

    /**
     * Convert JWK array to JSON string
     *
     * @param array $jwk
     * @return string
     */
    public function jwkToJson(array $jwk): string
    {
        return $this->jsonSerializer->serialize($jwk);
    }

    /**
     * Convert JWK array to pretty-printed JSON string for display
     *
     * @param array $jwk
     * @return string
     */
    public function jwkToPrettyJson(array $jwk): string
    {
        return (string) json_encode($jwk, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Parse JWK JSON string to array
     *
     * @param string $json
     * @return array
     * @throws LocalizedException
     */
    public function jsonToJwk(string $json): array
    {
        try {
            $jwk = $this->jsonSerializer->unserialize($json);
        } catch (\InvalidArgumentException $e) {
            throw new LocalizedException(__('Failed to parse JWK JSON: %1', $e->getMessage()));
        }

        return $jwk;
    }

    /**
     * Validate that a JWK has all required fields for UCP
     *
     * @param array $jwk
     * @return bool
     */
    public function isValidUcpJwk(array $jwk): bool
    {
        $requiredFields = ['kid', 'kty', 'crv', 'x', 'y', 'use', 'alg'];

        foreach ($requiredFields as $field) {
            if (!isset($jwk[$field])) {
                return false;
            }
        }

        return $jwk['kty'] === self::KEY_TYPE
            && $jwk['crv'] === self::CURVE
            && $jwk['alg'] === self::ALGORITHM
            && $jwk['use'] === self::USE;
    }

    /**
     * Encode binary data to base64url format (RFC 4648)
     *
     * @param string $data Binary data to encode
     * @return string Base64url encoded string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
