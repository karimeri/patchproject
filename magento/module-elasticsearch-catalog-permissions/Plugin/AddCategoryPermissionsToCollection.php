<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ElasticsearchCatalogPermissions\Plugin;

use Magento\Customer\Model\Session;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Search\Model\EngineResolver;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\CatalogPermissions\Model\Permission;
use Magento\CatalogPermissions\App\Config;

/**
 * Add category permissions filters to collection.
 */
class AddCategoryPermissionsToCollection
{
    /**
     * Customer session instance
     *
     * @var Session
     */
    private $customerSession;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var ResolverInterface
     */
    private $fieldNameResolver;

    /**
     * @var AttributeProvider
     */
    private $attributeAdapterProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var EngineResolverInterface
     */
    private $engineResolver;

    /**
     * Constructor
     *
     * @param Session $customerSession
     * @param StoreManager $storeManager
     * @param ResolverInterface $fieldNameResolver
     * @param AttributeProvider $attributeAdapterProvider
     * @param Config $config
     * @param EngineResolverInterface $engineResolver
     */
    public function __construct(
        Session $customerSession,
        StoreManager $storeManager,
        ResolverInterface $fieldNameResolver,
        AttributeProvider $attributeAdapterProvider,
        Config $config,
        EngineResolverInterface $engineResolver
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->fieldNameResolver = $fieldNameResolver;
        $this->attributeAdapterProvider = $attributeAdapterProvider;
        $this->config = $config;
        $this->engineResolver = $engineResolver;
    }

    /**
     * Add catalog permissions to filter.
     *
     * @param Collection $productCollection
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeLoad(Collection $productCollection, $printQuery = false, $logQuery = false)
    {
        if ($productCollection->isLoaded() || !$this->config->isEnabled() || $this->isCurrentEngineMysql()) {
            return [$printQuery, $logQuery];
        }
        $categoryPermissionAttribute = $this->attributeAdapterProvider->getByAttributeCode('category_permission');
        $categoryPermissionKey = $this->fieldNameResolver->getFieldName(
            $categoryPermissionAttribute,
            [
                'storeId' => $this->storeManager->getStore()->getId(),
                'customerGroupId' => $this->customerSession->getCustomerGroupId(),
            ]
        );

        $productCollection->addFieldToFilter('category_permissions_field', $categoryPermissionKey);
        $productCollection->addFieldToFilter('category_permissions_value', Permission::PERMISSION_DENY);

        return [$printQuery, $logQuery];
    }

    /**
     * Check if current engine is MYSQL.
     *
     * @return bool
     */
    private function isCurrentEngineMysql()
    {
        return $this->engineResolver->getCurrentSearchEngine() === EngineResolver::CATALOG_SEARCH_MYSQL_ENGINE;
    }
}
