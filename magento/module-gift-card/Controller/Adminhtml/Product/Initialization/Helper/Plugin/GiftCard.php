<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Controller\Adminhtml\Product\Initialization\Helper\Plugin;

use Magento\GiftCard\Api\Data\GiftcardAmountInterfaceFactory as AmountFactory;
use Magento\Eav\Api\AttributeRepositoryInterface as AttributeRepository;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as InitializationHelper;
use Magento\Catalog\Model\Product;

/**
 * Class GiftCard
 */
class GiftCard
{
    /**
     * @var AmountFactory
     */
    protected $amountFactory;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @param AmountFactory $amountFactory
     * @param AttributeRepository $attributeRepository
     */
    public function __construct(
        AmountFactory $amountFactory,
        AttributeRepository $attributeRepository
    ) {
        $this->amountFactory = $amountFactory;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Process giftcard amount data Before initialize from data
     *
     * @param InitializationHelper $subject
     * @param Product $product
     * @param array $productData
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeInitializeFromData(InitializationHelper $subject, Product $product, array $productData): array
    {
        if ($product->getTypeId() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD) {
            $productData['giftcard_amounts'] = isset($productData['giftcard_amounts'])
                ? $productData['giftcard_amounts']
                : [];
        }
        return [$product, $productData];
    }

    /**
     * Process product after initialize
     *
     * @param InitializationHelper $subject
     * @param Product $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitialize(InitializationHelper $subject, Product $product): Product
    {
        $attribute = $this->attributeRepository->get(Product::ENTITY, 'giftcard_amounts');
        $amounts = [];
        if ($product->getTypeId() != \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD) {
            return $product;
        }
        if (!$product->getData('giftcard_amounts')) {
            $amounts[] = $this->amountFactory->create(['data' => []]);
        } else {
            foreach ($product->getData('giftcard_amounts') as $amountData) {
                if (empty($amountData['delete'])) {
                    $amount = $this->amountFactory->create(['data' => $amountData]);
                    $amount->setAttributeId($attribute->getAttributeId());
                    $amount->setValue(isset($amountData['price']) ? $amountData['price'] : $amountData['value']);
                    $amounts[] = $amount;
                }
            }
        }
        $extension = $product->getExtensionAttributes();
        $extension->setGiftcardAmounts($amounts);
        $product->setExtensionAttributes($extension);
        return $product;
    }
}
