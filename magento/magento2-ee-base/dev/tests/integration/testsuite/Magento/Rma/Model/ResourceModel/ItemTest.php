<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\ResourceModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

class ItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * (@inheritdoc)
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/enabled_on_product 0
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Rma/_files/order.php
     * @return void
     */
    public function testGetOrderItems(): void
    {
        /** @var Order $order */
        $order = $this->objectManager->create(Order::class);
        $order->loadByIncrementId('100000001');
        $orderItems = $this->getOrderItems($order);
        $this->assertCount(1, $orderItems);
        $this->changeIsReturnableProduct(0);
        $orderItems = $this->getOrderItems($order);
        $this->assertCount(0, $orderItems);
    }

    /**
     * Change "is_returnable" product parameter
     *
     * @param int $value
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return void
     */
    private function changeIsReturnableProduct(int $value): void
    {
        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('simple');
        $product->setData('is_returnable', $value);
        $product->save();
    }

    /**
     * Return order items array
     *
     * @param Order $order
     * @return array
     */
    private function getOrderItems(Order $order): array
    {
        $item = $this->objectManager->create(Item::class);
        return $item->getOrderItems($order->getId())->getItems();
    }
}
