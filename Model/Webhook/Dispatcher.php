<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Webhook;

use Aeqet\Ucp\Api\Data\Webhook\DeliveryInterface;
use Aeqet\Ucp\Model\Capability\PlatformProfileFetcher;
use Aeqet\Ucp\Model\ResourceModel\CheckoutSession as CheckoutSessionResource;
use Aeqet\Ucp\Model\ResourceModel\WebhookDelivery as WebhookDeliveryResource;
use JsonException;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class Dispatcher
{
    /**
     * Tracks "quoteId:eventType" keys to prevent duplicate deliveries within a single request.
     *
     * @var array<string, true>
     */
    private array $dispatched = [];

    /**
     * @param CheckoutSessionResource $sessionResource
     * @param PlatformProfileFetcher $profileFetcher
     * @param DeliveryEntityFactory $deliveryEntityFactory
     * @param WebhookDeliveryResource $deliveryResource
     * @param EventBuilder $eventBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CheckoutSessionResource $sessionResource,
        private readonly PlatformProfileFetcher $profileFetcher,
        private readonly DeliveryEntityFactory $deliveryEntityFactory,
        private readonly WebhookDeliveryResource $deliveryResource,
        private readonly EventBuilder $eventBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Dispatch a webhook delivery for the given order event.
     *
     * @param string $eventType
     * @param Order $order
     * @return void
     */
    public function dispatch(string $eventType, Order $order): void
    {
        $quoteId = (int) $order->getQuoteId();
        if (!$quoteId) {
            return;
        }

        $key = $quoteId . ':' . $eventType;
        if (isset($this->dispatched[$key])) {
            return;
        }

        $sessionData = $this->sessionResource->getSessionDataByQuoteId($quoteId);
        if ($sessionData === null) {
            $this->logger->debug('UCP dispatch: no session for order', [
                'event_type' => $eventType,
                'order_id'   => $order->getIncrementId(),
                'quote_id'   => $quoteId,
            ]);
            return;
        }

        $profileUri = $sessionData['platform_profile_uri'] ?? null;
        if (!$profileUri) {
            $this->logger->debug('UCP dispatch: no platform_profile_uri for session', [
                'event_type' => $eventType,
                'session_id' => $sessionData['session_id'],
            ]);
            return;
        }

        $profile = $this->profileFetcher->fetchProfile($profileUri);
        if ($profile === null) {
            $this->logger->debug('UCP dispatch: failed to fetch platform profile', [
                'event_type'  => $eventType,
                'profile_uri' => $profileUri,
            ]);
            return;
        }

        $webhookUrl = null;
        foreach ($profile['ucp']['capabilities'] ?? [] as $cap) {
            if (($cap['name'] ?? '') === 'dev.ucp.shopping.order') {
                $url = $cap['config']['webhook_url'] ?? null;
                if ($url
                    && strncasecmp($url, 'https://', 8) === 0
                    && filter_var($url, FILTER_VALIDATE_URL)
                ) {
                    $webhookUrl = $url;
                }
                break;
            }
        }
        if (!$webhookUrl) {
            $this->logger->debug('UCP dispatch: no order webhook_url in platform profile', [
                'event_type'  => $eventType,
                'profile_uri' => $profileUri,
            ]);
            return;
        }

        try {
            $payload = $this->eventBuilder->buildOrderPayload($eventType, $order);
        } catch (JsonException $e) {
            $this->logger->error('UCP: failed to build order payload', ['error' => $e->getMessage()]);
            return;
        }

        $deliveryId = 'whdlv_' . bin2hex(random_bytes(16));

        $delivery = $this->deliveryEntityFactory->create();
        $delivery->setDeliveryId($deliveryId)
            ->setTargetUrl($webhookUrl)
            ->setEventType($eventType)
            ->setPayload($payload)
            ->setStatus(DeliveryInterface::STATUS_PENDING)
            ->setAttempts(0);

        $this->deliveryResource->save($delivery);
        $this->dispatched[$key] = true;

        $this->logger->debug('UCP webhook delivery queued', [
            'delivery_id' => $deliveryId,
            'event_type'  => $eventType,
            'target_url'  => $webhookUrl,
        ]);
    }
}
