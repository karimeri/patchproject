<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Validator\SilentOrder;

use Magento\Cybersource\Gateway\Helper\SilentOrderHelper;
use Magento\Cybersource\Gateway\Request\SilentOrder\MerchantSecureDataBuilder;
use Magento\Framework\Config\ScopeInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Validates signed request.
 */
class SignValidator extends AbstractValidator
{
    /**
     * Signed fields key
     */
    const SIGNED_FIELD_NAMES = 'signed_field_names';

    /**
     * Signature field
     */
    const SIGNATURE = 'signature';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ScopeInterface
     */
    private $scope;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param ConfigInterface $config
     * @param ScopeInterface $scope
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ConfigInterface $config,
        ScopeInterface $scope
    ) {
        parent::__construct($resultFactory);

        $this->config = $config;
        $this->scope = $scope;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return null|ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        $paymentDO = SubjectReader::readPayment($validationSubject);

        if (!isset(
            $response[static::SIGNED_FIELD_NAMES],
            $response[static::SIGNATURE]
        )
        ) {
            return $this->createResult(false, [__('Gateway validation error')]);
        }

        $storeId = $this->getStoreId($response) ?? $paymentDO->getOrder()->getStoreId();
        $areaPrefix = $this->getAreaPrefix($response, $storeId);

        try {
            return $this->createResult(
                SilentOrderHelper::signFields(
                    $this->getFieldsToSign(
                        $response,
                        $response[static::SIGNED_FIELD_NAMES]
                    ),
                    $this->config->getValue(
                        $areaPrefix . 'secret_key',
                        $storeId
                    )
                ) === $response[static::SIGNATURE]
            );
        } catch (\LogicException $e) {
            return $this->createResult(false, [__('Gateway validation error')]);
        }
    }

    /**
     * Returns signed fields
     *
     * @param array $response
     * @param string $signedList
     * @return array
     */
    private function getFieldsToSign(array $response, $signedList)
    {
        $result = [];
        foreach (explode(',', $signedList) as $key) {
            if (!isset($response[$key])) {
                throw new \LogicException;
            }
            $result[$key] = $response[$key];
        }
        return $result;
    }

    /**
     * Returns store id from Cybersource-related request.
     *
     * @param array $response
     * @return null|string
     */
    private function getStoreId($response): ?string
    {
        return $response[MerchantSecureDataBuilder::MERCHANT_SECURE_DATA2] ??
            $response['req_' . MerchantSecureDataBuilder::MERCHANT_SECURE_DATA2] ?? null;
    }

    /**
     * Returns config key area prefix.
     *
     * @param array $response
     * @param int|null $storeId
     * @return string
     */
    private function getAreaPrefix(array $response, $storeId = null): string
    {
        $area = $response[MerchantSecureDataBuilder::MERCHANT_SECURE_DATA3] ??
            $response['req_' . MerchantSecureDataBuilder::MERCHANT_SECURE_DATA3] ??
            $this->scope->getCurrentScope();

        $isMultidomain = $this->config->getValue('is_multidomain', $storeId);

        return ($area === 'adminhtml' && $isMultidomain) ? 'admin_' : '';
    }
}
