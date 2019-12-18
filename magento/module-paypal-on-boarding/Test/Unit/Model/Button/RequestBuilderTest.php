<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model\Button;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Paypal\Model\Config as PaypalConfig;
use Magento\PaypalOnBoarding\Model\MagentoMerchantId;
use Magento\PaypalOnBoarding\Model\Button\RequestBuilder;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestBuilderTest
 */
class RequestBuilderTest extends TestCase
{
    /**
     * @var PaypalConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paypalConfigMock;

    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var RequestBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestButtonBuilder;

    /**
     * @var MagentoMerchantId|MockObject
     */
    private $magentoMerchantId;

    protected function setUp()
    {
        $this->paypalConfigMock = $this->createMock(PaypalConfig::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->requestMock = $this->createMock(RequestInterface::class);

        $this->magentoMerchantId = $this->getMockBuilder(MagentoMerchantId::class)
            ->disableOriginalConstructor()
            ->setMethods(['generate'])
            ->getMock();

        $this->requestButtonBuilder = new RequestBuilder(
            $this->paypalConfigMock,
            $this->urlBuilderMock,
            $this->requestMock,
            $this->magentoMerchantId
        );
    }

    public function testBuild()
    {
        $website = 1;
        $merchantCountry = 'UK';
        $magentoMerchantId = 'qwe-rty';
        $successUrl = 'paypal_onboarding/redirect/success';
        $failureUrl = 'paypal_onboarding/redirect/failure';

        $this->requestMock->expects(static::once())
            ->method('getParam')
            ->with('website')
            ->willReturn($website);

        $this->paypalConfigMock->expects(static::once())
            ->method('getMerchantCountry')
            ->willReturn($merchantCountry);

        $this->magentoMerchantId->expects(static::once())
            ->method('generate')
            ->with($website)
            ->willReturn($magentoMerchantId);

        $this->urlBuilderMock->expects(static::exactly(2))
            ->method('getUrl')
            ->willReturnMap([
                [$successUrl, ['website' => $website], 'successUrl'],
                [$failureUrl, ['website' => $website], 'failureUrl']
            ]);

        $expected = [
            'countryCode' => $merchantCountry,
            'magentoMerchantId' => $magentoMerchantId,
            'successUrl' => 'successUrl',
            'failureUrl' => 'failureUrl'
        ];

        $this->assertEquals(
            $expected,
            $this->requestButtonBuilder->build()
        );
    }
}
