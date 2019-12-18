<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Plugin;

use Magento\Customer\Model\Attribute;
use Magento\CustomerCustomAttributes\Model\Customer\Attribute\CompositeValidator;
use Magento\CustomerCustomAttributes\Model\Plugin\ValidateCustomerAddressAttribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Model\Plugin\ValidateCustomerAddressAttribute class.
 */
class ValidateCustomerAddressAttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Attribute|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerAttributeMock;

    /**
     * @var CompositeValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $compositeValidatorMock;

    /**
     * @var ValidateCustomerAddressAttribute
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->compositeValidatorMock = $this->createMock(CompositeValidator::class);
        $this->customerAttributeMock = $this->createMock(Attribute::class);
        $objectManager = new ObjectManager($this);

        $this->model = $objectManager->getObject(
            ValidateCustomerAddressAttribute::class,
            [
                'compositeValidator' => $this->compositeValidatorMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testBeforeBeforeSave()
    {
        $this->compositeValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->customerAttributeMock)
            ->willReturnSelf();

        $this->model->beforeBeforeSave($this->customerAttributeMock);
    }

    /**
     * @return void
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The value of Admin scope can't be empty.
     */
    public function testBeforeBeforeSaveWithException()
    {
        $exception = new LocalizedException(__('The value of Admin scope can\'t be empty.'));

        $this->compositeValidatorMock->expects($this->once())
            ->method('validate')
            ->with($this->customerAttributeMock)
            ->willThrowException($exception);

        $this->model->beforeBeforeSave($this->customerAttributeMock);
    }
}
