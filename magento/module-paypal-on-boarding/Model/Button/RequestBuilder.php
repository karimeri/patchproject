<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model\Button;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Paypal\Model\Config as PaypalConfig;
use Magento\PaypalOnBoarding\Model\MagentoMerchantId;

/**
 * Class RequestBuilder
 */
class RequestBuilder
{
    /**
     * Country code key
     */
    private static $countryCodeKey = 'countryCode';

    /**
     * Unique merchant session identifier key, used as request signature
     */
    private static $magentoMerchantIdKey = 'magentoMerchantId';

    /**
     * Success url return key
     */
    private static $successUrlKey = 'successUrl';

    /**
     * Failure url return key
     */
    private static $failureUrlKey = 'failureUrl';

    /**
     * @var PaypalConfig
     */
    private $paypalConfig;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var MagentoMerchantId
     */
    private $magentoMerchantId;

    /**
     * @param PaypalConfig $paypalConfig
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param MagentoMerchantId $magentoMerchantId
     */
    public function __construct(
        PaypalConfig $paypalConfig,
        UrlInterface $urlBuilder,
        RequestInterface $request,
        MagentoMerchantId $magentoMerchantId
    ) {
        $this->paypalConfig = $paypalConfig;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->magentoMerchantId = $magentoMerchantId;
    }

    /**
     * @return array
     */
    public function build()
    {
        $website = $this->request->getParam('website');

        return [
            self::$countryCodeKey => $this->paypalConfig->getMerchantCountry(),
            self::$magentoMerchantIdKey => $this->magentoMerchantId->generate($website),
            self::$successUrlKey => $this->urlBuilder->getUrl(
                'paypal_onboarding/redirect/success',
                ['website' => $website]
            ),
            self::$failureUrlKey => $this->urlBuilder->getUrl(
                'paypal_onboarding/redirect/failure',
                ['website' => $website]
            )
        ];
    }
}
