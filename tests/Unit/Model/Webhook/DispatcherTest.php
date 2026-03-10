<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Webhook;

use Aeqet\Ucp\Api\Data\Webhook\DeliveryInterface;
use Aeqet\Ucp\Model\Capability\PlatformProfileFetcher;
use Aeqet\Ucp\Model\ResourceModel\CheckoutSession as CheckoutSessionResource;
use Aeqet\Ucp\Model\ResourceModel\WebhookDelivery as WebhookDeliveryResource;
use Aeqet\Ucp\Model\Webhook\DeliveryEntity;
use Aeqet\Ucp\Model\Webhook\DeliveryEntityFactory;
use Aeqet\Ucp\Model\Webhook\Dispatcher;
use Aeqet\Ucp\Model\Webhook\EventBuilder;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DispatcherTest extends TestCase
{
    private CheckoutSessionResource&MockObject $sessionResource;

    private PlatformProfileFetcher&MockObject $profileFetcher;

    private DeliveryEntityFactory&MockObject $deliveryEntityFactory;

    private WebhookDeliveryResource&MockObject $deliveryResource;

    private EventBuilder&MockObject $eventBuilder;

    private LoggerInterface&MockObject $logger;

    private Dispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->sessionResource = $this->createMock(CheckoutSessionResource::class);
        $this->profileFetcher = $this->createMock(PlatformProfileFetcher::class);
        $this->deliveryEntityFactory = $this->createMock(DeliveryEntityFactory::class);
        $this->deliveryResource = $this->createMock(WebhookDeliveryResource::class);
        $this->eventBuilder = $this->createMock(EventBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->dispatcher = new Dispatcher(
            $this->sessionResource,
            $this->profileFetcher,
            $this->deliveryEntityFactory,
            $this->deliveryResource,
            $this->eventBuilder,
            $this->logger
        );
    }

    public function testDispatchDoesNothingWhenOrderHasNoQuoteId(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getQuoteId')->willReturn(0);

        $this->sessionResource->expects($this->never())->method('getSessionDataByQuoteId');
        $this->deliveryResource->expects($this->never())->method('save');

        $this->dispatcher->dispatch('order.created', $order);
    }

    public function testDispatchLogsAndReturnsWhenNoSessionFound(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getQuoteId')->willReturn(42);

        $this->sessionResource->method('getSessionDataByQuoteId')->with(42)->willReturn(null);
        $this->logger->expects($this->once())->method('debug')
            ->with('UCP dispatch: no session for order', $this->arrayHasKey('quote_id'));

        $this->deliveryResource->expects($this->never())->method('save');

        $this->dispatcher->dispatch('order.created', $order);
    }

    public function testDispatchLogsAndReturnsWhenNoPlatformProfileUri(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getQuoteId')->willReturn(42);

        $this->sessionResource->method('getSessionDataByQuoteId')->willReturn([
            'session_id' => 'sess_abc',
            'platform_profile_uri' => null,
        ]);
        $this->logger->expects($this->once())->method('debug')
            ->with('UCP dispatch: no platform_profile_uri for session', $this->arrayHasKey('session_id'));

        $this->deliveryResource->expects($this->never())->method('save');

        $this->dispatcher->dispatch('order.created', $order);
    }

    public function testDispatchLogsAndReturnsWhenProfileHasNoOrderCapability(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getQuoteId')->willReturn(42);

        $this->sessionResource->method('getSessionDataByQuoteId')->willReturn([
            'session_id' => 'sess_abc',
            'platform_profile_uri' => 'https://platform.example.com/.well-known/ucp',
        ]);

        $profileWithoutOrderCap = [
            'ucp' => [
                'capabilities' => [
                    ['name' => 'dev.ucp.shopping.checkout', 'version' => '2026-01-11', 'config' => []],
                ],
            ],
        ];
        $this->profileFetcher->method('fetchProfile')->willReturn($profileWithoutOrderCap);

        $this->logger->expects($this->once())->method('debug')
            ->with('UCP dispatch: no order webhook_url in platform profile', $this->arrayHasKey('profile_uri'));

        $this->deliveryResource->expects($this->never())->method('save');

        $this->dispatcher->dispatch('order.created', $order);
    }

    /**
     * #2 / #4 — fetchProfile returning null must be handled gracefully with a debug log.
     */
    public function testDispatchHandlesNullProfile(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getQuoteId')->willReturn(42);

        $this->sessionResource->method('getSessionDataByQuoteId')->willReturn([
            'session_id' => 'sess_abc',
            'platform_profile_uri' => 'https://platform.example.com/.well-known/ucp',
        ]);
        $this->profileFetcher->method('fetchProfile')->willReturn(null);

        $this->logger->expects($this->once())->method('debug')
            ->with('UCP dispatch: failed to fetch platform profile', $this->arrayHasKey('profile_uri'));

        $this->deliveryResource->expects($this->never())->method('save');

        $this->dispatcher->dispatch('order.created', $order);
    }

    /**
     * #1 — webhook_url with http:// (non-HTTPS) must be rejected.
     */
    public function testDispatchRejectsNonHttpsWebhookUrl(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getQuoteId')->willReturn(42);

        $this->sessionResource->method('getSessionDataByQuoteId')->willReturn([
            'session_id' => 'sess_abc',
            'platform_profile_uri' => 'https://platform.example.com/.well-known/ucp',
        ]);

        $this->profileFetcher->method('fetchProfile')->willReturn([
            'ucp' => [
                'capabilities' => [
                    [
                        'name' => 'dev.ucp.shopping.order',
                        'version' => '2026-01-11',
                        'config' => ['webhook_url' => 'http://platform.example.com/webhooks'],
                    ],
                ],
            ],
        ]);

        $this->logger->expects($this->once())->method('debug')
            ->with('UCP dispatch: no order webhook_url in platform profile', $this->arrayHasKey('profile_uri'));

        $this->deliveryResource->expects($this->never())->method('save');

        $this->dispatcher->dispatch('order.created', $order);
    }

    /**
     * #3 — calling dispatch twice for the same order+eventType must only save one delivery.
     */
    public function testDispatchSkipsDuplicateDispatch(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getQuoteId')->willReturn(42);
        $order->method('getIncrementId')->willReturn('000000001');

        $this->sessionResource->method('getSessionDataByQuoteId')->willReturn([
            'session_id' => 'sess_abc',
            'platform_profile_uri' => 'https://platform.example.com/.well-known/ucp',
        ]);

        $profile = [
            'ucp' => [
                'capabilities' => [
                    [
                        'name' => 'dev.ucp.shopping.order',
                        'version' => '2026-01-11',
                        'config' => ['webhook_url' => 'https://platform.example.com/webhooks/ucp/orders'],
                    ],
                ],
            ],
        ];
        $this->profileFetcher->method('fetchProfile')->willReturn($profile);
        $this->eventBuilder->method('buildOrderPayload')->willReturn('{"event_type":"order.created"}');

        $delivery = $this->createMock(DeliveryEntity::class);
        $delivery->method('setDeliveryId')->willReturnSelf();
        $delivery->method('setTargetUrl')->willReturnSelf();
        $delivery->method('setEventType')->willReturnSelf();
        $delivery->method('setPayload')->willReturnSelf();
        $delivery->method('setStatus')->willReturnSelf();
        $delivery->method('setAttempts')->willReturnSelf();
        $this->deliveryEntityFactory->method('create')->willReturn($delivery);

        // Only one save despite two dispatch calls
        $this->deliveryResource->expects($this->once())->method('save');

        $this->dispatcher->dispatch('order.created', $order);
        $this->dispatcher->dispatch('order.created', $order);
    }

    public function testDispatchQueuesDeliveryOnSuccess(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getQuoteId')->willReturn(42);
        $order->method('getIncrementId')->willReturn('000000001');

        $this->sessionResource->method('getSessionDataByQuoteId')->willReturn([
            'session_id' => 'sess_abc',
            'platform_profile_uri' => 'https://platform.example.com/.well-known/ucp',
        ]);

        $profile = [
            'ucp' => [
                'capabilities' => [
                    [
                        'name' => 'dev.ucp.shopping.order',
                        'version' => '2026-01-11',
                        'config' => ['webhook_url' => 'https://platform.example.com/webhooks/ucp/orders'],
                    ],
                ],
            ],
        ];
        $this->profileFetcher->method('fetchProfile')->willReturn($profile);
        $this->eventBuilder->method('buildOrderPayload')->willReturn('{"event_type":"order.created"}');

        $delivery = $this->createMock(DeliveryEntity::class);
        $delivery->method('setDeliveryId')->willReturnSelf();
        $delivery->method('setTargetUrl')->willReturnSelf();
        $delivery->method('setEventType')->willReturnSelf();
        $delivery->method('setPayload')->willReturnSelf();
        $delivery->method('setStatus')->willReturnSelf();
        $delivery->method('setAttempts')->willReturnSelf();
        $this->deliveryEntityFactory->method('create')->willReturn($delivery);

        $delivery->expects($this->once())->method('setTargetUrl')
            ->with('https://platform.example.com/webhooks/ucp/orders')->willReturnSelf();
        $delivery->expects($this->once())->method('setStatus')
            ->with(DeliveryInterface::STATUS_PENDING)->willReturnSelf();

        $this->deliveryResource->expects($this->once())->method('save')->with($delivery);

        $this->dispatcher->dispatch('order.created', $order);
    }
}
