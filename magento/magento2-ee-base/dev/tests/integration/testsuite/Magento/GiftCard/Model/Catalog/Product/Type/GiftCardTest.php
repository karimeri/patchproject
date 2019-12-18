<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Model\Catalog\Product\Type;

use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftCardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_10.php
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_50.php
     * @magentoDataFixture Magento/GiftCard/_files/quote.php
     */
    public function testCollectTotalsWithPhysicalGiftCards()
    {
        $buyRequest = new \Magento\Framework\DataObject(
            [
                'giftcard_sender_name' => 'test sender name',
                'giftcard_recipient_name' => 'test recipient name',
                'giftcard_message' => '',
                'qty' => 1,
            ]
        );
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = Bootstrap::getObjectManager()->create(\Magento\Quote\Model\Quote::class);
        $quote->load('test01', 'reserved_order_id');

        $productRepository = Bootstrap::getObjectManager()->create(
            \Magento\Catalog\Api\ProductRepositoryInterface::class
        );
        $productOne = $productRepository->get('gift-card-with-fixed-amount-10', false, null, true);
        $productTwo = $productRepository->get('gift-card-with-fixed-amount-50', false, null, true);

        $quote->addProduct($productOne, $buyRequest);
        $quote->addProduct($productTwo, $buyRequest);

        $quote->collectTotals();

        $this->assertEquals(2, $quote->getItemsQty());
        $this->assertEquals(60, $quote->getGrandTotal());
        $this->assertEquals(60, $quote->getBaseGrandTotal());
    }

    /**
     * @magentoDataFixture Magento/GiftCard/_files/gift_card_physical_with_fixed_amount_50.php
     * @magentoDataFixture Magento/GiftCard/_files/quote.php
     */
    public function testFixedGiftCardAmountAddedToBuyRequest()
    {
        $buyRequest = new \Magento\Framework\DataObject(
            [
                'giftcard_sender_name' => 'Sender Name',
                'giftcard_sender_email' => 'sender@example.com',
                'giftcard_recipient_name' => 'Recipient Name',
                'giftcard_recipient_email' => 'recipient@example.com',
                'giftcard_message' => 'Message',
                'qty' => 1,
            ]
        );
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = Bootstrap::getObjectManager()->create(\Magento\Quote\Model\Quote::class);
        $quote->load('test01', 'reserved_order_id');

        $productRepository = Bootstrap::getObjectManager()->create(
            \Magento\Catalog\Api\ProductRepositoryInterface::class
        );
        $giftCardProduct = $productRepository->get('gift-card-with-fixed-amount-50', false, null, true);
        $quoteItem = $quote->addProduct($giftCardProduct, $buyRequest);
        $quoteItemBuyRequest = $quoteItem->getOptionByCode('info_buyRequest');
        $this->assertContains('"giftcard_amount":50', $quoteItemBuyRequest->getValue());
    }
}
