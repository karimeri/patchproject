<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model;

use Magento\Framework\Exception\ValidatorException;

/**
 * Validates response with credentials from PayPal Middleman application
 */
class ResponseCredentialsValidator
{
    /**
     * @var MagentoMerchantId
     */
    private $magentoMerchantId;

    /**
     * @param MagentoMerchantId $magentoMerchantId
     */
    public function __construct(
        MagentoMerchantId $magentoMerchantId
    ) {
        $this->magentoMerchantId = $magentoMerchantId;
    }

    /**
     * Validate response for errors
     *
     * @param array $data
     * @param array $fieldsToCheck fields should not be empty
     * @return bool
     * @throws ValidatorException
     */
    public function validate(array $data, array $fieldsToCheck = [])
    {
        $emptyFields = array_filter($fieldsToCheck, function ($field) use ($data) {
            return empty($data[$field]);
        });
        if (count($emptyFields) > 0) {
            throw new ValidatorException(
                __('Next fields are missed: %1', implode(',', $emptyFields))
            );
        }

        $websiteId = isset($data['website']) ? $data['website'] : null;
        if ($data['magentoMerchantId'] !== $this->magentoMerchantId->generate($websiteId)) {
            throw new ValidatorException(
                __('Wrong merchant signature')
            );
        }

        return true;
    }
}
