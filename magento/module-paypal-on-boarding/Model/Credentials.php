<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model;

use Magento\Framework\DataObject;
use Magento\PaypalOnBoarding\Api\Data\CredentialsInterface;

/**
 * PayPal credentials data object
 */
class Credentials extends DataObject implements CredentialsInterface
{
    /**
     * @inheritdoc
     *
     * @param string $username
     * @return void
     */
    public function setUsername($username)
    {
        $this->setData('username', $username);
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getData('username');
    }

    /**
     * @inheritdoc
     *
     * @param string $password
     * @return void
     */
    public function setPassword($password)
    {
        $this->setData('password', $password);
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->getData('password');
    }

    /**
     * @inheritdoc
     *
     * @param string $signature
     * @return void
     */
    public function setSignature($signature)
    {
        $this->setData('signature', $signature);
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->getData('signature');
    }

    /**
     * @inheritDoc
     */
    public function setMerchantId($merchantId)
    {
        $this->setData('merchant_id', $merchantId);
    }

    /**
     * @inheritDoc
     */
    public function getMerchantId()
    {
        return $this->getData('merchant_id');
    }
}
