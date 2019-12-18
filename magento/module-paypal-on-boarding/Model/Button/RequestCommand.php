<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Model\Button;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\HTTP\ZendClient;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Place request for getting urls from Middleman application
 */
class RequestCommand
{
    /**
     * @var ZendClientFactory
     */
    private $clientFactory;

    /**
     * @var ResponseValidator
     */
    private $responseButtonValidator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ZendClientFactory $clientFactory
     * @param ResponseValidator $responseButtonValidator
     * @param LoggerInterface $logger
     */
    public function __construct(
        ZendClientFactory $clientFactory,
        ResponseValidator $responseButtonValidator,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseButtonValidator = $responseButtonValidator;
        $this->logger = $logger;
    }

    /**
     * Place http request
     *
     * @param string $host
     * @param array $requestParams
     * @param array $responseFields fields should be present in response
     * @return string
     */
    public function execute($host, array $requestParams, array $responseFields)
    {
        /** @var ZendClient $client */
        $client = $this->clientFactory->create();
        $client->setParameterGet($requestParams);
        $client->setUri($host);

        $result = '';
        try {
            $response = $client->request();
            $this->responseButtonValidator->validate(
                $response,
                $responseFields
            );
            $result = $response->getBody();
        } catch (ValidatorException $e) {
            $this->logger->error($e->getMessage());
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
