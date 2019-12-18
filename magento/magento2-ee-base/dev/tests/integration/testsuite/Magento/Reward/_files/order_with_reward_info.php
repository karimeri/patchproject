<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\OrderRepositoryInterface;

require __DIR__ . '/../../../Magento/Sales/_files/order.php';

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);
$order = $orderRepository->get($order->getId());

$order->setData('reward_points_balance', 100)
    ->setData('reward_currency_amount', 15.1)
    ->setData('base_reward_currency_amount', 14.9);

$orderRepository->save($order);
