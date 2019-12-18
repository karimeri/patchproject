<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Test\TestStep;

use Magento\GiftCardAccount\Test\Page\Adminhtml\Index;
use Magento\Mtf\TestStep\TestStepInterface;

/**
 * Generate Gift Card Accounts Code Pool.
 */
class GenerateGiftCardAccountsCodePoolStep implements TestStepInterface
{
    /**
     * Gift Card Accounts page.
     *
     * @var Index
     */
    private $giftCardAccountIndex;

    /**
     * @constructor
     * @param Index $giftCardAccountIndex
     */
    public function __construct(Index $giftCardAccountIndex)
    {
        $this->giftCardAccountIndex = $giftCardAccountIndex;
    }

    /**
     * Generate new Code Pool.
     *
     * @return void
     */
    public function run()
    {
        $this->giftCardAccountIndex->open();
        $this->giftCardAccountIndex->getMessagesBlock()->clickLinkInMessage('error', 'here');
    }
}
