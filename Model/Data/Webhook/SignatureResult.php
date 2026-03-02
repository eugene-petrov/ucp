<?php
/**
 * UCP Webhook Signature Result Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data\Webhook;

use Aeqet\Ucp\Api\Data\Webhook\SignatureResultInterface;
use Magento\Framework\DataObject;

class SignatureResult extends DataObject implements SignatureResultInterface
{
    /**
     * @inheritDoc
     */
    public function getKid(): string
    {
        return (string) $this->getData('kid');
    }

    /**
     * @inheritDoc
     */
    public function setKid(string $kid): SignatureResultInterface
    {
        return $this->setData('kid', $kid);
    }

    /**
     * @inheritDoc
     */
    public function getSignature(): string
    {
        return (string) $this->getData('signature');
    }

    /**
     * @inheritDoc
     */
    public function setSignature(string $signature): SignatureResultInterface
    {
        return $this->setData('signature', $signature);
    }

    /**
     * @inheritDoc
     */
    public function getHeader(): string
    {
        return (string) $this->getData('header');
    }

    /**
     * @inheritDoc
     */
    public function setHeader(string $header): SignatureResultInterface
    {
        return $this->setData('header', $header);
    }
}
