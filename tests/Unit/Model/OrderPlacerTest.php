<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model;

use Aeqet\Ucp\Api\Data\OrderConfirmationInterfaceFactory;
use Aeqet\Ucp\Model\Checkout\OrderPlacer;
use Aeqet\Ucp\Model\Data\OrderConfirmation;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderPlacerTest extends TestCase
{
    private CartManagementInterface&MockObject $cartManagement;
    private OrderRepositoryInterface&MockObject $orderRepository;
    private OrderConfirmationInterfaceFactory&MockObject $confirmationFactory;
    private StoreManagerInterface&MockObject $storeManager;
    private OrderPlacer $placer;

    protected function setUp(): void
    {
        $this->cartManagement = $this->createMock(CartManagementInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->confirmationFactory = $this->createMock(OrderConfirmationInterfaceFactory::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->confirmationFactory->method('create')
            ->willReturnCallback(static fn(array $data = []) => new OrderConfirmation($data));

        $this->placer = new OrderPlacer(
            $this->cartManagement,
            $this->orderRepository,
            $this->confirmationFactory,
            $this->storeManager
        );
    }

    public function testPlaceReturnsConfirmationWithCorrectId(): void
    {
        $this->setupMocks(quoteId: 10, orderId: 55, incrementId: '000000055', entityId: 55);

        $confirmation = $this->placer->place(10);

        $this->assertSame('000000055', $confirmation->getId());
    }

    public function testPlaceReturnsConfirmationWithPermalinkUrl(): void
    {
        $this->setupMocks(quoteId: 10, orderId: 55, incrementId: '000000055', entityId: 55);

        $confirmation = $this->placer->place(10);

        $this->assertSame(
            'https://example.com/sales/order/view/order_id/55',
            $confirmation->getPermalinkUrl()
        );
    }

    public function testPlaceCallsPlaceOrderWithQuoteId(): void
    {
        $this->cartManagement->expects($this->once())
            ->method('placeOrder')
            ->with(7)
            ->willReturn(99);

        $this->setupOrderMocks(orderId: 99, incrementId: '000000099', entityId: 99);
        $this->setupStoreMock();

        $this->placer->place(7);
    }

    // ---- helpers ----

    private function setupMocks(int $quoteId, int $orderId, string $incrementId, int $entityId): void
    {
        $this->cartManagement->method('placeOrder')->with($quoteId)->willReturn($orderId);
        $this->setupOrderMocks($orderId, $incrementId, $entityId);
        $this->setupStoreMock();
    }

    private function setupOrderMocks(int $orderId, string $incrementId, int $entityId): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getIncrementId')->willReturn($incrementId);
        $order->method('getEntityId')->willReturn((string) $entityId);
        $this->orderRepository->method('get')->with($orderId)->willReturn($order);
    }

    private function setupStoreMock(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://example.com/');
        $this->storeManager->method('getStore')->willReturn($store);
    }
}
