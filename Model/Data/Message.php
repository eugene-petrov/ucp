<?php
/**
 * UCP Message Data Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\MessageInterface;

class Message implements MessageInterface
{
    /**
     * @var string
     */
    private string $type = self::TYPE_INFO;

    /**
     * @var string|null
     */
    private ?string $code = null;

    /**
     * @var string|null
     */
    private ?string $severity = null;

    /**
     * @var string
     */
    private string $content = '';

    /**
     * @var string|null
     */
    private ?string $path = null;

    /**
     * @var string|null
     */
    private ?string $contentType = self::CONTENT_TYPE_PLAIN;

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type): MessageInterface
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function setCode(?string $code): MessageInterface
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    /**
     * @inheritDoc
     */
    public function setSeverity(?string $severity): MessageInterface
    {
        $this->severity = $severity;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @inheritDoc
     */
    public function setContent(string $content): MessageInterface
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function setPath(?string $path): MessageInterface
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @inheritDoc
     */
    public function setContentType(?string $contentType): MessageInterface
    {
        $this->contentType = $contentType;
        return $this;
    }
}
