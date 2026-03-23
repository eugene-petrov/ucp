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
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class OrderPlacer
{
    /**
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderConfirmationInterfaceFactory $orderConfirmationFactory
     * @param StoreManagerInterface $storeManager
     * @param PaymentInterfaceFactory $paymentFactory
     */
    public function __construct(
        private readonly CartManagementInterface $cartManagement,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderConfirmationInterfaceFactory $orderConfirmationFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly PaymentInterfaceFactory $paymentFactory
    ) {
    }

    /**
     * Place an order for the given quote ID and return an OrderConfirmation.
     *
     * @param int $quoteId
     * @param string $paymentMethod
     * @return OrderConfirmationInterface
     * @throws LocalizedException
     */
    public function place(int $quoteId, string $paymentMethod): OrderConfirmationInterface
    {
        $payment = $this->paymentFactory->create();
        $payment->setMethod($paymentMethod);
        $orderId = $this->cartManagement->placeOrder($quoteId, $payment);
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
