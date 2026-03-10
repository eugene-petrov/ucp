<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Cron;

use Aeqet\Ucp\Model\Webhook\DeliveryProcessor;
use Exception;
use Psr\Log\LoggerInterface;

class ProcessWebhookDeliveries
{
    /**
     * @param DeliveryProcessor $processor
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly DeliveryProcessor $processor,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Execute the cron job to process pending webhook deliveries.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $this->processor->processPending();
        } catch (Exception $e) {
            $this->logger->error('UCP webhook cron failed', ['exception' => $e->getMessage()]);
        }
    }
}
