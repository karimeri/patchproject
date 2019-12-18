<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin;

use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\GiftCard\Api\Data\GiftcardAmountInterface;
use Magento\GiftCard\Controller\Adminhtml\Product\Initialization\Helper\Plugin\GiftCard;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Catalog\Model\Product;

class GiftCardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GiftCard
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $amountFactoryMock;

    /**
     * @var Helper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productMock;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $constructorArgs = $this->objectManagerHelper->getConstructArguments(GiftCard::class);
        $attributeRepositoryMock = $constructorArgs['attributeRepository'];
        $attributeMock = $this->getMockBuilder(AttributeInterface::class)
            ->getMockForAbstractClass();
        $attributeRepositoryMock->method('get')
            ->willReturn($attributeMock);
        $attributeMock->method('getAttributeId')
            ->willReturn('attributeId');
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes','setExtensionAttributes', 'getTypeId', 'getData'])
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->amountFactoryMock = $constructorArgs['amountFactory'];

        $this->plugin = $this->objectManagerHelper->getObject(GiftCard::class, $constructorArgs);
    }

    /**
     * @param string $productTypeId
     * @param array $productData
     * @param array $expectedProductData
     * @dataProvider beforeInitializeFromDataDataProvider
     */
    public function testBeforeInitializeFromData(string $productTypeId, array $productData, array $expectedProductData)
    {
        $this->productMock->expects(static::once())
            ->method('getTypeId')
            ->willReturn($productTypeId);

        $this->assertEquals(
            [$this->productMock, $expectedProductData],
            $this->plugin->beforeInitializeFromData($this->subjectMock, $this->productMock, $productData)
        );
    }

    public function beforeInitializeFromDataDataProvider()
    {
        return [
            'gift card, no amount' => [
                \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD,
                ['initialData'],
                ['initialData', 'giftcard_amounts' => []]
            ],
            'gift card, with amount' => [
                \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD,
                ['initialData', 'giftcard_amounts' => ['amount data']],
                ['initialData', 'giftcard_amounts' => ['amount data']]
            ],
            'non gift card' => [
                'other product',
                ['initialData'],
                ['initialData']
            ],
        ];
    }

    /**
     * @param string $productTypeId
     * @param array $amountsData
     * @param int $amountFactoryCallNum
     * @param int $callNum
     * @dataProvider afterInitializeDataProvider
     */
    public function testAfterInitialize(
        string $productTypeId,
        array $amountsData,
        int $amountFactoryCallNum,
        int $callNum = 1
    ) {
        $this->productMock->method('getTypeId')
            ->willReturn($productTypeId);
        $this->productMock->method('getData')
            ->willReturn($amountsData);
        $amountMock = $this->createMock(GiftcardAmountInterface::class);
        $this->amountFactoryMock->expects($this->exactly($amountFactoryCallNum))
            ->method('create')
            ->willReturn($amountMock);
        $extensionMock = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setGiftcardAmounts'])
            ->getMockForAbstractClass();
        $extensionMock->expects($this->exactly($callNum))
            ->method('setGiftcardAmounts');
        $this->productMock->expects($this->exactly($callNum))
            ->method('getExtensionAttributes')
            ->willReturn($extensionMock);
        $this->productMock->expects($this->exactly($callNum))
            ->method('setExtensionAttributes');
        $this->plugin->afterInitialize($this->subjectMock, $this->productMock);
    }

    public function afterInitializeDataProvider()
    {
        return [
            'gift card, no amount' => [
                \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD,
                [],
                1,
            ],
            'gift card, with amount' => [
                \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD,
                [['value' => 10]],
                1
            ],
            'non gift card' => [
                'other product',
                ['initialData'],
                0,
                0
            ],
        ];
    }
}
