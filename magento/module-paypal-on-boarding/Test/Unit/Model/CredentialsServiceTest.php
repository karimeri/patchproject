<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model;

use Magento\Config\Model\Config;
use Magento\Config\Model\Config\Factory;
use Magento\Paypal\Model\Config as PaypalConfig;
use Magento\PaypalOnBoarding\Model\Credentials;
use Magento\PaypalOnBoarding\Model\CredentialsService;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Contains tests for Credentials service
 */
class CredentialsServiceTest extends TestCase
{
    /**
     * @var Factory|MockObject
     */
    private $configFactory;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var PaypalConfig|MockObject
     */
    private $payPalConfig;

    /**
     * @var CredentialsService
     */
    private $credentialsService;

    protected function setUp()
    {
        $this->configFactory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();

        $this->payPalConfig = $this->getMockBuilder(PaypalConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->credentialsService = new CredentialsService($this->configFactory, $this->payPalConfig);
    }

    /**
     * Checks a config structure for different merchant countries.
     *
     * @param $merchantCountry
     * @param $username
     * @param $password
     * @param $signature
     * @param $merchantId
     * @param $websiteId
     * @param array $expected
     * @throws \Exception
     * @dataProvider configDataProvider
     */
    public function testSave(
        $merchantCountry,
        $username,
        $password,
        $signature,
        $merchantId,
        $websiteId,
        array $expected
    ) {

        $this->payPalConfig->method('getMerchantCountry')
            ->willReturn($merchantCountry);
        $this->configFactory->method('create')
            ->with(['data' => $expected])
            ->willReturn($this->config);

        $this->config->method('save')
            ->willReturnSelf();

        $credentials = new Credentials([
            'username' => $username,
            'password' => $password,
            'signature' => $signature,
            'merchant_id' => $merchantId
        ]);

        $result = $this->credentialsService->save($credentials, $websiteId);

        self::assertTrue($result);
    }

    /**
     * Gets list of variations.
     *
     * @return array
     */
    public function configDataProvider()
    {
        $username = 'merchant';
        $password = 'querty123';
        $signature = 'e1yu0djs4j2ls';
        $merchantId = '43V9GN4SHXNX4';
        $websiteId = 1;
        $config = [
            'groups' => [
                'express_checkout_required' => [
                    'groups' => [
                        'express_checkout_required_express_checkout' => [
                            'fields' => [
                                'api_username' => ['value' => $username],
                                'api_password' => ['value' => $password],
                                'api_signature' => ['value' => $signature],
                            ]
                        ]
                    ],
                    'fields' => [
                        'merchant_id' => ['value' => $merchantId],
                    ],
                ]
            ]
        ];
        return [
            [
                'merchantCountry' => 'GB',
                'username' => $username,
                'password' => $password,
                'signature' => $signature,
                'merchantId' => $merchantId,
                'websiteId' => $websiteId,
                'expected' => [
                    'section' => 'payment',
                    'website' => $websiteId,
                    'store' => null,
                    'groups' => [
                        'paypal_alternative_payment_methods' => [
                            'groups' => [
                                'express_checkout_gb' => $config
                            ]
                        ]
                    ]
                ]
            ],
            [
                'merchantCountry' => 'CA',
                'username' => $username,
                'password' => $password,
                'signature' => $signature,
                'merchantId' => $merchantId,
                'websiteId' => $websiteId,
                'expected' => [
                    'section' => 'payment',
                    'website' => $websiteId,
                    'store' => null,
                    'groups' => [
                        'express_checkout_other' => $config
                    ]
                ]
            ]
        ];
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Save config exception
     */
    public function testSaveWithException()
    {
        $username = 'merchant';
        $password = 'querty123';
        $signature = 'e1yu0djs4j2ls';
        $merchantId = '43V9GN4SHXNX4';
        $websiteId = 1;

        $this->configFactory->method('create')
            ->willReturn($this->config);

        $this->config->method('save')
            ->willThrowException(new \Exception('Save config exception'));

        $credentials = new Credentials([
            'username' => $username,
            'password' => $password,
            'signature' => $signature,
            'merchant_id' => $merchantId
        ]);

        $this->credentialsService->save($credentials, $websiteId);
    }
}
