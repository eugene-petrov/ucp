<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\ResourceModel\WebhookDelivery;

use Aeqet\Ucp\Model\ResourceModel\WebhookDelivery as WebhookDeliveryResource;
use Aeqet\Ucp\Model\Webhook\DeliveryEntity;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'aeqet_ucp_webhook_delivery_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'webhook_delivery_collection';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(DeliveryEntity::class, WebhookDeliveryResource::class);
    }

    /**
     * Filter to deliveries that are pending and due for processing.
     *
     * @return $this
     */
    public function addDueForRetryFilter(): self
    {
        $this->getSelect()->where(
            "status = 'pending' AND (next_retry_at IS NULL OR next_retry_at <= NOW())"
        );
        return $this;
    }
}
