<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\PaypalOnBoarding\Api\CredentialsServiceInterface;
use Magento\PaypalOnBoarding\Api\Data\CredentialsInterfaceFactory;
use Magento\PaypalOnBoarding\Api\Data\CredentialsInterface;
use Magento\PaypalOnBoarding\Model\ResponseCredentialsValidator;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Processes success response from PayPal Middleman application
 */
class Success extends Action implements CsrfAwareActionInterface
{
    /**#@+
     * Response data keys
     */
    private static $keyUsername = 'username';
    private static $keyPassword = 'password';
    private static $keySignature = 'signature';
    private static $keyMagentoMerchantId = 'magentoMerchantId';
    private static $keyPaypalMerchantId = 'paypalMerchantId';
    /**#@-*/

    /**
     * @var ResponseCredentialsValidator
     */
    private $validator;

    /**
     * @var CredentialsServiceInterface
     */
    private $credentialsService;

    /**
     * @var CredentialsInterfaceFactory
     */
    private $credentialsFactory;

    /**
     * @param Context $context
     * @param ResponseCredentialsValidator $validator
     * @param CredentialsServiceInterface $credentialsService
     * @param CredentialsInterfaceFactory $credentialsFactory
     */
    public function __construct(
        Context $context,
        ResponseCredentialsValidator $validator,
        CredentialsServiceInterface $credentialsService,
        CredentialsInterfaceFactory $credentialsFactory
    ) {
        parent::__construct($context);
        $this->validator = $validator;
        $this->credentialsService = $credentialsService;
        $this->credentialsFactory = $credentialsFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $request = $this->getRequest()->getParams();

        try {
            $this->validator->validate(
                $request,
                [
                    self::$keyUsername,
                    self::$keyPassword,
                    self::$keySignature,
                    self::$keyMagentoMerchantId,
                    self::$keyPaypalMerchantId
                ]
            );

            /** @var CredentialsInterface $credentials */
            $credentials = $this->credentialsFactory->create();
            $credentials->setUsername($request[self::$keyUsername]);
            $credentials->setPassword($request[self::$keyPassword]);
            $credentials->setSignature($request[self::$keySignature]);
            $credentials->setMerchantId($request[self::$keyPaypalMerchantId]);
            $this->credentialsService->save(
                $credentials,
                (int)$this->getRequest()->getParam('website')
            );
            $this->messageManager->addSuccessMessage(
                __('You saved PayPal credentials. Please enable PayPal Express Checkout.')
            );
        } catch (ValidatorException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while saving credentials: %1', $e->getMessage())
            );
        }

        $redirect = $this->resultRedirectFactory->create();

        return $redirect->setPath(
            'adminhtml/system_config/edit',
            [
                '_current' => ['website'],
                '_nosid' => true,
                'section' => 'payment'
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
