<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect;

use Magento\Config\Model\Config;
use Magento\Framework\Message\MessageInterface;
use Magento\Paypal\Model\Config as PaypalConfig;
use Magento\PaypalOnBoarding\Model\CredentialsService;
use Magento\PaypalOnBoarding\Model\MagentoMerchantId;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Contains tests for Success controller with different variations
 */
class SuccessTest extends AbstractBackendController
{
    /**
     * @var string
     */
    private static $entryPoint = 'backend/paypal_onboarding/redirect/success';

    /**
     * @var string
     */
    private static $configPath = 'backend/admin/system_config/edit/section/payment';

    /**
     * @var string
     */
    private static $userName = 'username';

    /**
     * @var string
     */
    private static $userPassword = 'password';

    /**
     * @var string
     */
    private static $signature = 'signature';

    /**
     * @var string
     */
    private static $paypalMerchantId = '43V9GN4SHXNX4';

    /**
     * @var PaypalConfig
     */
    private $config;

    /**
     * @magentoAppArea adminhtml
     */
    public function testExecuteWithCredentialsSaveFailing()
    {
        $errorMessage = 'DB error';
        $credentialsServiceMock = $this->getMockBuilder(CredentialsService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $credentialsServiceMock->expects(static::once())
            ->method('save')
            ->willThrowException(new \Exception($errorMessage));
        $this->_objectManager->addSharedInstance($credentialsServiceMock, CredentialsService::class);
        /** @var MagentoMerchantId $merchantService */
        $merchantService = $this->_objectManager->get(MagentoMerchantId::class);
        $magentoMerchantId = $merchantService->generate();
        $this->getRequest()->setPostValue('magentoMerchantId', $magentoMerchantId);
        $this->getRequest()->setPostValue('username', self::$userName);
        $this->getRequest()->setPostValue('password', self::$userPassword);
        $this->getRequest()->setPostValue('signature', self::$signature);
        $this->getRequest()->setPostValue('paypalMerchantId', self::$paypalMerchantId);

        $this->dispatch(self::$entryPoint);

        self::assertRedirect(static::stringContains(self::$configPath));
        self::assertSessionMessages(
            self::equalTo(['Something went wrong while saving credentials: ' . $errorMessage]),
            MessageInterface::TYPE_ERROR
        );
        $this->_objectManager->removeSharedInstance(CredentialsService::class);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testExecuteWithFakeWebsiteId()
    {
        $originWebsiteId = 1;
        $fakeWebsiteId = 2;

        /** @var MagentoMerchantId $merchantService */
        $merchantService = $this->_objectManager->get(MagentoMerchantId::class);
        $magentoMerchantId = $merchantService->generate($originWebsiteId);
        $this->getRequest()->setPostValue('magentoMerchantId', $magentoMerchantId);
        $this->getRequest()->setPostValue('username', self::$userName);
        $this->getRequest()->setPostValue('password', self::$userPassword);
        $this->getRequest()->setPostValue('signature', self::$signature);
        $this->getRequest()->setPostValue('paypalMerchantId', self::$paypalMerchantId);
        $this->getRequest()->setPostValue('website', $fakeWebsiteId);

        $this->dispatch(self::$entryPoint);

        self::assertRedirect(static::stringContains(self::$configPath));
        self::assertSessionMessages(
            self::equalTo(['Wrong merchant signature']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * Checks success credentials storing for different websites with different merchant country.
     *
     * @param int $website
     * @param int $countryCode
     * @magentoAppArea adminhtml
     * @dataProvider getWebsiteDataProvider
     */
    public function testExecuteSuccess(int $website, string $countryCode)
    {
        /** @var Config $config */
        $config = $this->_objectManager->get(Config::class);
        $config->setDataByPath('paypal/general/merchant_country', $countryCode);
        $config->save();

        $this->config = $this->_objectManager->get(PaypalConfig::class);
        $merchantService = $this->_objectManager->get(MagentoMerchantId::class);
        $magentoMerchantId = $merchantService->generate($website);

        $this->getRequest()->setPostValue('magentoMerchantId', $magentoMerchantId);
        $this->getRequest()->setPostValue('username', self::$userName);
        $this->getRequest()->setPostValue('password', self::$userPassword);
        $this->getRequest()->setPostValue('signature', self::$signature);
        $this->getRequest()->setPostValue('paypalMerchantId', self::$paypalMerchantId);

        $this->dispatch(self::$entryPoint . '/website/' . $website);

        // Assert for saved data

        $this->config->setMethodCode(PaypalConfig::METHOD_EXPRESS);

        self::assertEquals($this->config->getValue('api_username', $website), self::$userName);
        self::assertEquals($this->config->getValue('api_password', $website), self::$userPassword);
        self::assertEquals($this->config->getValue('api_signature', $website), self::$signature);
        self::assertEquals($this->config->getValue('merchant_id', $website), self::$paypalMerchantId);

        self::assertRedirect(static::stringContains(self::$configPath));

        // Assert for success session message
        self::assertSessionMessages(
            self::equalTo(['You saved PayPal credentials. Please enable PayPal Express Checkout.']),
            MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * Get variations for controller test
     * @return array
     */
    public function getWebsiteDataProvider()
    {
        return [
            ['website' => 0, 'countryCode' => 'AR'],
            ['website' => 1, 'countryCode' => 'AU'],
            ['website' => 0, 'countryCode' => 'AT'],
            ['website' => 0, 'countryCode' => 'BE'],
            ['website' => 0, 'countryCode' => 'BR'],
            ['website' => 0, 'countryCode' => 'BG'],
            ['website' => 0, 'countryCode' => 'CA'],
            ['website' => 0, 'countryCode' => 'CL'],
            ['website' => 0, 'countryCode' => 'CN'],
            ['website' => 0, 'countryCode' => 'CR'],
            ['website' => 0, 'countryCode' => 'CY'],
            ['website' => 0, 'countryCode' => 'CZ'],
            ['website' => 0, 'countryCode' => 'DK'],
            ['website' => 0, 'countryCode' => 'DO'],
            ['website' => 0, 'countryCode' => 'EC'],
            ['website' => 0, 'countryCode' => 'EE'],
            ['website' => 0, 'countryCode' => 'FI'],
            ['website' => 0, 'countryCode' => 'FR'],
            ['website' => 0, 'countryCode' => 'GF'],
            ['website' => 1, 'countryCode' => 'DE'],
            ['website' => 0, 'countryCode' => 'GI'],
            ['website' => 0, 'countryCode' => 'GR'],
            ['website' => 0, 'countryCode' => 'GP'],
            ['website' => 0, 'countryCode' => 'HK'],
            ['website' => 0, 'countryCode' => 'HU'],
            ['website' => 0, 'countryCode' => 'IS'],
            ['website' => 0, 'countryCode' => 'IN'],
            ['website' => 0, 'countryCode' => 'ID'],
            ['website' => 0, 'countryCode' => 'IE'],
            ['website' => 0, 'countryCode' => 'IS'],
            ['website' => 0, 'countryCode' => 'IT'],
            ['website' => 0, 'countryCode' => 'JM'],
            ['website' => 0, 'countryCode' => 'JP'],
            ['website' => 0, 'countryCode' => 'LV'],
            ['website' => 0, 'countryCode' => 'LI'],
            ['website' => 0, 'countryCode' => 'LT'],
            ['website' => 0, 'countryCode' => 'LU'],
            ['website' => 0, 'countryCode' => 'MY'],
            ['website' => 0, 'countryCode' => 'MT'],
            ['website' => 0, 'countryCode' => 'MQ'],
            ['website' => 0, 'countryCode' => 'MX'],
            ['website' => 0, 'countryCode' => 'NL'],
            ['website' => 0, 'countryCode' => 'NZ'],
            ['website' => 0, 'countryCode' => 'NO'],
            ['website' => 0, 'countryCode' => 'PH'],
            ['website' => 0, 'countryCode' => 'PL'],
            ['website' => 0, 'countryCode' => 'PT'],
            ['website' => 0, 'countryCode' => 'RE'],
            ['website' => 0, 'countryCode' => 'RO'],
            ['website' => 0, 'countryCode' => 'RU'],
            ['website' => 0, 'countryCode' => 'SM'],
            ['website' => 0, 'countryCode' => 'SG'],
            ['website' => 0, 'countryCode' => 'SK'],
            ['website' => 0, 'countryCode' => 'SI'],
            ['website' => 0, 'countryCode' => 'ZA'],
            ['website' => 0, 'countryCode' => 'KR'],
            ['website' => 0, 'countryCode' => 'ES'],
            ['website' => 0, 'countryCode' => 'SE'],
            ['website' => 0, 'countryCode' => 'CH'],
            ['website' => 0, 'countryCode' => 'TW'],
            ['website' => 0, 'countryCode' => 'TH'],
            ['website' => 0, 'countryCode' => 'TR'],
            ['website' => 0, 'countryCode' => 'AE'],
            ['website' => 1, 'countryCode' => 'GB'],
            ['website' => 0, 'countryCode' => 'US'],
            ['website' => 0, 'countryCode' => 'UY'],
            ['website' => 0, 'countryCode' => 'VE'],
            ['website' => 0, 'countryCode' => 'VN']
        ];
    }
}
