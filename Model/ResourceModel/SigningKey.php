<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * UCP Signing Key ResourceModel
 */
class SigningKey extends AbstractDb
{
    /**
     * Table name
     */
    public const TABLE_NAME = 'aeqet_ucp_signing_key';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'entity_id');
    }

    /**
     * Check if key exists by kid
     *
     * @param string $kid
     * @return bool
     */
    public function keyExistsByKid(string $kid): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['kid'])
            ->where('kid = ?', $kid)
            ->limit(1);

        return (bool) $connection->fetchOne($select);
    }

    /**
     * Get all active keys
     *
     * @return array
     */
    public function getActiveKeys(): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('is_active = ?', 1)
            ->order('created_at DESC');

        return $connection->fetchAll($select);
    }

    /**
     * Deactivate key by kid
     *
     * @param string $kid
     * @return int Number of affected rows
     */
    public function deactivateByKid(string $kid): int
    {
        return $this->getConnection()->update(
            $this->getMainTable(),
            ['is_active' => 0],
            ['kid = ?' => $kid]
        );
    }

    /**
     * Delete key by kid
     *
     * @param string $kid
     * @return int Number of affected rows
     */
    public function deleteByKid(string $kid): int
    {
        return $this->getConnection()->delete(
            $this->getMainTable(),
            ['kid = ?' => $kid]
        );
    }
}
