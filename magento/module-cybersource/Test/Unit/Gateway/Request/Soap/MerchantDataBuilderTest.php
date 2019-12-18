<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\Soap;

use Magento\Cybersource\Gateway\Request\Soap\MerchantDataBuilder;
use Magento\Cybersource\Gateway\Request\SilentOrder\TransactionDataBuilder;
use Magento\Framework\Config\ScopeInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;

/**
 * Class MerchantDataBuilderTest
 */
class MerchantDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const MERCHANT_ID = 'MERCHANT_ID';

    const REFERENCE_NUMBER = 'REFERENCE_NUMBER';

    private const STORE_ID = 1;

    /**
     * @var MerchantDataBuilder
     */
    private $merchantDataBuilder;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var ScopeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scope;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->scope = $this->createMock(ScopeInterface::class);

        $this->merchantDataBuilder = new MerchantDataBuilder($this->configMock, $this->scope);
    }

    /**
     * Run test build method
     *
     * @return void
     */
    public function testBuildSuccess()
    {
        $expected = [
            'merchantID' => self::MERCHANT_ID,
            'merchantReferenceCode' => self::REFERENCE_NUMBER
        ];

        $this->configMock->expects(static::once())
            ->method('getValue')
            ->with(MerchantDataBuilder::MERCHANT_ID)
            ->willReturn(self::MERCHANT_ID, self::STORE_ID);

        $result = $this->merchantDataBuilder->build(['payment' => $this->getPaymentMock()]);
        static::assertEquals($expected, $result);
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentInstanceMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInstanceMock);

        $paymentMock->method('getOrder')
            ->willReturn($this->getOrderMock());

        $paymentInstanceMock->expects(static::once())
            ->method('getAdditionalInformation')
            ->with(TransactionDataBuilder::REFERENCE_NUMBER)
            ->willReturn(self::REFERENCE_NUMBER);

        return $paymentMock;
    }

    /**
     * Run test build method (Exception)
     *
     * @return void
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBuildException()
    {
        $this->merchantDataBuilder->build(['payment' => null]);
    }

    /**
     * @return OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getOrderMock()
    {
        $orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $orderMock->method('getStoreId')
            ->willReturn(self::STORE_ID);

        return $orderMock;
    }
}
