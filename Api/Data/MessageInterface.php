<?php
/**
 * UCP Message Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface MessageInterface
{
    public const TYPE_INFO = 'info';
    public const CONTENT_TYPE_PLAIN = 'text/plain';

    /**
     * Get message type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set message type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * Get machine-readable code
     *
     * @return string|null
     */
    public function getCode(): ?string;

    /**
     * Set code
     *
     * @param string|null $code
     * @return $this
     */
    public function setCode(?string $code): self;

    /**
     * Get severity (for errors)
     *
     * @return string|null
     */
    public function getSeverity(): ?string;

    /**
     * Set severity
     *
     * @param string|null $severity
     * @return $this
     */
    public function setSeverity(?string $severity): self;

    /**
     * Get human-readable content
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Set content
     *
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): self;

    /**
     * Get JSONPath to field
     *
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * Set path
     *
     * @param string|null $path
     * @return $this
     */
    public function setPath(?string $path): self;

    /**
     * Get content type
     *
     * @return string|null
     */
    public function getContentType(): ?string;

    /**
     * Set content type
     *
     * @param string|null $contentType
     * @return $this
     */
    public function setContentType(?string $contentType): self;
}
