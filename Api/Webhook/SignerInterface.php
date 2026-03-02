<?php
/**
 * UCP Webhook Signer Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Webhook;

use Aeqet\Ucp\Api\Data\Webhook\SignatureResultInterface;
use Magento\Framework\Exception\LocalizedException;

interface SignerInterface
{
    /**
     * Sign a webhook payload using the current active ECDSA key (Detached JWT RFC 7797)
     *
     * @param string $payload Raw webhook payload to sign
     * @return SignatureResultInterface
     * @throws LocalizedException If no active key is found or signing fails
     */
    public function sign(string $payload): SignatureResultInterface;

    /**
     * Sign a payload and return the HTTP Signature header value
     *
     * @param string $payload Raw webhook payload to sign
     * @return string HTTP Signature header value (e.g. "sig1=:header..sig:")
     * @throws LocalizedException If no active key is found or signing fails
     */
    public function getSignatureHeader(string $payload): string;
}
