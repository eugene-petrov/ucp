<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Webhook;

use Magento\Sales\Model\Order;

class EventBuilder
{
    /**
     * Build a JSON payload for an order event.
     *
     * @param string $eventType
     * @param Order $order
     * @return string
     */
    public function buildOrderPayload(string $eventType, Order $order): string
    {
        $payload = [
            'event_id'   => 'evt_' . bin2hex(random_bytes(16)),
            'event_type' => $eventType,
            'timestamp'  => gmdate('Y-m-d\TH:i:s\Z'),
            'order'      => [
                'id'          => $order->getIncrementId(),
                'status'      => $order->getStatus(),
                'grand_total' => $order->getGrandTotal(),
                'currency'    => $order->getOrderCurrencyCode(),
            ],
        ];

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
