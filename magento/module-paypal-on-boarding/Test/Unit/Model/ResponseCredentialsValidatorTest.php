<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model;

use Magento\PaypalOnBoarding\Model\MagentoMerchantId;
use Magento\PaypalOnBoarding\Model\ResponseCredentialsValidator;
use PHPUnit\Framework\TestCase;

/**
 * Class ResponseCredentialsValidatorTest
 */
class ResponseCredentialsValidatorTest extends TestCase
{
    /**
     * @var MagentoMerchantId|\PHPUnit_Framework_MockObject_MockObject
     */
    private $magentoMerchantIdMock;

    /**
     * @var ResponseCredentialsValidator
     */
    private $responseCredentialsValidator;

    protected function setUp()
    {
        $this->magentoMerchantIdMock = $this->createMock(MagentoMerchantId::class);

        $this->responseCredentialsValidator = new ResponseCredentialsValidator($this->magentoMerchantIdMock);
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Model\ResponseCredentialsValidator::validate()
     */
    public function testValidateSuccess()
    {
        $magentoMerchantId = 'qweasd';
        $websiteId = 1;
        $data = [
            'username' => 'username',
            'password' => 'password',
            'signature' => 'signature',
            'website' => $websiteId,
            'magentoMerchantId' => $magentoMerchantId
        ];
        $this->magentoMerchantIdMock->expects(static::once())
            ->method('generate')
            ->with($websiteId)
            ->willReturn($magentoMerchantId);

        $this->assertTrue(
            $this->responseCredentialsValidator->validate(
                $data,
                ['username', 'password', 'signature', 'magentoMerchantId']
            )
        );
    }

    /**
     * @param array $data response params
     * @dataProvider validateFailDataProvider
     * @expectedException \Magento\Framework\Exception\ValidatorException
     */
    public function testValidateFail(array $data)
    {
        $this->magentoMerchantIdMock->expects(static::any())
            ->method('generate')
            ->willReturn('right signature');

        $this->responseCredentialsValidator->validate(
            $data,
            ['username', 'password', 'signature', 'magentoMerchantId']
        );
    }

    public function validateFailDataProvider()
    {
        return [
            ['Required param missing' =>
                [
                    'username' => 'username',
                    'password' => 'password'
                ]
            ],
            ['Wrong merchant signature' =>
                [
                    'username' => 'username',
                    'password' => 'password',
                    'signature' => 'signature',
                    'magentoMerchantId' => 'wrong signature'
                ]
            ],
        ];
    }
}
