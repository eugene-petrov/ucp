<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class WebhookDelivery extends AbstractDb
{
    public const TABLE_NAME = 'aeqet_ucp_webhook_delivery';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }
}
