<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Model\Indexer\Product\Action;

use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

/**
 * Prepares select with products for indexer actions
 */
class ProductSelectDataProvider
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var CatalogConfig
     */
    private $catalogConfig;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var int|null
     */
    private $statusAttributeId;

    /**
     * @var int|null
     */
    private $isActiveAttributeId;

    /**
     * @var string
     */
    private $productLinkField;

    /**
     * @var string
     */
    private $categoryLinkField;

    /**
     * @param ResourceConnection $resource
     * @param CatalogConfig $catalogConfig
     * @param MetadataPool $metadataPool
     * @throws \Exception
     */
    public function __construct(
        ResourceConnection $resource,
        CatalogConfig $catalogConfig,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->catalogConfig = $catalogConfig;
        $this->statusAttributeId = null;
        $this->isActiveAttributeId = null;
        $this->productLinkField = $metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $this->categoryLinkField = $metadataPool->getMetadata(CategoryInterface::class)->getLinkField();
    }

    /**
     * Getter for statusAttributeId
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getStatusAttributeId(): ?int
    {
        if ($this->statusAttributeId === null) {
            $statusAttributeId = $this->catalogConfig->getAttribute(
                Product::ENTITY,
                'status'
            )->getId();
            if ($statusAttributeId) {
                $this->statusAttributeId = (int)$statusAttributeId;
            }
        }
        return $this->statusAttributeId;
    }

    /**
     * Getter for isActiveAttributeId
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getIsActiveAttributeId(): ?int
    {
        if ($this->isActiveAttributeId === null) {
            $isActiveAttributeId = $this->catalogConfig->getAttribute(
                Category::ENTITY,
                'is_active'
            )->getId();
            if ($isActiveAttributeId) {
                $this->isActiveAttributeId = (int)$isActiveAttributeId;
            }
        }
        return $this->isActiveAttributeId;
    }

    /**
     * Build select with necessary permissions
     *
     * @param int $customerGroupId
     * @param int $storeId
     * @param array $permissionsColumns
     * @param string $indexTableName
     * @param int[] $productList
     * @return Select
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSelect(
        int $customerGroupId,
        int $storeId,
        array $permissionsColumns,
        string $indexTableName,
        array $productList
    ): Select {
        $statusAttributeId = $this->getStatusAttributeId();
        $isActiveAttributeId = $this->getIsActiveAttributeId();

        $select = $this->connection->select()->from(
            ['category_product' => $this->resource->getTableName('catalog_category_product')],
            []
        )->columns(
            array_merge(
                ['category_product.product_id', 'store.store_id', 'permission_index.customer_group_id'],
                $permissionsColumns
            )
        )->joinInner(
            ['product_website' => $this->resource->getTableName('catalog_product_website')],
            'product_website.product_id = category_product.product_id',
            []
        )->joinInner(
            ['store_group' => $this->resource->getTableName('store_group')],
            'store_group.website_id = product_website.website_id',
            []
        )->joinInner(
            ['store' => $this->resource->getTableName('store')],
            'store.website_id = product_website.website_id'
            . ' AND store.group_id = store_group.group_id'
            . $this->connection->quoteInto(' AND store.store_id = ?', $storeId),
            []
        )->joinInner(
            ['category' => $this->resource->getTableName('catalog_category_entity')],
            'category.entity_id = category_product.category_id' .
            ' AND category.path LIKE ' .
            $this->connection->getConcatSql(
                [
                    $this->connection->quote(Category::TREE_ROOT_ID . '/'),
                    $this->connection->quoteIdentifier('store_group.root_category_id'),
                    $this->connection->quote('/%')
                ]
            ),
            []
        )->joinInner(
            ['cpe' => $this->resource->getTableName('catalog_product_entity')],
            'cpe.entity_id = category_product.product_id',
            []
        )->joinLeft(
            ['cpsd' => $this->resource->getTableName('catalog_product_entity_int')],
            'cpsd.'. $this->productLinkField . ' = cpe.' . $this->productLinkField . ' AND cpsd.store_id = 0'
            . $this->connection->quoteInto(
                ' AND cpsd.attribute_id = ?',
                $statusAttributeId
            ),
            []
        )->joinLeft(
            ['cpss' => $this->resource->getTableName('catalog_product_entity_int')],
            'cpss.' . $this->productLinkField . ' = cpe.' . $this->productLinkField
            . ' AND cpss.attribute_id = cpsd.attribute_id AND cpss.store_id = store.store_id',
            []
        )->joinLeft(
            ['ccad' => $this->resource->getTableName('catalog_category_entity_int')],
            'ccad.' . $this->categoryLinkField . ' = category.' . $this->categoryLinkField . ' AND ccad.store_id = 0'
            . $this->connection->quoteInto(
                ' AND ccad.attribute_id = ?',
                $isActiveAttributeId
            ),
            []
        )->joinLeft(
            ['ccas' => $this->resource->getTableName('catalog_category_entity_int')],
            'ccas.' . $this->categoryLinkField . ' = category.' . $this->categoryLinkField
            . ' AND ccas.attribute_id = ccad.attribute_id AND ccas.store_id = store.store_id',
            []
        )->joinInner(
            ['permission_index' => $indexTableName],
            'permission_index.category_id = category_product.category_id' .
            ' AND permission_index.website_id = product_website.website_id' .
            $this->connection->quoteInto(' AND permission_index.customer_group_id = ?', $customerGroupId),
            []
        )->where(
            $this->connection->getIfNullSql('cpss.value', 'cpsd.value') . ' = ?',
            Status::STATUS_ENABLED
        )->where(
            $this->connection->getIfNullSql('ccas.value', 'ccad.value') . ' = ?',
            1
        )->group(
            ['category_product.product_id']
        );

        if (!empty($productList)) {
            $select->where('category_product.product_id IN (?)', $productList);
        }

        return $select;
    }
}
