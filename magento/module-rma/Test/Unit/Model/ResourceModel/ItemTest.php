<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Model\ResourceModel\Item as RmaItem;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RmaItem
     */
    protected $resourceModel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $appResource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eqvModelConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeSet;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatLocale;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderItemCollection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productTypesConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $adminItem;

    /**
     * @var ProductCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productCollectionFactoryMock;

    protected function setUp()
    {
        $this->appResource = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eqvModelConfig = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeSet = $this->getMockBuilder(\Magento\Eav\Model\Entity\Attribute\Set::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatLocale = $this->getMockBuilder(\Magento\Framework\Locale\Format::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceHelper = $this->getMockBuilder(\Magento\Eav\Model\ResourceModel\Helper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validatorFactory = $this->getMockBuilder(\Magento\Framework\Validator\UniversalFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rmaHelper = $this->getMockBuilder(\Magento\Rma\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderItemCollection =
            $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productFactory = $this->getMockBuilder(\Magento\Catalog\Model\ProductFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productTypesConfig = $this->getMockBuilder(\Magento\Catalog\Model\ProductTypes\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adminItem = $this->getMockBuilder(\Magento\Sales\Model\Order\Admin\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = [];
        $this->productCollectionFactoryMock = $this->getMockBuilder(ProductCollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $arguments = [
            'resource' => $this->appResource,
            'eavConfig' => $this->eqvModelConfig,
            'attrSetEntity' => $this->attributeSet,
            'localeFormat' => $this->formatLocale,
            'resourceHelper' => $this->resourceHelper,
            'universalFactory' => $this->validatorFactory,
            'rmaData' => $this->rmaHelper,
            'ordersFactory' => $this->orderItemCollection,
            'productFactory' => $this->productFactory,
            'refundableList' => $this->productTypesConfig,
            'adminOrderItem' => $this->adminItem,
            'data' => $data,
            'productCollectionFactory' => $this->productCollectionFactoryMock
        ];

        $this->resourceModel = $objectManager->getObject(RmaItem::class, $arguments);
    }

    public function testGetReturnableItems()
    {
        $shippedItems = [5 => 3];
        $expectsItems = [5 => 0];
        $salesAdapterMock = $this->getAdapterMock($shippedItems);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);
        $orderId = 1000001;
        $result = $this->resourceModel->getReturnableItems($orderId);
        $this->assertEquals($expectsItems, $result);
    }

    public function testGetOrderItemsNoItems()
    {
        $orderId = 10000001;

        $readMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $readMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($readMock);
        $expression = new \Zend_Db_Expr('(qty_shipped - qty_returned)');

        $orderItemsCollectionMock = $this->getMockBuilder(
            \Magento\Sales\Model\ResourceModel\Order\Item\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemsCollectionMock->expects($this->once())
            ->method('addExpressionFieldToSelect')
            ->with('available_qty', $expression, ['qty_shipped', 'qty_returned'])
            ->will($this->returnSelf());
        $orderItemsCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->will($this->returnSelf());
        $orderItemsCollectionMock->expects($this->once())
            ->method('count')
            ->will($this->returnValue(0));

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderItemsCollectionMock));

        $result = $this->resourceModel->getOrderItems($orderId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    public function testGetOrderItemsRemoveByParent()
    {
        $orderId = 10000001;
        $excludeId = 5;
        $parentId = 6;
        $itemId = 1;

        $readMock = $this->getAdapterMock([$itemId => 1]);
        $salesAdapterMock = $this->getAdapterMock([$itemId => 1]);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);
        $this->resourceModel->setConnection($readMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(0);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderItemsCollectionMock));

        $parentItemMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentItemId', 'getId', '__wakeup'])
            ->getMock();
        $parentItemMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($itemId));
        $parentItemMock->expects($this->any())
            ->method('getParentItemId')
            ->will($this->returnValue($parentId));

        $iterator = new \ArrayIterator([$parentItemMock]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($iterator));

        $result = $this->resourceModel->getOrderItems($orderId, $excludeId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    public function testGetOrderItemsCanReturnNotEmpty()
    {
        $orderId = 10000001;
        $itemId = 1;
        $fetchData = [$itemId => 2];
        $storeId = 1;

        $salesAdapterMock = $this->getAdapterMock($fetchData);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(1);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderItemsCollectionMock));

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $itemMockCanReturn = $this->prepareOrderItemMock($itemId, $storeId, $productMock);

        $iterator = new \ArrayIterator([$itemMockCanReturn]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($iterator));

        $this->rmaHelper->method('canReturnProduct')
            ->willReturn(true);

        $this->prepareProductCollectionMock();

        $returnableItems = $this->resourceModel->getReturnableItems($orderId);
        $result = $this->resourceModel->getOrderItems($orderId);
        foreach ($result as $item) {
            $this->assertEquals($item->getAvailableQty(), $returnableItems[$item->getId()]);
        }
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    public function testGetOrderItemsCanReturnEmpty()
    {
        $orderId = 10000001;
        $itemId = 1;
        $fetchData = [];
        $storeId = 1;

        $salesAdapterMock = $this->getAdapterMock($fetchData);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(1);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderItemsCollectionMock));

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFactory->method('create')
            ->will($this->returnValue($productMock));

        $this->rmaHelper->method('canReturnProduct')
            ->willReturn(true);

        $this->prepareProductCollectionMock();

        $itemMockCanReturn = $this->prepareOrderItemMock($itemId, $storeId, $productMock);

        $iterator = new \ArrayIterator([$itemMockCanReturn]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($iterator));

        $result = $this->resourceModel->getOrderItems($orderId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    public function testGetOrderItemsCanReturn()
    {
        $orderId = 10000001;
        $itemId = 1;
        $fetchData = [];
        $storeId = 1;

        $salesAdapterMock = $this->getAdapterMock($fetchData);
        $this->appResource->expects($this->atLeastOnce())->method('getConnection')->willReturn($salesAdapterMock);

        $orderItemsCollectionMock = $this->prepareOrderItemCollectionMock(1);

        $this->orderItemCollection->expects($this->once())
            ->method('create')
            ->will($this->returnValue($orderItemsCollectionMock));

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFactory->method('create')
            ->will($this->returnValue($productMock));

        $itemMockCanReturn = $this->prepareOrderItemMock($itemId, $storeId, $productMock);

        $iterator = new \ArrayIterator([$itemMockCanReturn]);

        $orderItemsCollectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($iterator));

        $this->rmaHelper->method('canReturnProduct')
            ->willReturn(true);

        $this->prepareProductCollectionMock();

        $result = $this->resourceModel->getOrderItems($orderId);
        $this->assertEquals($orderItemsCollectionMock, $result);
    }

    /**
     * Get universal adapter mock with specified result for fetchPairs
     *
     * @param array $data
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAdapterMock($data)
    {
        $this->appResource->expects($this->any())
            ->method('getTableName')
            ->will($this->returnArgument(0));

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('from')
            ->will($this->returnSelf());
        $selectMock->expects($this->any())
            ->method('joinInner')
            ->will($this->returnSelf());
        $selectMock->expects($this->any())
            ->method('where')
            ->will($this->returnSelf());

        $connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connectionMock->expects($this->any())
            ->method('select')
            ->will($this->returnValue($selectMock));
        $connectionMock->expects($this->any())
            ->method('fetchPairs')
            ->will($this->returnValue($data));

        return $connectionMock;
    }

    /**
     * @param int $itemId
     * @param int $storeId
     * @param \PHPUnit_Framework_MockObject_MockObject $productMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareOrderItemMock($itemId, $storeId, $productMock)
    {
        $itemMockCanReturn = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getParentItemId',
                'getId',
                '__wakeup',
                'getStoreId',
                'getProduct',
                'getOrderProducts',
                'getProductId'
            ])
            ->getMock();
        $itemMockCanReturn->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($itemId));
        $itemMockCanReturn->method('getStoreId')
            ->will($this->returnValue($storeId));
        $itemMockCanReturn->method('getProduct')
            ->willReturn($productMock);
        $itemMockCanReturn->method('getProductId')
            ->willReturn(1);

        return $itemMockCanReturn;
    }

    /**
     * @param int $countItemCollection
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareOrderItemCollectionMock(int $countItemCollection)
    {
        $orderItemsCollectionMock = $this->getMockBuilder(
            \Magento\Sales\Model\ResourceModel\Order\Item\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $orderItemsCollectionMock->expects($this->once())
            ->method('addExpressionFieldToSelect')
            ->will($this->returnSelf());
        $orderItemsCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->will($this->returnSelf());
        $orderItemsCollectionMock->expects($this->once())
            ->method('count')
            ->will($this->returnValue($countItemCollection));
        $orderItemsCollectionMock->expects($this->any())
            ->method('removeItemByKey');
        return $orderItemsCollectionMock;
    }

    /**
     * Prepare product collection mocks for "getOrderProducts" method
     *
     * @return void
     */
    private function prepareProductCollectionMock(): void
    {
        $productCollectionMock = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productCollectionFactoryMock->method('create')
            ->willReturn($productCollectionMock);

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productCollectionMock->method('getSelect')
            ->willReturn($selectMock);

        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productCollectionMock->method('getItems')
            ->willReturn([1 => $productMock]);

        $selectMock->method('reset')->willReturnSelf();
        $selectMock->method('columns')->willReturnSelf();
    }
}
