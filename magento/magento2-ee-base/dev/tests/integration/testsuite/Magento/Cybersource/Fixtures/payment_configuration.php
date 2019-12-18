<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Config\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var EncryptorInterface $encryptor */
$encryptor = $objectManager->get(EncryptorInterface::class);

$processConfigData = function (Config $config, array $data) {
    foreach ($data as $key => $value) {
        $config->setDataByPath($key, $value);
        $config->save();
    }
};

// save payment configuration for the default scope
$configData = [
    'payment/cybersource/access_key' => $encryptor->encrypt('def_access_key'),
    'payment/cybersource/profile_id' => $encryptor->encrypt('def_profile_id'),
];
/** @var Config $defConfig */
$defConfig = $objectManager->create(Config::class);
$defConfig->setScope(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
$processConfigData($defConfig, $configData);

// save payment website config data
require __DIR__ . '/../../Store/_files/second_website_with_two_stores.php';

$websiteConfigData = [
    'payment/cybersource/access_key' => $encryptor->encrypt('website_access_key'),
    'payment/cybersource/profile_id' => $encryptor->encrypt('website_profile_id'),
];
/** @var Config $websiteConfig */
$websiteConfig = $objectManager->create(Config::class);
$websiteConfig->setScope(ScopeInterface::SCOPE_WEBSITES);
$websiteConfig->setWebsite($websiteId);
$processConfigData($websiteConfig, $websiteConfigData);
