<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Test\Unit\Model\Product;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Model\AttributeRepository;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\GiftCard\Model\Giftcard\AmountRepository;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Model\Product\SaveHandler
     */
    private $saveHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPool;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $getAmountIdsByProduct;

    /**
     * @var AmountRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftcardAmountRepositoryMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->metadataPool = $this->createMock(\Magento\Framework\EntityManager\MetadataPool::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->getAmountIdsByProduct = $this->createMock(
            \Magento\GiftCard\Model\ResourceModel\Db\GetAmountIdsByProduct::class
        );
        $this->giftcardAmountRepositoryMock = $this->createMock(AmountRepository::class);
        $attributeRepositoryMock = $this->createMock(ProductAttributeRepositoryInterface::class);
        $attributeMock = $this->createMock(Attribute::class);
        $attributeRepositoryMock->method('get')->willReturn($attributeMock);
        $attributeMock->method('getAttributeId')->willReturn('attributeId');
        $this->saveHandler = $objectManager->getObject(
            \Magento\GiftCard\Model\Product\SaveHandler::class,
            [
                'metadataPool' => $this->metadataPool,
                'storeManager' => $this->storeManager,
                'getAmountIdsByProduct' => $this->getAmountIdsByProduct,
                'giftcardAmountRepository' => $this->giftcardAmountRepositoryMock,
                'attributeRepository' => $attributeRepositoryMock,
            ]
        );
    }

    /**
     * @param GiftcardAmountInterface[] $amounts
     * @param int $deleteCallNum
     * @param int $saveCallNum
     * @param array $amountIds
     * @dataProvider executeDataProvider
     */
    public function testExecute($amounts, int $deleteCallNum = 0, int $saveCallNum = 0, $amountIds = [1])
    {
        $giftCardAmounts = ['test' => []];
        $entityData = ['row_id' => 1];
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $metadataMock = $this->createMock(\Magento\Framework\EntityManager\EntityMetadataInterface::class);
        $hydratorMock = $this->createMock(\Magento\Framework\EntityManager\HydratorInterface::class);
        $productMock->expects($this->once())->method('getTypeId')->willReturn(Giftcard::TYPE_GIFTCARD);
        $extensionAttributes = $this->createPartialMock(
            \Magento\Catalog\Api\Data\ProductExtension::class,
            ['getGiftcardAmounts']
        );
        $this->metadataPool->expects($this->once())->method('getMetadata')->willReturn($metadataMock);
        $metadataMock->expects($this->once())->method('getLinkField')->willReturn('row_id');
        $this->metadataPool
            ->expects($this->once())
            ->method('getHydrator')
            ->with(ProductInterface::class)
            ->willReturn($hydratorMock);
        $hydratorMock->method('extract')->with($productMock)->willReturn($entityData);
        $productMock->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())->method('getGiftcardAmounts')->willReturn($amounts);
        $productMock->method('getData')->with('giftcard_amounts')->willReturn($giftCardAmounts);
        $this->storeManager->method('getStore')->willReturn($storeMock);
        $storeMock->method('getWebsiteId')->willReturn(1);
        $this->getAmountIdsByProduct->method('execute')->with('row_id', 1, 1)->willReturn($amountIds);
        $this->giftcardAmountRepositoryMock->method('get')->willReturn($this->getGiftCardAmountMock());
        $this->giftcardAmountRepositoryMock->expects($this->exactly($saveCallNum))->method('save');
        $this->giftcardAmountRepositoryMock->expects($this->exactly($deleteCallNum))->method('delete');
        $this->saveHandler->execute($productMock);
    }

    public function executeDataProvider()
    {
        $giftcardAmountMockWithDataA = $this->getGiftCardAmountMock();
        $giftcardAmountMockWithDataA->method('getData')->willReturn(['value' => 30]);
        $giftcardAmountMockWithDataB = $this->getGiftCardAmountMock();
        $giftcardAmountMockWithDataB->method('getData')->willReturn(['value' => 40]);
        $giftcardAmountMockNoDataC = $this->getGiftCardAmountMock();
        $giftcardAmountMockNoDataC->method('getData')->willReturn([]);
        $giftcardAmountMockNoDataD = $this->getGiftCardAmountMock();
        $giftcardAmountMockNoDataD->method('getData')->willReturn([]);
        return [
            'no amounts entity' => [[], 0, 0],
            'one amount entity' => [[$giftcardAmountMockWithDataA], 1, 1],
            'one amount no data entity' => [[$giftcardAmountMockNoDataC], 1, 0],
            'two amounts entity' => [[$giftcardAmountMockWithDataA, $giftcardAmountMockWithDataB], 0, 2, []],
            'two amounts, one with, one without data entity' => [
                [$giftcardAmountMockWithDataA, $giftcardAmountMockNoDataC],
                1,
                1
            ],
            'two amounts without data entity' => [
                [$giftcardAmountMockNoDataC, $giftcardAmountMockNoDataD],
                1,
                0
            ],
        ];
    }

    /**
     * Get GiftCardAmountInterface mock object
     *
     * @return MockObject
     */
    private function getGiftCardAmountMock(): MockObject
    {
        return $this->getMockBuilder(GiftcardAmountInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setData', 'unsetData'])
            ->getMockForAbstractClass();
    }
}
