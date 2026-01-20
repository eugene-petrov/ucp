<?php
/**
 * UCP Total Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\TotalInterface;
use Magento\Framework\DataObject;

class Total extends DataObject implements TotalInterface
{
    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return (string) $this->getData('type');
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type): TotalInterface
    {
        return $this->setData('type', $type);
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): int
    {
        return (int) $this->getData('amount');
    }

    /**
     * @inheritDoc
     */
    public function setAmount(int $amount): TotalInterface
    {
        return $this->setData('amount', $amount);
    }

    /**
     * @inheritDoc
     */
    public function getDisplayText(): ?string
    {
        return $this->getData('display_text');
    }

    /**
     * @inheritDoc
     */
    public function setDisplayText(?string $displayText): TotalInterface
    {
        return $this->setData('display_text', $displayText);
    }
}
