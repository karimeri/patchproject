<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Http\Soap;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

/**
 * Builds gateway transfer object.
 */
class TransferFactory implements TransferFactoryInterface
{
    const HEAD_NAMESPACE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @param ConfigInterface $config
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(
        ConfigInterface $config,
        TransferBuilder $transferBuilder
    ) {
        $this->config = $config;
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $storeId = $request['store_id'] ?? null;
        // sending store id and other additional keys are restricted by Cybersource API
        unset($request['store_id']);

        return $this->transferBuilder
            ->setClientConfig(
                [
                    'wsdl' => (bool)$this->config->getValue('sandbox_flag', $storeId)
                        ? $this->config->getValue('wsdl_test_mode', $storeId)
                        : $this->config->getValue('wsdl', $storeId)
                ]
            )
            ->setHeaders([$this->createHeaders($storeId)])
            ->setBody($request)
            ->setMethod('runTransaction')
            ->setUri('')
            ->build();
    }

    /**
     * Creates header
     *
     * @param int|null $storeId
     * @return \SoapHeader
     */
    private function createHeaders(?int $storeId = null)
    {
        $soapUsername = new \SoapVar(
            $this->config->getValue('merchant_id', $storeId),
            XSD_STRING,
            null,
            null,
            'Username',
            self::HEAD_NAMESPACE
        );

        $soapPassword = new \SoapVar(
            $this->config->getValue('transaction_key', $storeId),
            XSD_STRING,
            null,
            null,
            'Password',
            self::HEAD_NAMESPACE
        );

        $soapAuth = new \SoapVar(
            [
                $soapUsername,
                $soapPassword
            ],
            SOAP_ENC_OBJECT,
            null,
            null,
            'UsernameToken',
            self::HEAD_NAMESPACE
        );

        $soapToken = new \SoapVar(
            [$soapAuth],
            SOAP_ENC_OBJECT,
            null,
            null,
            'UsernameToken',
            self::HEAD_NAMESPACE
        );

        $security =new \SoapVar(
            $soapToken,
            SOAP_ENC_OBJECT,
            null,
            null,
            'Security',
            self::HEAD_NAMESPACE
        );

        return new \SoapHeader(
            self::HEAD_NAMESPACE,
            'Security',
            $security,
            true
        );
    }
}
