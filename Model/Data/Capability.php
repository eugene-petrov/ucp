<?php
/**
 * UCP Capability Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\CapabilityInterface;
use Magento\Framework\DataObject;

class Capability extends DataObject implements CapabilityInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return (string) $this->getData('name');
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): CapabilityInterface
    {
        return $this->setData('name', $name);
    }

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
    public function setVersion(string $version): CapabilityInterface
    {
        return $this->setData('version', $version);
    }
}
