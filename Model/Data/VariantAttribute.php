<?php
/**
 * UCP Variant Attribute Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\VariantAttributeInterface;
use Magento\Framework\DataObject;

class VariantAttribute extends DataObject implements VariantAttributeInterface
{
    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return (string) $this->getData('code');
    }

    /**
     * @inheritDoc
     */
    public function setCode(string $code): VariantAttributeInterface
    {
        return $this->setData('code', $code);
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return (string) $this->getData('value');
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): VariantAttributeInterface
    {
        return $this->setData('value', $value);
    }
}
