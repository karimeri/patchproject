<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Controller\Adminhtml\SilentOrder;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 */
class TokenRequestTest extends AbstractBackendController
{
    /**
     * @var Quote
     */
    private $quoteSession;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->getRequest()->setParam('cc_type', 'VI');
        $this->quoteSession = $this->_objectManager->get(Quote::class);
    }

    /**
     * Checks if payment token request to Cybersource is initialized with default scope.
     *
     * @magentoDataFixture Magento/Sales/_files/quote_with_new_customer.php
     * @magentoDataFixture Magento/Cybersource/Fixtures/payment_configuration.php
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     */
    public function testExecute()
    {
        $this->perform('default', 'def_access_key', 'def_profile_id');
    }

    /**
     * Checks if payment token request to Cybersource is initialized per website.
     *
     * @magentoDataFixture Magento/Sales/_files/quote_with_new_customer.php
     * @magentoDataFixture Magento/Cybersource/Fixtures/payment_configuration.php
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     */
    public function testExecuteWithWebsiteConfiguration()
    {
        $this->perform('fixture_second_store', 'website_access_key', 'website_profile_id');
    }

    /**
     * Perform test.
     *
     * @param string $storeCode
     * @param string $accessKey
     * @param string $profileId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function perform(string $storeCode, string $accessKey, string $profileId)
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->_objectManager->get(StoreRepositoryInterface::class);
        $store = $storeRepository->get($storeCode);

        $quote = $this->getQuote('2000000001');
        $this->quoteSession->setQuoteId($quote->getId());
        $this->quoteSession->setStoreId($store->getId());

        $this->dispatch('backend/cybersource/SilentOrder/TokenRequest');

        /** @var SerializerInterface $serializer */
        $serializer = $this->_objectManager->get(SerializerInterface::class);
        $decoded = $serializer->unserialize($this->getResponse()->getBody());

        self::assertEquals($accessKey, $decoded['cybersource']['fields']['access_key']);
        self::assertEquals($profileId, $decoded['cybersource']['fields']['profile_id']);
    }

    /**
     * Gets quote by reserved order id.
     *
     * @param string $reservedOrderId
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    private function getQuote(string $reservedOrderId): \Magento\Quote\Api\Data\CartInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', $reservedOrderId)
            ->create();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $this->_objectManager->get(CartRepositoryInterface::class);
        $items = $quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($items);
    }
}
