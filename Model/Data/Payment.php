<?php
/**
 * UCP Payment Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;

class Payment extends DataObject implements PaymentInterface
{
    /**
     * @inheritDoc
     */
    public function getHandlers(): array
    {
        return $this->getData('handlers') ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setHandlers(array $handlers): PaymentInterface
    {
        return $this->setData('handlers', $handlers);
    }
}
