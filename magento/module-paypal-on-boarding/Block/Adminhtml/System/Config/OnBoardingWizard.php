<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\PaypalOnBoarding\Model\MiddlemanService;
use Magento\PaypalOnBoarding\Model\Button\Button;

/**
 * Custom renderer for PayPal On-Boarding credentials wizard popup
 */
class OnBoardingWizard extends Field
{
    /**
     * @inherit
     */
    protected $_template = 'system/config/on_boarding_wizard.phtml';

    /**
     * @var MiddlemanService
     */
    private $middlemanService;

    /**
     * @var Button|null
     */
    private $button;

    /**
     * OnBoardingWizard constructor.
     * @param Context $context
     * @param array $data
     * @param MiddlemanService $middlemanService
     */
    public function __construct(
        Context $context,
        MiddlemanService $middlemanService,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->middlemanService = $middlemanService;
    }

    /**
     * Get Paypal api credentials button
     *
     * @return \Magento\PaypalOnBoarding\Model\Button\Button
     */
    public function getButton()
    {
        if (null === $this->button) {
            $this->button = $this->middlemanService->createButton();
        }

        return $this->button;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
