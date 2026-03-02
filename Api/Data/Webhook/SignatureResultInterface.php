<?php
/**
 * UCP Webhook Signature Result Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data\Webhook;

interface SignatureResultInterface
{
    /**
     * Get the key ID used for signing
     *
     * @return string
     */
    public function getKid(): string;

    /**
     * Set the key ID used for signing
     *
     * @param string $kid
     * @return $this
     */
    public function setKid(string $kid): self;

    /**
     * Get the detached JWT signature token (header..signature)
     *
     * @return string
     */
    public function getSignature(): string;

    /**
     * Set the detached JWT signature token
     *
     * @param string $signature
     * @return $this
     */
    public function setSignature(string $signature): self;

    /**
     * Get the HTTP Signature header value
     *
     * @return string
     */
    public function getHeader(): string;

    /**
     * Set the HTTP Signature header value
     *
     * @param string $header
     * @return $this
     */
    public function setHeader(string $header): self;
}
