<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\Sales\Model\Order\Item;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\GiftCardAccount\Model\Pool;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\DB\Transaction;

/** @var ObjectManager $objectManager */
$objectManager = Bootstrap::getObjectManager();

$addressData = include __DIR__ . '/../../Sales/_files/address_data.php';
require __DIR__ . '/../../../Magento/Braintree/Fixtures/payment.php';

$storeId = $objectManager->get(StoreManagerInterface::class)
    ->getStore()
    ->getId();
$websiteId = $objectManager->get(StoreManagerInterface::class)
    ->getWebsite()
    ->getId();

$objectManager->get(MutableScopeConfigInterface::class)
    ->setValue(Pool::XML_CONFIG_POOL_SIZE, 2, 'website', 'base');
/** @var $pool Pool */
$pool = $objectManager->create(Pool::class);
$pool->setWebsiteId($websiteId)
    ->generatePool();

$billingAddress = $objectManager->create(Address::class, ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)
    ->setAddressType('shipping');

/** @var Item $orderGiftCardItem Item */
$orderGiftCardItem = $objectManager->create(Item::class);
$orderGiftCardItem->setProductId(1)
    ->setProductType(Giftcard::TYPE_GIFTCARD)
    ->setBasePrice(100)
    ->setQtyOrdered(2)
    ->setStoreId($storeId)
    ->setProductOptions(
        [
            'giftcard_amount' => 'custom',
            'custom_giftcard_amount' => 100,
            'giftcard_sender_name' => 'Gift Card Sender Name',
            'giftcard_sender_email' => 'sender@example.com',
            'giftcard_recipient_name' => 'Gift Card Recipient Name',
            'giftcard_recipient_email' => 'recipient@example.com',
            'giftcard_message' => 'Gift Card Message',
            'giftcard_email_template' => 'giftcard_email_template',
        ]
    );
/** @var Order $order */
$order = $objectManager->create(Order::class);
$order->setIncrementId('100000002')
    ->addItem($orderGiftCardItem)
    ->setCustomerEmail('someone@example.com')
    ->setCustomerIsGuest(true)
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->setStoreId($storeId)
    ->setPayment($payment);

$orderService = $objectManager::getInstance()->create(InvoiceManagementInterface::class);
/** @var Invoice $invoice */
$invoice = $orderService->prepareInvoice($order);
$invoice->register();
$order = $invoice->getOrder();
$order->setIsInProcess(true);
$transactionSave = $objectManager->create(Transaction::class);
$transactionSave->addObject($order)->addObject($invoice)->save();
