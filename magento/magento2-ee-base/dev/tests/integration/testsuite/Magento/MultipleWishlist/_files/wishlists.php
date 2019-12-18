<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

include 'customer.php';
include 'products.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$storeRepository = $objectManager->create(\Magento\Store\Api\StoreRepositoryInterface::class);
$store = $storeRepository->get('default');

$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
for ($i = 1; $i <= 2; $i++) {
    $wishlist = $objectManager->create(\Magento\Wishlist\Model\Wishlist::class);
    $wishlist->setSharingCode('wishlist_fixture_' . $i)
        ->setStore($store)
        ->setCustomerId($customer->getId());
    $wishlist->save();

    $product = $productRepository->get('simple' . $i);
    $wishlist->addNewItem($product, new \Magento\Framework\DataObject());
    $wishlist->save();
}
