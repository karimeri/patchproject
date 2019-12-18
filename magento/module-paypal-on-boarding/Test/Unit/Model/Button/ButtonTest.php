<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaypalOnBoarding\Test\Unit\Model\Button;

use Magento\PaypalOnBoarding\Model\Button\Button;
use PHPUnit\Framework\TestCase;

/**
 * Class ButtonTest
 */
class ButtonTest extends TestCase
{
    /**
     * @covers \Magento\PaypalOnBoarding\Model\Button\Button::getSandboxUrl()
     * @covers \Magento\PaypalOnBoarding\Model\Button\Button::getLiveUrl()
     */
    public function testButtonInit()
    {
        $liveButtonUrl = "https://www.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $sandboxButtonUrl = "https://www.sandbox.paypal.com/webapps/merchantboarding/webflow/externalpartnerflow";
        $button = new Button($sandboxButtonUrl, $liveButtonUrl);

        $this->assertEquals($liveButtonUrl, $button->getLiveUrl());
        $this->assertEquals($sandboxButtonUrl, $button->getSandboxUrl());
    }
}
