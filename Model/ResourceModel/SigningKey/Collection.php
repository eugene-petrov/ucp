<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\ResourceModel\SigningKey;

use Aeqet\Ucp\Model\SigningKeyEntity;
use Aeqet\Ucp\Model\ResourceModel\SigningKey as SigningKeyResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zend_Db_Expr;

/**
 * UCP Signing Key Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'aeqet_ucp_signing_key_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'signing_key_collection';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(SigningKeyEntity::class, SigningKeyResource::class);
    }

    /**
     * Filter by active status
     *
     * @param bool $isActive
     * @return $this
     */
    public function addActiveFilter(bool $isActive = true): self
    {
        $this->addFieldToFilter('is_active', $isActive ? 1 : 0);
        return $this;
    }

    /**
     * Filter by non-expired keys
     *
     * @return $this
     */
    public function addNotExpiredFilter(): self
    {
        $this->addFieldToFilter(
            'expires_at',
            [
                ['null' => true],
                ['gteq' => new Zend_Db_Expr('NOW()')]
            ]
        );
        return $this;
    }
}
