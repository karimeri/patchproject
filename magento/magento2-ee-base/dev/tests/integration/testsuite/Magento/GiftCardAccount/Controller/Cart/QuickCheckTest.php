<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Controller\Cart;

use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Test for \Magento\GiftCardAccount\Controller\Cart\QuickCheck class.
 */
class QuickCheckTest extends AbstractController
{
    /**
     * Check that controller applied only POST requests.
     */
    public function testExecuteWithNonPostRequest()
    {
        $this->getRequest()->setParam('isAjax', true);
        $this->getRequest()->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->dispatch('/giftcard/cart/quickCheck/');

        $this->assert404NotFound();
    }
}
