<?php
/**
 * UCP Payment Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface PaymentInterface
{
    /**
     * Get payment handlers
     *
     * @return \Aeqet\Ucp\Api\Data\PaymentHandlerInterface[]
     */
    public function getHandlers(): array;

    /**
     * Set payment handlers
     *
     * @param \Aeqet\Ucp\Api\Data\PaymentHandlerInterface[] $handlers
     * @return $this
     */
    public function setHandlers(array $handlers): self;
}
