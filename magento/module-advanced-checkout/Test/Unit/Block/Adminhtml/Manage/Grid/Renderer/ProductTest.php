<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Block\Adminhtml\Manage\Grid\Renderer;

use Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Product;
use PHPUnit\Framework\MockObject\MockObject;

class ProductTest extends \PHPUnit\Framework\TestCase
{
    /** @var  Product */
    private $renderer;

    /**
     * Set up method
     */
    protected function setUp()
    {
        parent::setUp();
        $context = $this->getMockBuilder(\Magento\Backend\Block\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->atLeastOnce())
            ->method('getEscaper')
            ->willReturn(new \Magento\Framework\Escaper());

        /** @var \Magento\Backend\Block\Widget\Grid | MockObject $grid */
        $grid = $this->getMockBuilder(\Magento\Backend\Block\Widget\Grid::class)
            ->disableOriginalConstructor()
            ->setMethods(['getListType'])
            ->getMock();

        $grid->expects($this->atLeastOnce())
            ->method('getListType')
            ->willReturn('testListType');
        /** @var \Magento\Backend\Block\Widget\Grid\Column | MockObject $column */
        $column = $this->getMockBuilder(\Magento\Backend\Block\Widget\Grid\Column::class)
            ->disableOriginalConstructor()
            ->getMock();

        $column->expects($this->atLeastOnce())
            ->method('getGrid')
            ->willReturn($grid);

        $this->renderer = new Product($context);
        $this->renderer->setColumn($column);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function rowDataProvider(): array
    {
        $rowAsProduct = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var MockObject $rowAsProductConfigure */
        $rowAsProductConfigure = $this->createProductMock();
        $rowAsProductConfigure->expects($this->once())
            ->method('getid')
            ->willReturn('id');

        /** @var MockObject $rowAsWishlistItem */
        $rowAsWishlistItem = $this->getMockBuilder(\Magento\Wishlist\Model\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rowAsWishlistItem->expects($this->once())
            ->method('getId')
            ->willReturn('id');
        $rowAsWishlistItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->createProductMock());

        /** @var MockObject $rowAsWishlistItem */
        $rowAsOrderItem = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rowAsOrderItem->expects($this->once())
            ->method('getId')
            ->willReturn('id');
        $rowAsOrderItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->createProductMock());

        return [
            [
                '<a href="javascript:void(0)" disabled="disabled" class="action-configure disabled">Configure</a>',
                new \Magento\Framework\DataObject()
            ],
            [
                '<a href="javascript:void(0)" disabled="disabled" class="action-configure disabled">Configure</a>',
                $rowAsProduct
            ],
            [
                '<a href="javascript:void(0)" list_type = "testListType" item_id = id '
                . 'class="action-configure ">Configure</a>',
                $rowAsProductConfigure
            ],
            [
                '<a href="javascript:void(0)" list_type = "testListType" item_id = id '
                . 'class="action-configure ">Configure</a>',
                $rowAsWishlistItem
            ],
            [
                '<a href="javascript:void(0)" list_type = "testListType" item_id = id '
                . 'class="action-configure ">Configure</a>',
                $rowAsOrderItem
            ],
        ];
    }

    /**
     * Test render with null product
     *
     * @param string $expected
     * @param \Magento\Framework\DataObject $row
     * @throws \Magento\Framework\Exception\LocalizedException
     * @dataProvider rowDataProvider
     */
    public function testRenderWithNullRow($expected, $row): void
    {
        $this->assertEquals($expected, $this->renderer->render($row));
    }

    /**
     * Creates product mock
     *
     * @return \Magento\Catalog\Model\Product
     */
    protected function createProductMock(): \Magento\Catalog\Model\Product
    {
        /** @var \Magento\Catalog\Model\Product | MockObject $productMock */
        $productMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock->expects($this->once())
            ->method('canConfigure')
            ->willReturn(true);
        return $productMock;
    }
}
