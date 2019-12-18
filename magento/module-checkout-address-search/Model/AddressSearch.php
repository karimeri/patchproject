<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory as AddressCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Returns collection of customer addresses for onepage checkout address search.
 */
class AddressSearch
{
    /**
     * Configuration value for address search results page size.
     */
    private const SEARCH_PAGE_SIZE = 'checkout/options/address_search_page_size';

    /**
     * @var AddressCollectionFactory
     */
    private $shippingAddressCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param AddressCollectionFactory $shippingAddressCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        AddressCollectionFactory $shippingAddressCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->shippingAddressCollectionFactory = $shippingAddressCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Creates address collection and applies filters and customer limitations.
     *
     * @param string $pattern
     * @param int $customerId
     * @param int $pageNum
     * @return \Magento\Customer\Model\ResourceModel\Address\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function search(
        string $pattern,
        int $customerId,
        int $pageNum
    ): \Magento\Customer\Model\ResourceModel\Address\Collection {
        $customer = $this->customerRepository->getById($customerId);
        /** @var \Magento\Customer\Model\ResourceModel\Address\Collection $addressCollection */
        $addressCollection = $this->shippingAddressCollectionFactory->create();

        // filter only active addresses
        $addressCollection->addFilter('is_active', 1);
        $addressCollection->addAttributeToSelect('*');
        // filter addresses of the certain customer
        $addressCollection->setCustomerFilter($customer);
        // sort by lastly added address
        $addressCollection->setOrder('entity_id', 'desc');
        $addressCollection->setCurPage($pageNum);
        $addressCollection->setPageSize($this->getPageSize());
        $addressCollection->addAttributeToFilter(
            [
                ['attribute' => 'postcode', 'like' => $pattern . '%'],
                ['attribute' => 'region', 'like' => '%' . $pattern . '%'],
                ['attribute' => 'city', 'like' => '%' . $pattern . '%'],
                ['attribute' => 'street', 'like' => '%' . $pattern . '%']
            ]
        );

        return $addressCollection;
    }

    /**
     * Returns page size from the configuration.
     *
     * @return int
     */
    private function getPageSize(): int
    {
        return (int)$this->scopeConfig->getValue(self::SEARCH_PAGE_SIZE);
    }
}
