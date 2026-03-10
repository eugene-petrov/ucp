<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Webhook;

use Aeqet\Ucp\Model\Data\Webhook\SignatureResult;
use Aeqet\Ucp\Model\Data\Webhook\SignatureResultFactory;
use Aeqet\Ucp\Model\Security\KeyManager;
use Aeqet\Ucp\Model\Security\SigningKeyEntity;
use Aeqet\Ucp\Model\Webhook\Signer;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Webhook Signer (Detached JWT RFC 7797)
 *
 * Verifies ECDSA P-256 signing and signature correctness using public key.
 */
class SignerTest extends TestCase
{
    private KeyManager&MockObject $keyManager;
    private SignatureResultFactory&MockObject $signatureResultFactory;
    private Signer $signer;

    /** @var array{private: string, public: string} */
    private array $keyPair;
    private string $testKid = 'test_2026_abc123';

    protected function setUp(): void
    {
        $this->keyPair = $this->generateTestKeyPair();

        $this->keyManager = $this->createMock(KeyManager::class);

        $this->signatureResultFactory = $this->createMock(SignatureResultFactory::class);
        $this->signatureResultFactory->method('create')
            ->willReturnCallback(static fn(array $data = []) => new SignatureResult($data));

        $this->signer = new Signer($this->keyManager, $this->signatureResultFactory);
    }

    public function testSignReturnsCorrectKid(): void
    {
        $this->setupKeyManagerMocks();

        $result = $this->signer->sign('{"hello":"world"}');

        $this->assertSame($this->testKid, $result->getKid());
    }

    public function testSignProducesDetachedJwtFormat(): void
    {
        $this->setupKeyManagerMocks();

        $result = $this->signer->sign('{"hello":"world"}');
        $detachedJwt = $result->getSignature();

        $parts = explode('.', $detachedJwt);
        $this->assertCount(3, $parts, 'Detached JWT must have exactly 3 dot-separated parts');
        $this->assertNotEmpty($parts[0], 'Header must not be empty');
        $this->assertEmpty($parts[1], 'Payload section must be empty in detached JWT');
        $this->assertNotEmpty($parts[2], 'Signature must not be empty');
    }

    public function testSignHeaderContainsCorrectAlgAndKid(): void
    {
        $this->setupKeyManagerMocks();

        $result = $this->signer->sign('{"hello":"world"}');
        $detachedJwt = $result->getSignature();
        $parts = explode('.', $detachedJwt);

        $headerJson = $this->base64UrlDecode($parts[0]);
        $header = json_decode($headerJson, true);

        $this->assertSame('ES256', $header['alg']);
        $this->assertSame($this->testKid, $header['kid']);
    }

    public function testSignatureIsVerifiableWithPublicKey(): void
    {
        $this->setupKeyManagerMocks();

        $payload = '{"order_id":"123","event":"order.created"}';
        $result = $this->signer->sign($payload);
        $detachedJwt = $result->getSignature();
        $parts = explode('.', $detachedJwt);

        $headerB64 = $parts[0];
        $signatureB64 = $parts[2];

        $payloadB64 = $this->base64UrlEncode($payload);
        $signingInput = $headerB64 . '.' . $payloadB64;

        $publicKey = openssl_pkey_get_public($this->keyPair['public']);
        $this->assertNotFalse($publicKey, 'Must be able to load test public key');

        $derSignature = $this->base64UrlDecode($signatureB64);
        $verifyResult = openssl_verify($signingInput, $derSignature, $publicKey, OPENSSL_ALGO_SHA256);

        $this->assertSame(1, $verifyResult, 'Signature must verify successfully with the public key');
    }

    public function testGetSignatureHeaderReturnsCorrectFormat(): void
    {
        $this->setupKeyManagerMocks();

        $headerValue = $this->signer->getSignatureHeader('test payload');

        $this->assertStringStartsWith('sig1=:', $headerValue);
        $this->assertStringEndsWith(':', $headerValue);
        $inner = substr($headerValue, 6, -1);
        $parts = explode('.', $inner);
        $this->assertCount(3, $parts);
    }

    public function testSignThrowsWhenNoActiveKey(): void
    {
        $this->keyManager->method('getCurrentKey')->willReturn(null);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/No active UCP signing key/');

        $this->signer->sign('test');
    }

    public function testSignThrowsWhenPrivateKeyNotFound(): void
    {
        $entity = $this->createMock(SigningKeyEntity::class);
        $entity->method('getKid')->willReturn($this->testKid);

        $this->keyManager->method('getCurrentKey')->willReturn($entity);
        $this->keyManager->method('decryptPrivateKey')->willReturn('');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/Failed to retrieve private key/');

        $this->signer->sign('test');
    }

    // ----- helpers -----

    private function setupKeyManagerMocks(): void
    {
        $entity = $this->createMock(SigningKeyEntity::class);
        $entity->method('getKid')->willReturn($this->testKid);

        $this->keyManager->method('getCurrentKey')->willReturn($entity);
        $this->keyManager->method('decryptPrivateKey')
            ->with($entity)
            ->willReturn($this->keyPair['private']);
    }

    /** @return array{private: string, public: string} */
    private function generateTestKeyPair(): array
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        $this->assertNotFalse($key, 'OpenSSL must support EC P-256 key generation');

        $privateKeyPem = '';
        openssl_pkey_export($key, $privateKeyPem);

        $details = openssl_pkey_get_details($key);
        return ['private' => $privateKeyPem, 'public' => $details['key']];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) + (4 - strlen($data) % 4) % 4, '=');
        return (string) base64_decode($padded, true);
    }
}
