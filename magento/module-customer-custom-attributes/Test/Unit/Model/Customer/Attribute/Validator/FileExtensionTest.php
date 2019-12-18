<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Attribute\Validator;

use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\FileExtension;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\FileExtension class.
 */
class FileExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NotProtectedExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionValidatorMock;

    /**
     * @var AttributeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeMock;

    /**
     * @var FileExtension
     */
    private $fileExtensionValidator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->extensionValidatorMock = $this->createMock(NotProtectedExtension::class);
        $this->attributeMock = $this->createPartialMock(AttributeInterface::class, ['getData']);

        $objectHelper = new ObjectManager($this);
        $this->fileExtensionValidator = $objectHelper->getObject(
            FileExtension::class,
            [
                'extensionValidator' => $this->extensionValidatorMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testValidate()
    {
        $fileExtension = 'jpeg';

        $this->attributeMock->expects($this->at(0))->method('getData')->with('frontend_input')->willReturn('file');
        $this->attributeMock->expects($this->at(1))
            ->method('getData')
            ->with('file_extensions')
            ->willReturn($fileExtension);
        $this->extensionValidatorMock->expects($this->once())
            ->method('isValid')
            ->with($fileExtension)
            ->willReturn(true);

        $this->fileExtensionValidator->validate($this->attributeMock);
    }

    /**
     * @return void
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Please correct the value for file extensions.
     */
    public function testValidateWithException()
    {
        $fileExtension = 'php';

        $this->attributeMock->expects($this->at(0))->method('getData')->with('frontend_input')->willReturn('file');
        $this->attributeMock->expects($this->at(1))
            ->method('getData')
            ->with('file_extensions')
            ->willReturn($fileExtension);
        $this->extensionValidatorMock->expects($this->once())
            ->method('isValid')
            ->with($fileExtension)
            ->willReturn(false);

        $this->fileExtensionValidator->validate($this->attributeMock);
    }
}
