<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model\Button;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\HTTP\ZendClient;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\PaypalOnBoarding\Model\Button\ResponseValidator;
use Magento\PaypalOnBoarding\Model\Button\RequestCommand;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestCommandTest
 */
class RequestCommandTest extends TestCase
{
    /**
     * @var ZendClientFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientFactoryMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ResponseValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseButtonValidatorMock;

    /**
     * @var RequestCommand
     */
    private $requestCommand;

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
     * @var string
     */
    private $host = 'https://middleman.com/start';

    /**
     * @var array $responseFields
     */
    private $responseFields = ['liveButtonUrl', 'sandboxButtonUrl'];

    protected function setUp()
    {
        $this->clientFactoryMock = $this->getMockBuilder(ZendClientFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->responseButtonValidatorMock = $this->createMock(ResponseValidator::class);

        $this->requestCommand = new RequestCommand(
            $this->clientFactoryMock,
            $this->responseButtonValidatorMock,
            $this->loggerMock
        );
    }

    /**
     * Test successful request
     *
     * @covers \Magento\PaypalOnBoarding\Model\Button\RequestCommand::execute()
     */
    public function testExecuteSuccess()
    {

        $liveButtonUrl = "https://www.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $sandboxButtonUrl = "https://www.sandbox.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $middlemanResponse = json_encode(['liveButtonUrl' => $liveButtonUrl, 'sandboxButtonUrl' => $sandboxButtonUrl]);

        $httpClient = $this->getHttpClientMock();
        $this->clientFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($httpClient);

        $response = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBody'])
            ->getMock();

        $response->expects(static::once())
            ->method('getBody')
            ->willReturn($middlemanResponse);

        $httpClient->expects(static::once())
            ->method('request')
            ->willReturn($response);

        $this->responseButtonValidatorMock->expects(static::once())
            ->method('validate')
            ->with($response, $this->responseFields)
            ->willReturn(true);

        $this->requestCommand->execute($this->host, $this->requestParams, $this->responseFields);
    }

    /**
     * Request fails due to \Zend_Http_Client_Exception
     */
    public function testExecuteWithZendHttpClientException()
    {
        $httpClient = $this->getHttpClientMock();
        $this->clientFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($httpClient);
        $httpClient->expects(static::once())
            ->method('request')
            ->willThrowException(new \Zend_Http_Client_Exception());

        $this->responseButtonValidatorMock->expects(static::never())
            ->method('validate');

        $this->loggerMock->expects(static::once())
            ->method('error');

        $this->requestCommand->execute($this->host, $this->requestParams, $this->responseFields);
    }

    /**
     * Request fails due to ValidatorException
     */
    public function testExecuteWithValidatorException()
    {
        $httpClient = $this->getHttpClientMock();
        $this->clientFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($httpClient);

        $response = $this->createMock(\Zend_Http_Response::class);
        $httpClient->expects(static::once())
            ->method('request')
            ->willReturn($response);

        $this->responseButtonValidatorMock->expects(static::once())
            ->method('validate')
            ->with($response, $this->responseFields)
            ->willThrowException(new ValidatorException(
                __('error')
            ));

        $this->loggerMock->expects(static::once())
            ->method('error');

        $this->requestCommand->execute($this->host, $this->requestParams, $this->responseFields);
    }

    /**
     * Return ZendClient mock
     *
     * @return ZendClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getHttpClientMock()
    {
        /** @var ZendClient|\PHPUnit_Framework_MockObject_MockObject $httpClient */
        $httpClient = $this->getMockBuilder(ZendClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['setUri', 'setParameterGet', 'request'])
            ->getMock();

        $httpClient->expects(static::once())
            ->method('setUri')
            ->with($this->host);
        $httpClient->expects(static::once())
            ->method('setParameterGet')
            ->with($this->requestParams);

        return $httpClient;
    }
}
