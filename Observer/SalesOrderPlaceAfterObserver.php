<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Observer;

use Aeqet\Ucp\Model\Webhook\Dispatcher;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class SalesOrderPlaceAfterObserver implements ObserverInterface
{
    /**
     * @param Dispatcher $dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();
            if (!$order instanceof Order) {
                return;
            }
            $this->dispatcher->dispatch('order.created', $order);
        } catch (Exception $e) {
            $this->logger->error('UCP observer error (order.created)', ['exception' => $e->getMessage()]);
        }
    }
}
