<?php
/**
 * UCP Checkout Session Collection
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\ResourceModel\CheckoutSession;

use Aeqet\Ucp\Model\CheckoutSessionEntity;
use Aeqet\Ucp\Model\ResourceModel\CheckoutSession as CheckoutSessionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'session_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'aeqet_ucp_checkout_session_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'checkout_session_collection';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(CheckoutSessionEntity::class, CheckoutSessionResource::class);
    }

    /**
     * Filter by status
     *
     * @param string|array $status
     * @return $this
     */
    public function addStatusFilter($status): self
    {
        if (is_array($status)) {
            $this->addFieldToFilter('status', ['in' => $status]);
        } else {
            $this->addFieldToFilter('status', $status);
        }
        return $this;
    }

    /**
     * Filter by quote ID
     *
     * @param int $quoteId
     * @return $this
     */
    public function addQuoteIdFilter(int $quoteId): self
    {
        $this->addFieldToFilter('quote_id', $quoteId);
        return $this;
    }

    /**
     * Filter by masked quote ID
     *
     * @param string $maskedQuoteId
     * @return $this
     */
    public function addMaskedQuoteIdFilter(string $maskedQuoteId): self
    {
        $this->addFieldToFilter('masked_quote_id', $maskedQuoteId);
        return $this;
    }

    /**
     * Filter sessions created before a timestamp
     *
     * @param string $timestamp
     * @return $this
     */
    public function addCreatedBeforeFilter(string $timestamp): self
    {
        $this->addFieldToFilter('created_at', ['lt' => $timestamp]);
        return $this;
    }
}
