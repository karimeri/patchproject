<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Attribute\Validator;

use Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\AttributeCodeLength;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Validator\StringLength;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\AttributeCodeLength class.
 */
class AttributeCodeLengthTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var AttributeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeMock;

    /**
     * @var Type|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityTypeMock;

    /**
     * @var StringLength|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stringLengthMock;

    /**
     * @var AttributeCodeLength
     */
    private $attributeCodeLengthValidator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->attributeMock = $this->createPartialMock(
            AttributeInterface::class,
            [
                'getId',
                'getAttributeCode',
                'getEntityType',
            ]
        );
        $this->entityTypeMock = $this->createMock(Type::class);
        $this->stringLengthMock = $this->createMock(StringLength::class);

        $this->attributeCodeLengthValidator = $this->objectManager->getObject(
            AttributeCodeLength::class,
            [
                'stringLength' => $this->stringLengthMock,
                'codeLengthByEntityType' => [
                    'customer' => 51,
                    'customer_address' => 60,
                ],
            ]
        );
    }

    /**
     * @param string $attributeCode
     * @param string $entityTypeCode
     * @param int $maxLength
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $attributeCode, string $entityTypeCode, int $maxLength)
    {
        $this->prepareValidation($attributeCode, $entityTypeCode, $maxLength);
        $this->stringLengthMock->expects($this->atLeastOnce())
            ->method('isValid')
            ->with($attributeCode)
            ->willReturn(true);

        $this->attributeCodeLengthValidator->validate($this->attributeMock);
    }

    /**
     * @param string $attributeCode
     * @param string $entityTypeCode
     * @param int $maxLength
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidateWithException(string $attributeCode, string $entityTypeCode, int $maxLength)
    {
        $this->prepareValidation($attributeCode, $entityTypeCode, $maxLength);
        $this->stringLengthMock->expects($this->atLeastOnce())
            ->method('isValid')
            ->with($attributeCode)
            ->willReturn(false);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'The attribute code needs to be ' . $maxLength . ' characters or fewer. Re-enter the code and try again.'
        );

        $this->attributeCodeLengthValidator->validate($this->attributeMock);
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            ['test_attribute_code', 'customer', 51],
            ['test_attribute_long_code', 'customer_address', 60],
        ];
    }

    /**
     * @param string $attributeCode
     * @param string $entityTypeCode
     * @param int $maxLength
     * @return void
     */
    private function prepareValidation(string $attributeCode, string $entityTypeCode, int $maxLength): void
    {
        $this->attributeMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getEntityType')
            ->willReturn($this->entityTypeMock);
        $this->entityTypeMock->expects($this->atLeastOnce())
            ->method('getEntityTypeCode')
            ->willReturn($entityTypeCode);
        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn($attributeCode);
        $this->stringLengthMock->expects($this->atLeastOnce())
            ->method('setMax')
            ->with($maxLength)
            ->willReturnSelf();
    }
}
