<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model\Button;

use Magento\PaypalOnBoarding\Model\Button\ResponseValidator;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseValidatorTest
 */
class ResponseValidatorTest extends TestCase
{
    /**
     * @var \Zend_Http_Response|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseMock;

    /**
     * @var \Magento\PaypalOnBoarding\Model\Button\ResponseValidator
     */
    private $responseButtonValidator;

    protected function setUp()
    {
        $this->responseMock = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBody', 'getStatus'])
            ->getMock();

        $this->responseButtonValidator = new ResponseValidator();
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Model\Button\ResponseValidator::validate()
     */
    public function testValidateSuccess()
    {
        $this->responseMock->expects(static::once())
            ->method('getStatus')
            ->willReturn(200);

        $this->responseMock->expects(static::once())
            ->method('getBody')
            ->willReturn(json_encode(['sandboxButtonUrl' => 'sandboxUrl', 'liveButtonUrl' => 'liveUrl']));

        $this->assertTrue(
            $this->responseButtonValidator->validate(
                $this->responseMock,
                ['sandboxButtonUrl', 'liveButtonUrl']
            )
        );
    }

    /**
     * @param string $responseCode response code
     * @param string $responseBody response body
     * @dataProvider validateFailDataProvider
     * @expectedException \Magento\Framework\Exception\ValidatorException
     */
    public function testValidateFail($responseCode, $responseBody)
    {
        $this->responseMock->expects(static::once())
            ->method('getStatus')
            ->willReturn($responseCode);

        $this->responseMock->expects(static::once())
            ->method('getBody')
            ->willReturn($responseBody);

        $this->responseButtonValidator->validate(
            $this->responseMock,
            ['sandboxButtonUrl', 'liveButtonUrl']
        );
    }

    public function validateFailDataProvider()
    {
        return [
            [400, json_encode(['sandboxButtonUrl' => 'sandboxUrl', 'liveButtonUrl' => 'liveUrl'])],
            [200, 'Not JSON string'],
            [200, json_encode(['wrongField' => 'wrongValue'])],
            [200, json_encode(['sandboxButtonUrl' => 'sandboxUrl'])],
        ];
    }
}
