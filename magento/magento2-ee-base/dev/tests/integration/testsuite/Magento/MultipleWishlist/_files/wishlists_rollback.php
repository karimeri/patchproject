<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

for ($i = 1; $i <= 2; $i++) {
    $wishlist = $objectManager->create(\Magento\Wishlist\Model\Wishlist::class);
    $wishlist->loadByCode('wishlist_fixture_' . $i);
    if ($wishlist->getId()) {
        $wishlist->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

include 'products_rollback.php';
include 'customer_rollback.php';
