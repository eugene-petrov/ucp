<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Observer;

use Aeqet\Ucp\Model\Webhook\Dispatcher;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;

class SalesShipmentSaveAfterObserver implements ObserverInterface
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
            /** @var Shipment $shipment */
            $shipment = $observer->getEvent()->getShipment();
            if (!$shipment instanceof Shipment) {
                return;
            }
            $order = $shipment->getOrder();
            if (!$order instanceof Order) {
                return;
            }
            $this->dispatcher->dispatch('order.shipped', $order);
        } catch (Exception $e) {
            $this->logger->error('UCP observer error (order.shipped)', ['exception' => $e->getMessage()]);
        }
    }
}
