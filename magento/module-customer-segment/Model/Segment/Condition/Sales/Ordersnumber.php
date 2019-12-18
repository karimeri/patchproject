<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Model\Segment\Condition\Sales;

/**
 * Order numbers condition
 */
class Ordersnumber extends \Magento\CustomerSegment\Model\Segment\Condition\Sales\Combine
{
    /**
     * Name of condition for displaying as html
     *
     * @var string
     */
    protected $frontConditionName = 'Number of Orders';

    /**
     * Get condintion sql for number of orders
     *
     * @param string $operator
     * @param string $value
     * @return \Zend_Db_Expr
     */
    protected function getConditionSql($operator, $value)
    {
        $condition = $this->getResource()
            ->getConnection()
            ->getCheckSql("COUNT(*) {$operator} {$value}", 1, 0);
        return new \Zend_Db_Expr($condition);
    }

    /**
     * Check additional condition when number of orders equals to zero
     *
     * @inheritdoc
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        if ((int)$this->getValue() !== 0) {
            return parent::_prepareConditionsSql($customer, $website, $isFiltered);
        }

        $select = $this->getResource()->createSelect();
        $conditionSelect = $this->getResource()->createSelect();
        $conditionSelect->from(
            $this->getResource()->getTable('sales_order'),
            ['sales_order.customer_id']
        )
        ->where('customer_entity.entity_id = sales_order.customer_id')
        ->limit(1);

        if ($isFiltered) {
            $condition = $this->getResource()
                ->getConnection()
                ->getCheckSql("($conditionSelect) IS NULL", 1, 0);
            $select->from(
                $this->getResource()->getTable('customer_entity'),
                [$condition]
            )
            ->where('root.entity_id = customer_entity.entity_id');
        } else {
            $select->from(
                $this->getResource()->getTable('customer_entity'),
                ['customer_entity.entity_id', 'sales_order_customer_id' => $conditionSelect]
            )
            ->having('sales_order_customer_id IS NULL');
        }

        if ($isFiltered) {
            $select->where($this->_createCustomerFilter($customer, 'customer_entity.entity_id'));
        }
        return $select;
    }
}
