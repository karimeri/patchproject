<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect;

use Magento\Framework\Message\MessageInterface;
use Magento\PaypalOnBoarding\Model\MagentoMerchantId;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Contains tests for Failure controller with different variations
 */
class FailureTest extends AbstractBackendController
{
    /**
     * @var string
     */
    private static $entryPoint = 'backend/paypal_onboarding/redirect/failure';

    /**
     * @covers \Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect\Failure::execute
     * @magentoAppArea adminhtml
     */
    public function testExecuteWithoutMerchantId()
    {
        $this->dispatch(self::$entryPoint);

        static::assertRedirect(static::stringContains('backend/admin/system_config/edit/section/payment'));
        static::assertSessionMessages(
            static::equalTo(['Wrong merchant signature.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect\Failure::execute
     * @magentoAppArea adminhtml
     */
    public function testExecute()
    {
        /** @var MagentoMerchantId $merchantService */
        $merchantService = $this->_objectManager->get(MagentoMerchantId::class);
        $magentoMerchantId = $merchantService->generate();
        $this->getRequest()->setPostValue('magentoMerchantId', $magentoMerchantId);

        $this->dispatch(self::$entryPoint);

        static::assertRedirect(static::stringContains('backend/admin/system_config/edit/section/payment'));
        static::assertSessionMessages(
            static::equalTo(['We were unable to save PayPal credentials. Please try again later.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @covers \Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect\Failure::execute
     * @param int $website
     * @param string $route
     * @magentoAppArea adminhtml
     * @dataProvider getWebsiteDataProvider
     */
    public function testExecutePerWebsite($website, $route)
    {
        /** @var MagentoMerchantId $merchantService */
        $merchantService = $this->_objectManager->get(MagentoMerchantId::class);
        $magentoMerchantId = $merchantService->generate($website);
        $this->getRequest()->setPostValue('magentoMerchantId', $magentoMerchantId);

        $this->dispatch(self::$entryPoint . '/website/' . $website);

        static::assertRedirect(static::stringContains('backend/admin/system_config/edit' . $route));
    }

    /**
     * Get variations for controller test
     * @return array
     */
    public function getWebsiteDataProvider()
    {
        return [
            ['website' => 0, 'route' => '/section/payment/'],
            ['website' => 1, 'route' => '/section/payment/website/1/'],
            ['website' => 2, 'route' => '/section/payment/website/2/'],
            ['website' => 3, 'route' => '/section/payment/website/3/'],
        ];
    }
}
