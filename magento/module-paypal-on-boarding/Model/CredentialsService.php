<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model;

use Magento\Config\Model\Config\Factory as ConfigFactory;
use Magento\PaypalOnBoarding\Api\CredentialsServiceInterface;
use Magento\PaypalOnBoarding\Api\Data\CredentialsInterface;
use Magento\Paypal\Model\Config as PaypalConfig;

/**
 * PayPal credentials service
 */
class CredentialsService implements CredentialsServiceInterface
{
    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var PaypalConfig
     */
    private $config;

    /**
     * @param ConfigFactory $configFactory
     * @param PaypalConfig $config
     */
    public function __construct(ConfigFactory $configFactory, PaypalConfig $config)
    {
        $this->configFactory = $configFactory;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     *
     * @param CredentialsInterface $credentials
     * @param int|null $websiteId
     * @return bool
     * @throws \Exception
     */
    public function save(CredentialsInterface $credentials, $websiteId)
    {
        $configData = [
            'section' => 'payment',
            'website' => $websiteId,
            'store' => null,
            'groups' => $this->getGroupsForSave($credentials),
        ];
        /** @var \Magento\Config\Model\Config $configModel  */
        $configModel = $this->configFactory->create(['data' => $configData]);
        $configModel->save();

        return true;
    }

    /**
     * Prepare groups data for save
     *
     * @param CredentialsInterface $credentials
     * @return array
     */
    private function getGroupsForSave(CredentialsInterface $credentials)
    {
        $merchantCountry = $this->config->getMerchantCountry();

        if ($this->isAlternativeSolution($merchantCountry)) {
            return [
                'paypal_alternative_payment_methods' => [
                    'groups' => [
                        'express_checkout_' . strtolower($merchantCountry) => [
                            'groups' => $this->getBaseStructure($credentials)
                        ]
                    ]
                ]
            ];
        }

        // Germany has specific groups structure
        if ($merchantCountry === 'DE') {
            return [
                'paypal_payment_solutions' => [
                    'groups' => [
                        'express_checkout_' . strtolower($merchantCountry) => [
                            'groups' => $this->getBaseStructure($credentials)
                        ]
                    ]
                ]
            ];
        }

        return [
            'express_checkout_other' => [
                'groups' => $this->getBaseStructure($credentials)
            ]
        ];
    }

    /**
     * Gets config base structure.
     *
     * @param CredentialsInterface $credentials
     * @return array
     */
    private function getBaseStructure(CredentialsInterface $credentials)
    {
        return [
            'express_checkout_required' => [
                'groups' => [
                    'express_checkout_required_express_checkout' => [
                        'fields' => [
                            'api_username' => ['value' => $credentials->getUsername()],
                            'api_password' => ['value' => $credentials->getPassword()],
                            'api_signature' => ['value' => $credentials->getSignature()],
                        ]
                    ]
                ],
                'fields' => [
                    'merchant_id' => ['value' => $credentials->getMerchantId()],
                ],
            ]
        ];
    }

    /**
     * Checks if country code in list of alternative solutions.
     * PayPal has different config structure for different countries.
     *
     * @param $countryCode
     * @return bool
     */
    private function isAlternativeSolution($countryCode)
    {
        return in_array($countryCode, ['US', 'GB']);
    }
}
