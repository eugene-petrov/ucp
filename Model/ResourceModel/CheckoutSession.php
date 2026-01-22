<?php
/**
 * UCP Checkout Session ResourceModel
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class CheckoutSession extends AbstractDb
{
    /**
     * Table name
     */
    public const TABLE_NAME = 'aeqet_ucp_checkout_session';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }

    /**
     * Check if session exists by session ID
     *
     * @param string $sessionId
     * @return bool
     */
    public function sessionExists(string $sessionId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['session_id'])
            ->where('session_id = ?', $sessionId)
            ->limit(1);

        return (bool) $connection->fetchOne($select);
    }

    /**
     * Delete by session ID
     *
     * @param string $sessionId
     * @return int Number of affected rows
     */
    public function deleteBySessionId(string $sessionId): int
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            ['session_id = ?' => $sessionId]
        );
    }

    /**
     * Get session ID by masked quote ID
     *
     * @param string $maskedQuoteId
     * @return string|null
     */
    public function getSessionIdByMaskedQuoteId(string $maskedQuoteId): ?string
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['session_id'])
            ->where('masked_quote_id = ?', $maskedQuoteId);

        $result = $connection->fetchOne($select);
        return $result ?: null;
    }
}
