<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Controller\Adminhtml\Redirect;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\PaypalOnBoarding\Model\MagentoMerchantId;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Processes failure response from PayPal Middleman application
 */
class Failure extends Action implements CsrfAwareActionInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoMerchantId
     */
    private $merchantService;

    /**
     * Failure constructor.
     * @param Context $context
     * @param MagentoMerchantId $merchantService
     * @param LoggerInterface $logger
     */
    public function __construct(Context $context, MagentoMerchantId $merchantService, LoggerInterface $logger)
    {
        parent::__construct($context);
        $this->merchantService = $merchantService;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $websiteId = $this->getRequest()->getParam('website');
        $magentoMerchantId = $this->getRequest()->getParam('magentoMerchantId');
        $errorMessage = $this->getRequest()->getParam('message');

        if (!$magentoMerchantId || $magentoMerchantId !== $this->merchantService->generate($websiteId)) {
            $this->messageManager->addErrorMessage(__('Wrong merchant signature.'));
            return $this->getRedirect();
        }

        $this->messageManager->addErrorMessage(
            __('We were unable to save PayPal credentials. Please try again later.')
        );
        $this->logger->error($magentoMerchantId . ' : ' . $errorMessage);

        return $this->getRedirect();
    }

    /**
     * Get response redirect
     * @return Redirect
     */
    private function getRedirect()
    {
        $redirect = $this->resultRedirectFactory->create();
        return $redirect->setPath(
            'adminhtml/system_config/edit',
            [
                '_current' => ['website'],
                'section' => 'payment',
                '_nosid' => true
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
