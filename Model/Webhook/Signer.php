<?php
/**
 * UCP Webhook Signer — Detached JWT (RFC 7797)
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Webhook;

use Aeqet\Ucp\Api\Data\Webhook\SignatureResultInterface;
use Aeqet\Ucp\Api\Webhook\SignerInterface;
use Aeqet\Ucp\Model\Data\Webhook\SignatureResultFactory;
use Aeqet\Ucp\Model\Security\KeyManager;
use Magento\Framework\Exception\LocalizedException;

/**
 * Signs webhook payloads using Detached JWT (RFC 7797) with ECDSA P-256.
 *
 * Algorithm:
 *   header_b64  = base64url({"alg":"ES256","kid":"<kid>"})
 *   payload_b64 = base64url(raw_payload)
 *   signing_input = header_b64 + "." + payload_b64
 *   signature = ECDSA-P256-sign(private_key, signing_input)
 *   detached_jwt = header_b64 + ".." + base64url(DER_signature)
 *
 * Resulting header: Signature: sig1=:<detached_jwt>:
 */
class Signer implements SignerInterface
{
    /**
     * @param KeyManager $keyManager
     * @param SignatureResultFactory $signatureResultFactory
     */
    public function __construct(
        private readonly KeyManager $keyManager,
        private readonly SignatureResultFactory $signatureResultFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sign(string $payload): SignatureResultInterface
    {
        $currentKey = $this->keyManager->getCurrentKey();
        if ($currentKey === null) {
            throw new LocalizedException(
                __('No active UCP signing key found. Run bin/magento ucp:keys:generate to create one.')
            );
        }

        $kid = $currentKey->getKid();
        $privateKeyPem = $this->keyManager->decryptPrivateKey($currentKey);
        if ($privateKeyPem === '') {
            throw new LocalizedException(
                __('Failed to retrieve private key for kid "%1".', $kid)
            );
        }

        $headerJson = json_encode(['alg' => 'ES256', 'kid' => $kid], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $headerB64 = $this->base64UrlEncode($headerJson);
        $payloadB64 = $this->base64UrlEncode($payload);
        $signingInput = $headerB64 . '.' . $payloadB64;

        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if ($privateKey === false) {
            throw new LocalizedException(__('Failed to load private key for signing.'));
        }

        $derSignature = '';
        // OPENSSL_ALGO_SHA256 is correct for EC keys: OpenSSL detects the key type
        // and applies ECDSA-SHA256 (ES256), producing a DER-encoded ECDSA signature.
        if (!openssl_sign($signingInput, $derSignature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new LocalizedException(__('ECDSA signing failed: %1', openssl_error_string() ?: 'unknown error'));
        }

        $signatureB64 = $this->base64UrlEncode($derSignature);
        $detachedJwt = $headerB64 . '..' . $signatureB64;
        $headerValue = 'sig1=:' . $detachedJwt . ':';

        $result = $this->signatureResultFactory->create();
        $result->setKid($kid);
        $result->setSignature($detachedJwt);
        $result->setHeader($headerValue);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getSignatureHeader(string $payload): string
    {
        return $this->sign($payload)->getHeader();
    }

    /**
     * Encode binary data or string to base64url (RFC 4648)
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
