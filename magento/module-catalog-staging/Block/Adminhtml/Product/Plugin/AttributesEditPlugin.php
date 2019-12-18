<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Block\Adminhtml\Product\Plugin;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Action\Attribute\Tab\Attributes;

/**
 * Class AttributesEditPlugin
 */
class AttributesEditPlugin
{
    /** @var array */
    private $excludedFields;

    /**
     * AttributesEditPlugin constructor.
     * @param array $excludedFields
     */
    public function __construct(array $excludedFields)
    {
        $this->excludedFields = $excludedFields;
    }

    /**
     * Add new excluded fields
     *
     * @param Attributes $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetExcludedFields(Attributes $subject, $result): array
    {
        return array_merge($result, $this->excludedFields);
    }
}
