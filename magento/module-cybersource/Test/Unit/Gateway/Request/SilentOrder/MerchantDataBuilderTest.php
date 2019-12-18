<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\MerchantDataBuilder;
use Magento\Framework\Config\ScopeInterface;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Class MerchantDataBuilderTest
 */
class MerchantDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    const STORE_ID = 10;

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
     * Run test for build method
     *
     * @param string $scope
     * @param string $isMultidomain
     * @param string $areaPrefix
     * @return void
     * @dataProvider buildSuccessDataProvider
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testBuildSuccess(string $scope, string $isMultidomain, string $areaPrefix)
    {
        $accessKeyValue = MerchantDataBuilder::ACCESS_KEY. '-value';
        $profileIdValue = MerchantDataBuilder::PROFILE_ID. '-value';

        $this->scope->method('getCurrentScope')
            ->willReturn($scope);

        $this->configMock->expects($this->at(0))
            ->method('getValue')
            ->with('is_multidomain', self::STORE_ID)
            ->willReturn($isMultidomain);

        $this->configMock->expects($this->at(1))
            ->method('getValue')
            ->with($areaPrefix . MerchantDataBuilder::ACCESS_KEY, self::STORE_ID)
            ->willReturn($accessKeyValue);
        $this->configMock->expects($this->at(2))
            ->method('getValue')
            ->with($areaPrefix . MerchantDataBuilder::PROFILE_ID, self::STORE_ID)
            ->willReturn($profileIdValue);

        $result = $this->merchantDataBuilder->build(['payment' => $this->getPaymentMock()]);

        $this->assertArrayHasKey(MerchantDataBuilder::ACCESS_KEY, $result);
        $this->assertArrayHasKey(MerchantDataBuilder::PROFILE_ID, $result);

        $this->assertEquals($result[MerchantDataBuilder::ACCESS_KEY], $accessKeyValue);
        $this->assertEquals($result[MerchantDataBuilder::PROFILE_ID], $profileIdValue);
    }

    /**
     * Dataprovider
     *
     * @return array
     */
    public function buildSuccessDataProvider()
    {
        return [
            ['adminhtml', '1', 'admin_'],
            ['adminhtml', '0', ''],
            ['frontend', '1', ''],
            ['frontend', '0', ''],
        ];
    }

    /**
     * @return \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentMock()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $paymentMock->method('getOrder')
            ->willReturn($this->getOrderMock());

        return $paymentMock;
    }

    /**
     * @return \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getOrderMock()
    {
        $orderMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $orderMock->method('getStoreId')
            ->willReturn(self::STORE_ID);

        return $orderMock;
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
}
