<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model\Button;

/**
 * Contains information about PayPal credentials urls
 */
class Button
{
    /**
     * PayPal sandbox url for getting api credentials
     *
     * @var string
     */
    private $sandboxUrl;

    /**
     * PayPal live url for getting api credentials
     *
     * @var string
     */
    private $liveUrl;

    /**
     * @param string $sandboxUrl
     * @param string $liveUrl
     */
    public function __construct($sandboxUrl = '', $liveUrl = '')
    {
        $this->sandboxUrl = $sandboxUrl;
        $this->liveUrl = $liveUrl;
    }

    /**
     * Gets PayPal sandbox url for getting api credentials
     *
     * @return string
     */
    public function getSandboxUrl()
    {
        return $this->sandboxUrl;
    }

    /**
     * Gets PayPal live url for getting api credentials
     *
     * @return string
     */
    public function getLiveUrl()
    {
        return $this->liveUrl;
    }
}
