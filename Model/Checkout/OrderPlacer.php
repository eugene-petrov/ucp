<?php
/**
 * UCP Order Placer — places a Magento order and returns an OrderConfirmation
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Checkout;

use Aeqet\Ucp\Api\Data\OrderConfirmationInterface;
use Aeqet\Ucp\Api\Data\OrderConfirmationInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class OrderPlacer
{
    /**
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderConfirmationInterfaceFactory $orderConfirmationFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CartManagementInterface $cartManagement,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderConfirmationInterfaceFactory $orderConfirmationFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Place an order for the given quote ID and return an OrderConfirmation.
     *
     * @param int $quoteId
     * @return OrderConfirmationInterface
     * @throws LocalizedException
     */
    public function place(int $quoteId): OrderConfirmationInterface
    {
        $orderId = $this->cartManagement->placeOrder($quoteId);
        $order = $this->orderRepository->get($orderId);

        $confirmation = $this->orderConfirmationFactory->create();
        $confirmation->setId($order->getIncrementId());

        $baseUrl = rtrim($this->storeManager->getStore()->getBaseUrl(), '/');
        $confirmation->setPermalinkUrl(
            $baseUrl . '/sales/order/view/order_id/' . $order->getEntityId()
        );

        return $confirmation;
    }
}
