<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Api;

/**
 *  PayPal credentials service interface.
 *
 * @api
 */
interface CredentialsServiceInterface
{

    /**
     * Save PayPal API credentials
     *
     * @param \Magento\PaypalOnBoarding\Api\Data\CredentialsInterface $credentials
     * @param int $websiteId
     * @return bool
     */
    public function save(\Magento\PaypalOnBoarding\Api\Data\CredentialsInterface $credentials, $websiteId);
}
