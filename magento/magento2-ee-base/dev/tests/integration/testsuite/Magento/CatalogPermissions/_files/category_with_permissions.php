<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\CatalogPermissions\Model\Permission;
use Magento\Store\Model\StoreManagerInterface;

require __DIR__ . '/../../Catalog/_files/category.php';

/** @var $permission Permission */
$permission = Bootstrap::getObjectManager()->create(Permission::class);
$websiteId = Bootstrap::getObjectManager()
    ->get(StoreManagerInterface::class)
    ->getWebsite()
    ->getId();
$permission->setEntityId(1)
    ->setWebsiteId($websiteId)
    ->setCategoryId($category->getId())
    ->setCustomerGroupId(1)
    ->setGrantCatalogCategoryView(Permission::PERMISSION_DENY)
    ->setGrantCatalogProductPrice(Permission::PERMISSION_DENY)
    ->setGrantCheckoutItems(Permission::PERMISSION_DENY)
    ->save();
