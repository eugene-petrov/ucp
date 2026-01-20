<?php
/**
 * UCP Meta Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\UcpMetaInterface;
use Magento\Framework\DataObject;

class UcpMeta extends DataObject implements UcpMetaInterface
{
    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return (string) $this->getData('version');
    }

    /**
     * @inheritDoc
     */
    public function setVersion(string $version): UcpMetaInterface
    {
        return $this->setData('version', $version);
    }

    /**
     * @inheritDoc
     */
    public function getCapabilities(): array
    {
        return $this->getData('capabilities') ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setCapabilities(array $capabilities): UcpMetaInterface
    {
        return $this->setData('capabilities', $capabilities);
    }
}
