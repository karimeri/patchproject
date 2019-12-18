<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\UrlInterface;
use Magento\PaypalOnBoarding\Model\MagentoMerchantId;
use PHPUnit\Framework\TestCase;

/**
 * Class MagentoMerchantIdTest
 */
class MagentoMerchantIdTest extends TestCase
{
    /**
     * @var UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    /**
     * @var DeploymentConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deploymentConfigMock;

    /**
     * @var MagentoMerchantId
     */
    private $magentoMerchantId;

    protected function setUp()
    {
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);

        $this->magentoMerchantId = new MagentoMerchantId(
            $this->urlBuilderMock,
            $this->deploymentConfigMock
        );
    }

    public function testGenerate()
    {
        $website = 1;
        $baseUrl = 'http://test.url';
        $cryptKey = 'dfgbvrtasdf';
        $magentoMerchantId = sha1($baseUrl . $cryptKey . $website);

        $this->urlBuilderMock->expects(static::once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $this->deploymentConfigMock->expects(static::once())
            ->method('get')
            ->willReturn($cryptKey);

        $this->assertEquals($magentoMerchantId, $this->magentoMerchantId->generate($website));
    }
}
