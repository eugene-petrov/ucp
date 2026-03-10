<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Webhook;

use Aeqet\Ucp\Api\Data\Webhook\DeliveryInterface;
use Aeqet\Ucp\Api\Webhook\SignerInterface;
use Aeqet\Ucp\Model\ResourceModel\WebhookDelivery as WebhookDeliveryResource;
use Aeqet\Ucp\Model\ResourceModel\WebhookDelivery\CollectionFactory as WebhookDeliveryCollectionFactory;
use Exception;
use Magento\Framework\HTTP\ClientFactory;
use Psr\Log\LoggerInterface;

class DeliveryProcessor
{
    private const RETRY_DELAYS = [60, 300, 1800, 7200, 28800, 86400]; // 1m, 5m, 30m, 2h, 8h, 24h
    private const BATCH_SIZE = 50;

    /**
     * @param WebhookDeliveryCollectionFactory $deliveryCollectionFactory
     * @param WebhookDeliveryResource $deliveryResource
     * @param SignerInterface $signer
     * @param ClientFactory $clientFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly WebhookDeliveryCollectionFactory $deliveryCollectionFactory,
        private readonly WebhookDeliveryResource $deliveryResource,
        private readonly SignerInterface $signer,
        private readonly ClientFactory $clientFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process all pending webhook deliveries that are due for dispatch.
     *
     * @return void
     */
    public function processPending(): void
    {
        $collection = $this->deliveryCollectionFactory->create()
            ->addDueForRetryFilter();
        $collection->setPageSize(self::BATCH_SIZE);

        foreach ($collection as $delivery) {
            $this->processDelivery($delivery);
        }
    }

    /**
     * Attempt to deliver a single webhook and update its status.
     *
     * @param DeliveryEntity $delivery
     * @return void
     */
    public function processDelivery(DeliveryEntity $delivery): void
    {
        $deliveryId = (string) $delivery->getData('delivery_id');
        $url        = (string) $delivery->getData('target_url');
        $payload    = (string) $delivery->getData('payload');
        $attempts   = $delivery->getAttempts() + 1;
        $delivery->setAttempts($attempts);

        try {
            $signatureHeader = $this->signer->getSignatureHeader($payload);

            $client = $this->clientFactory->create();
            $client->setTimeout(10);
            $client->addHeader('Content-Type', 'application/json');
            $client->addHeader('X-Webhook-Signature', $signatureHeader);
            $client->addHeader('X-Delivery-Id', $deliveryId);
            $client->post($url, $payload);

            $responseStatus = $client->getStatus();
        } catch (Exception $e) {
            $this->scheduleRetryOrFail($delivery, $deliveryId, $attempts, $e->getMessage());
            return;
        }

        if ($responseStatus >= 200 && $responseStatus < 300) {
            $delivery->setStatus(DeliveryInterface::STATUS_DELIVERED);
            $delivery->setLastError(null);
            $delivery->setNextRetryAt(null);
            $this->deliveryResource->save($delivery);
            $this->logger->debug('UCP webhook delivery succeeded', [
                'delivery_id' => $deliveryId,
                'http_status' => $responseStatus,
            ]);
            return;
        }

        // 4xx client errors (except 429 Too Many Requests) are permanent failures — no retry
        if ($responseStatus >= 400 && $responseStatus < 500 && $responseStatus !== 429) {
            $delivery->setStatus(DeliveryInterface::STATUS_FAILED);
            $delivery->setLastError("HTTP $responseStatus (client error, no retry)");
            $this->deliveryResource->save($delivery);
            $this->logger->warning('UCP webhook delivery permanently failed (client error)', [
                'delivery_id' => $deliveryId,
                'http_status' => $responseStatus,
            ]);
            return;
        }

        $this->scheduleRetryOrFail($delivery, $deliveryId, $attempts, "HTTP $responseStatus");
    }

    /**
     * Schedule a retry or permanently fail the delivery depending on attempts count.
     *
     * @param DeliveryEntity $delivery
     * @param string $deliveryId
     * @param int $attempts
     * @param string $error
     * @return void
     */
    private function scheduleRetryOrFail(
        DeliveryEntity $delivery,
        string $deliveryId,
        int $attempts,
        string $error
    ): void {
        if ($attempts >= count(self::RETRY_DELAYS)) {
            $delivery->setStatus(DeliveryInterface::STATUS_FAILED);
            $delivery->setLastError($error);
            $this->deliveryResource->save($delivery);
            $this->logger->warning('UCP webhook delivery permanently failed', [
                'delivery_id' => $deliveryId,
                'attempts'    => $attempts,
                'error'       => $error,
            ]);
        } else {
            $delay = self::RETRY_DELAYS[$attempts - 1];
            $delivery->setNextRetryAt(gmdate('Y-m-d H:i:s', time() + $delay));
            $delivery->setLastError($error);
            $this->deliveryResource->save($delivery);
            $this->logger->info('UCP webhook delivery scheduled for retry', [
                'delivery_id'   => $deliveryId,
                'attempts'      => $attempts,
                'next_retry_in' => $delay,
                'error'         => $error,
            ]);
        }
    }
}
