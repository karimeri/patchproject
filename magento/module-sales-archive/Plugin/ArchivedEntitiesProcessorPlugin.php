<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Plugin;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Provider\UpdatedIdListProvider;
use Magento\SalesArchive\Model\ResourceModel\Archive\TableMapper;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Plugin is needed as the fix to problem when archived orders
 * are restored in Orders Grid because mechanism that updates
 * orders in the grid does not take into account archived data in the DB
 */
class ArchivedEntitiesProcessorPlugin
{
    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /** @var TableMapper */

    private $tableMapper;

    /**
     * ArchivedOrdersProcessorPlugin constructor.
     * @param ResourceConnection $resourceConnection
     * @param TableMapper $tableMapper
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TableMapper $tableMapper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->tableMapper = $tableMapper;
    }

    /**
     * Gets ids from archive table matches them to provided ids and returns their diff.
     *
     * @param UpdatedIdListProvider $provider
     * @param array $result
     * @param string $mainTableName
     * @param string $gridTableName
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIds(
        UpdatedIdListProvider $provider,
        array $result,
        string $mainTableName,
        string $gridTableName
    ) : array {
        $archiveTable = $this->tableMapper->getArchiveEntityTableBySourceTable($gridTableName);
        if (!empty($result) && $archiveTable !== null) {
            $select = $this->getConnection()->select()
                ->from($archiveTable)
                ->where($archiveTable . '.entity_id IN (?)', $result);
            $fetchedArchivedIds = $this->getConnection()->fetchAll($select, [], \Zend_Db::FETCH_COLUMN);
            return array_diff($result, $fetchedArchivedIds);
        }
        return $result;
    }

    /**
     * Returns connection.
     *
     * @return AdapterInterface
     */
    private function getConnection() : AdapterInterface
    {
        if (!$this->connection) {
            $this->connection = $this->resourceConnection->getConnection('sales');
        }
        return $this->connection;
    }
}
