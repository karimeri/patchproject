<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Api\Data;

/**
 * PayPal credentials interface.
 * @api
 */
interface CredentialsInterface
{
    /**
     * Set API username
     *
     * @param string $username
     * @return void
     */
    public function setUsername($username);

    /**
     * Get API username
     *
     * @return string
     */
    public function getUsername();

    /**
     * Set API password
     *
     * @param string $password
     * @return void
     */
    public function setPassword($password);

    /**
     * Get API password
     *
     * @return string
     */
    public function getPassword();

    /**
     * Set API signature
     *
     * @param string $signature
     * @return void
     */
    public function setSignature($signature);

    /**
     * Get API signature
     *
     * @return string
     */
    public function getSignature();

    /**
     * Set PayPal merchant id
     *
     * @param string $merchantId
     * @return void
     */
    public function setMerchantId($merchantId);

    /**
     * Get PayPal merchant id
     *
     * @return string
     */
    public function getMerchantId();
}
