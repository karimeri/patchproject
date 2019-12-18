<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Block\Adminhtml\Manage\Grid\Renderer\Ordered;

use Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Ordered\Price;
use PHPUnit\Framework\MockObject\MockObject;

class PriceTest extends \PHPUnit\Framework\TestCase
{
    /** @var  Price */
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

        /** @var \Magento\Backend\Block\Widget\Grid\Column | MockObject $column */
        $column = $this->getMockBuilder(\Magento\Backend\Block\Widget\Grid\Column::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRate', 'getCurrencyCode'])
            ->getMock();
        $column->method('getRate')
            ->willReturn(1);

        $localCurrency = $this->getMockBuilder(\Magento\Framework\Locale\CurrencyInterface::class)
            ->getMock();
        $this->renderer = new Price($context, $localCurrency, []);
        $this->renderer->setColumn($column);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function rowDataProvider(): array
    {
        $row = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct'])
            ->getMock();

        $row->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->createProductMock());

        return [
            ['', new \Magento\Framework\DataObject()],
            ['1.200000', $row],
        ];
    }

    /**
     * Test render with null product
     *
     * @param string $expected
     * @param \Magento\Framework\DataObject $row
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
            ->method('getPrice')
            ->willReturn(1.2);
        return $productMock;
    }
}
