<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerCustomAttributes\Model\Customer\Attribute;

use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for Customer attributes validation.
 */
interface ValidatorInterface
{
    /**
     * Validate customer attributes.
     *
     * Throws localized exception if is not valid.
     *
     * @param AttributeInterface $attribute
     * @return void
     * @throws LocalizedException
     */
    public function validate(AttributeInterface $attribute): void;
}
