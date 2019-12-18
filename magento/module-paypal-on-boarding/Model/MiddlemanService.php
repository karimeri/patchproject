<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model;

use Magento\Framework\App\CacheInterface;
use Magento\PaypalOnBoarding\Model\Button\Button;
use Magento\PaypalOnBoarding\Model\Button\ButtonFactory;
use Magento\PaypalOnBoarding\Model\Button\RequestBuilder;
use Magento\PaypalOnBoarding\Model\Button\RequestCommand;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Service for getting info from Middleman application
 */
class MiddlemanService
{
    /**#@+
     * Response data keys
     */
    private static $keySandboxUrl = 'sandboxButtonUrl';
    private static $keyLiveUrl = 'liveButtonUrl';
    /**#@-*/

    /**
     * @var int Response cache save time
     */
    private static $responseCacheTime = 86400;

    /**
     * @var ButtonFactory
     */
    private $buttonFactory;

    /**
     * @var RequestBuilder
     */
    private $requestButtonBuilder;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var RequestCommand
     */
    private $requestButtonCommand;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ButtonFactory $buttonFactory
     * @param RequestBuilder $requestButtonBuilder
     * @param CacheInterface $cache
     * @param RequestCommand $requestButtonCommand
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ButtonFactory $buttonFactory,
        RequestBuilder $requestButtonBuilder,
        CacheInterface $cache,
        RequestCommand $requestButtonCommand,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->buttonFactory = $buttonFactory;
        $this->requestButtonBuilder = $requestButtonBuilder;
        $this->cache = $cache;
        $this->requestButtonCommand = $requestButtonCommand;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Create button info object
     *
     * @return Button
     */
    public function createButton()
    {
        $result = $this->getCredentialUrls();

        /** @var Button $button */
        $button = $this->buttonFactory->create(
            [
                'sandboxUrl' => isset($result[self::$keySandboxUrl]) ? $result[self::$keySandboxUrl] : '',
                'liveUrl' => isset($result[self::$keyLiveUrl]) ? $result[self::$keyLiveUrl] : ''
            ]
        );

        return $button;
    }

    /**
     * Request credential urls
     *
     * @return array
     */
    private function getCredentialUrls()
    {
        $host = $this->getHost();
        if (empty($host)) {
            return [];
        }

        $requestParams = $this->requestButtonBuilder->build();
        $cacheId = sha1(json_encode($requestParams) . $host);
        $result = $this->cache->load($cacheId);
        if (false === $result) {
            $result = $this->requestButtonCommand->execute(
                $host,
                $requestParams,
                [self::$keySandboxUrl, self::$keyLiveUrl]
            );
            if (!empty($result)) {
                $this->cache->save($result, $cacheId, [], self::$responseCacheTime);
            }
        }

        $result = json_decode($result, true);

        return (array)$result;
    }

    /**
     * Get middleman application host
     *
     * @return string
     */
    private function getHost()
    {
        $domain = $this->scopeConfig->getValue(
            'paypal_onboarding/middleman_domain',
            ScopeInterface::SCOPE_STORE
        );

        return !empty($domain) ? sprintf('https://%s/start', $domain) : '';
    }
}
