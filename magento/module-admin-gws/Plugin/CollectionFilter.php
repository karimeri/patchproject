<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class for filer collection and leave only allowed for current admin entities.
 */
class CollectionFilter
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var array
     */
    private $tableColumns;

    /**
     * @param Role $role
     */
    public function __construct(Role $role)
    {
        $this->role = $role;
        $this->tableColumns = [];
    }

    /**
     * Adds allowed websites or stores to query filter.
     *
     * @param AbstractCollection $collection
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoadWithFilter(AbstractCollection $collection, $printQuery = false, $logQuery = false)
    {
        $this->filterCollection($collection);

        return [$printQuery, $logQuery];
    }

    /**
     * Adds allowed websites or stores to query filter.
     *
     * @param AbstractCollection $collection
     */
    public function beforeGetSelectCountSql(AbstractCollection $collection)
    {
        $this->filterCollection($collection);
    }

    /**
     * Add filter to collection.
     *
     * @param AbstractCollection $collection
     */
    private function filterCollection(AbstractCollection $collection)
    {
        if (!$this->role->getIsAll() && isset($collection->getSelect()->getPart(Select::FROM)['main_table'])) {
            $mainTable = $collection->getMainTable();
            if (!isset($this->tableColumns[$mainTable])) {
                $describe = $collection->getConnection()->describeTable($mainTable);
                $this->tableColumns[$mainTable] = array_column($describe, 'COLUMN_NAME');
            }

            if (method_exists($collection, 'addStoreFilter')) {
                $collection->addStoreFilter($this->role->getStoreIds());
            } elseif (in_array('website_id', $this->tableColumns[$mainTable], true)) {
                $collection->getSelect()->where('main_table.website_id IN (?)', $this->role->getRelevantWebsiteIds());
            } elseif (in_array('store_id', $this->tableColumns[$mainTable], true)) {
                $collection->getSelect()->where('main_table.store_id IN (?)', $this->role->getStoreIds());
            }
        }
    }
}
