<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\PaypalOnBoarding\Model\Button\Button;
use Magento\PaypalOnBoarding\Model\Button\ButtonFactory;
use Magento\PaypalOnBoarding\Model\Button\RequestBuilder;
use Magento\PaypalOnBoarding\Model\Button\RequestCommand;
use Magento\PaypalOnBoarding\Model\MiddlemanService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class MiddlemanServiceTest
 */
class MiddlemanServiceTest extends TestCase
{
    /**
     * @var ButtonFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buttonFactoryMock;

    /**
     * @var RequestBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestButtonBuilderMock;

    /**
     * @var RequestCommand|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestButtonCommandMock;

    /**
     * @var Button|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buttonMock;

    /**
     * @var CacheInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    /**
     * @var MiddlemanService
     */
    private $middlemanService;

    /**
     * @var array $requestParams
     */
    private $requestParams = [
        'countryCode' => 'UK',
        'magentoMerchantId' => 'qwe-rty',
        'successUrl' => 'https://magento.loc/paypal_onboarding/redirect/success',
        'failureUrl' => 'https://magento.loc/paypal_onboarding/redirect/failure'
    ];

    /**
     * @var array $responseFields
     */
    private $responseFields = ['sandboxButtonUrl', 'liveButtonUrl'];

    protected function setUp()
    {
        $this->buttonFactoryMock = $this->getMockBuilder(ButtonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->requestButtonBuilderMock = $this->createMock(RequestBuilder::class);
        $this->requestButtonCommandMock = $this->createMock(RequestCommand::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->buttonMock = $this->createMock(Button::class);

        $this->middlemanService = new MiddlemanService(
            $this->buttonFactoryMock,
            $this->requestButtonBuilderMock,
            $this->cacheMock,
            $this->requestButtonCommandMock,
            $this->scopeConfigMock
        );
    }

    /**
     * Test successful button creation
     *
     * @covers \Magento\PaypalOnBoarding\Model\MiddlemanService::createButton()
     * @param $fromCache
     * @dataProvider createButtonSuccessDataProvider
     */
    public function testCreateButtonSuccess($fromCache)
    {
        $liveButtonUrl = "https://www.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $sandboxButtonUrl = "https://www.sandbox.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $responseBody = json_encode(['liveButtonUrl' => $liveButtonUrl, 'sandboxButtonUrl' => $sandboxButtonUrl]);
        $domain = 'www.middleman.com';
        $host = 'https://' . $domain . '/start';

        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->willReturn($domain);

        $this->requestButtonBuilderMock->expects(static::once())
            ->method('build')
            ->willReturn($this->requestParams);

        $this->cacheMock->expects(static::once())
            ->method('load')
            ->willReturn($fromCache ? $responseBody : $fromCache);

        if ($fromCache === false) {
            $this->requestButtonCommandMock->expects(static::once())
                ->method('execute')
                ->with($host, $this->requestParams, $this->responseFields)
                ->willReturn($responseBody);

            $this->cacheMock->expects(static::once())
                ->method('save')
                ->with($responseBody, sha1(json_encode($this->requestParams) . $host), [], 86400);
        }

        $this->buttonFactoryMock->expects(static::once())
            ->method('create')
            ->with(['sandboxUrl' => $sandboxButtonUrl, 'liveUrl' => $liveButtonUrl])
            ->willReturn($this->buttonMock);

        $this->middlemanService->createButton();
    }

    /**
     * @return array
     */
    public function createButtonSuccessDataProvider()
    {
        return [
            ['fromCache' => true],
            ['fromCache' => false],
        ];
    }

    /**
     * Test button creation with empty response
     *
     * @covers \Magento\PaypalOnBoarding\Model\MiddlemanService::createButton()
     */
    public function testCreateButtonWithoutMiddlemanDomain()
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->willReturn(null);

        $this->cacheMock->expects(static::never())
            ->method('load');

        $this->requestButtonCommandMock->expects(static::never())
            ->method('execute');

        $this->cacheMock->expects(static::never())
            ->method('save');

        $this->buttonFactoryMock->expects(static::once())
            ->method('create')
            ->with(['sandboxUrl' => '', 'liveUrl' => ''])
            ->willReturn($this->buttonMock);

        $this->middlemanService->createButton();
    }

    /**
     * Test button creation with empty response
     *
     * @covers \Magento\PaypalOnBoarding\Model\MiddlemanService::createButton()
     */
    public function testCreateButtonWithEmptyResponse()
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->willReturn('www.middleman.com');

        $this->requestButtonBuilderMock->expects(static::once())
            ->method('build')
            ->willReturn($this->requestParams);

        $this->cacheMock->expects(static::once())
            ->method('load')
            ->willReturn(false);

        $this->requestButtonCommandMock->expects(static::once())
            ->method('execute')
            ->willReturn('');

        $this->cacheMock->expects(static::never())
            ->method('save');

        $this->buttonFactoryMock->expects(static::once())
            ->method('create')
            ->with(['sandboxUrl' => '', 'liveUrl' => ''])
            ->willReturn($this->buttonMock);

        $this->middlemanService->createButton();
    }
}
