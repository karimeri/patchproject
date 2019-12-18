<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Helper;

use Magento\CustomerCustomAttributes\Helper\Data;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\Helper\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eavConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeDate;

    /**
     * @var \Magento\Framework\Filter\FilterManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterManager;

    /**
     * @var \Magento\CustomerCustomAttributes\Helper\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerAddress;

    /**
     * @var \Magento\CustomerCustomAttributes\Helper\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customer;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavConfig = $this->getMockBuilder(\Magento\Eav\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeDate = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
            ->getMockForAbstractClass();

        $this->filterManager = $this->getMockBuilder(\Magento\Framework\Filter\FilterManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerAddress = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Helper\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(\Magento\CustomerCustomAttributes\Helper\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new Data(
            $this->context,
            $this->eavConfig,
            $this->localeDate,
            $this->filterManager,
            $this->customerAddress,
            $this->customer
        );
    }

    public function testGetAttributeValidateFilters(): void
    {
        $result = $this->helper->getAttributeValidateFilters();

        self::assertInternalType('array', $result);
        self::assertArrayHasKey('length', $result);
        self::assertEquals(__('Length Only'), $result['length']);
    }

    /**
     * Checks attributes according to provided input types.
     *
     * @param {String} $inputType - type of input field
     * @param {Boolean} $lengthValidation - is length validation allowed
     * @dataProvider inputTypesDataProvider
     */
    public function testGetAttributeInputTypesWithInputTypes($inputType, $lengthValidation): void
    {
        $result = $this->helper->getAttributeInputTypes($inputType);

        self::assertInternalType('array', $result);
        self::assertArrayHasKey('validate_filters', $result);
        self::assertInternalType('array', $result['validate_filters']);
        self::assertEquals($lengthValidation, in_array('length', $result['validate_filters'], true));
    }

    /**
     * Checks attributes according to provided input types.
     *
     * @param {String} $inputType - type of input field
     * @param {Boolean} $lengthValidation - is length validation allowed
     * @dataProvider inputTypesDataProvider
     */
    public function testGetAttributeInputTypesWithInputTypeNull($inputType, $lengthValidation): void
    {
        $result = $this->helper->getAttributeInputTypes();

        self::assertInternalType('array', $result);
        self::assertArrayHasKey($inputType, $result);
        self::assertInternalType('array', $result[$inputType]);
        self::assertArrayHasKey('validate_filters', $result[$inputType]);
        self::assertInternalType('array', $result[$inputType]['validate_filters']);
        self::assertEquals(
            $lengthValidation,
            in_array('length', $result[$inputType]['validate_filters'], true)
        );
    }

    /**
     * Provides possible input types
     *
     * @return array
     */
    public function inputTypesDataProvider(): array
    {
        return [
            ['text', true],
            ['textarea', false],
            ['multiline', true],
            ['date', false],
            ['select', false],
            ['multiselect', false],
            ['boolean', false],
            ['file', false],
            ['image', false]
        ];
    }
}
