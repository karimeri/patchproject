<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model\Button;

use Magento\Framework\Exception\ValidatorException;

/**
 * Validates button request response
 */
class ResponseValidator
{
    /**
     * Validate response for errors
     *
     * @param \Zend_Http_Response $response
     * @param array $fieldsToCheck fields should not be empty
     * @return bool
     * @throws ValidatorException
     */
    public function validate(\Zend_Http_Response $response, array $fieldsToCheck = [])
    {
        $status = $response->getStatus();
        $body = $response->getBody();

        if ($status != 200) {
            throw new ValidatorException(
                __(
                    'PayPal On-boarding urls request; Response code: %1; Response body: %2',
                    $status,
                    $body
                )
            );
        }

        $data = json_decode($body, true);
        if (null === $data) {
            throw new ValidatorException(
                __('PayPal On-boarding urls response format is not JSON; Response body: %1', $body)
            );
        }

        $emptyFields = array_filter($fieldsToCheck, function ($field) use ($data) {
            return empty($data[$field]);
        });
        if (count($emptyFields) > 0) {
            throw new ValidatorException(
                __('PayPal On-boarding urls response should contain next fields: %1', implode(',', $emptyFields))
            );
        }

        return true;
    }
}
