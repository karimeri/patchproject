<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

require __DIR__ . '/../../../Magento/Sales/_files/order_list.php';

/** @var array $orderList */
foreach ($orderList as $order) {
    $order
        ->setRewardPointsBalance(100)
        ->setRewardCurrencyAmount(15.1)
        ->setBaseRewardCurrencyAmount(14.9);

    $orderRepository->save($order);
}
