<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\Soap;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Cybersource\Gateway\Request\SilentOrder\TransactionDataBuilder;

/**
 * Adds merchant data to request.
 */
class MerchantDataBuilder implements BuilderInterface
{
    /**
     * Merchant id key
     */
    const MERCHANT_ID = 'merchant_id';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $storeId = $paymentDO->getOrder()->getStoreId();

        return [
            'merchantID' => $this->config->getValue(self::MERCHANT_ID, $storeId),
            'merchantReferenceCode' => $paymentDO->getPayment()
                ->getAdditionalInformation(
                    TransactionDataBuilder::REFERENCE_NUMBER
                )
        ];
    }
}
