<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\UrlInterface;

/**
 * Generate merchant session id
 */
class MagentoMerchantId
{
    /**
     * Array key of encryption key in deployment config
     */
    private static $paramCryptKey = 'crypt/key';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param UrlInterface $urlBuilder
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        UrlInterface $urlBuilder,
        DeploymentConfig $deploymentConfig
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Generate merchant session id
     *
     * @param int|null $websiteId
     * @return string
     */
    public function generate($websiteId = null)
    {
        return sha1(
            $this->urlBuilder->getBaseUrl() . $this->getLatestCryptKey() . ($websiteId ?: '')
        );
    }

    /**
     * Get latest crypt key
     *
     * @return string
     */
    private function getLatestCryptKey()
    {
        $keys = preg_split('/\s+/s', trim($this->deploymentConfig->get(self::$paramCryptKey)));

        return end($keys);
    }
}
