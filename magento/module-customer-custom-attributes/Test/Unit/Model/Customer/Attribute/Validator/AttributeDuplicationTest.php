<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Customer\Attribute\Validator;

use Magento\Customer\Model\AttributeFactory;
use Magento\Customer\Model\Attribute;
use Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\AttributeDuplication;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteFactory;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Model\Customer\Attribute\Validator\AttributeDuplication class.
 */
class AttributeDuplicationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eavConfigMock;

    /**
     * @var AttributeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeFactoryMock;

    /**
     * @var WebsiteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteFactory;

    /**
     * @var Type|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityTypeMock;

    /**
     * @var AttributeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeMock;

    /**
     * @var AttributeDuplication
     */
    private $attributeDuplicationValidator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->eavConfigMock = $this->createMock(Config::class);
        $this->websiteFactory = $this->createMock(WebsiteFactory::class);
        $this->attributeFactoryMock = $this->createMock(AttributeFactory::class);
        $this->entityTypeMock = $this->createMock(Type::class);
        $this->attributeMock = $this->createMock(Attribute::class);

        $objectHelper = new ObjectManager($this);
        $this->attributeDuplicationValidator = $objectHelper->getObject(
            AttributeDuplication::class,
            [
                'eavConfig' => $this->eavConfigMock,
                'attributeFactory' => $this->attributeFactoryMock,
                'websiteFactory' => $this->websiteFactory,
            ]
        );
    }

    /**
     * @return void
     * @expectedException  \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage An attribute with this code already exists.
     */
    public function testValidate()
    {
        $websiteMock = $this->createMock(Website::class);
        $newAttribute = $this->createPartialMock(
            AttributeInterface::class,
            ['getId', 'getAttributeCode', 'getWebsite', 'getEntityTypeId']
        );
        $attributeCode = 'test_attribute';
        $entityTypeId = 1;

        $newAttribute->expects($this->once())->method('getId')->willReturn(null);
        $newAttribute->expects($this->once())->method('getAttributeCode')->willReturn($attributeCode);
        $newAttribute->expects($this->once())->method('getWebsite')->willReturn($websiteMock);
        $newAttribute->expects($this->once())->method('getEntityTypeId')->willReturn($entityTypeId);
        $this->eavConfigMock->expects($this->once())
            ->method('getEntityType')
            ->with($entityTypeId)
            ->willReturn($this->entityTypeMock);
        $this->attributeFactoryMock->expects($this->once())->method('create')->willReturn($this->attributeMock);
        $this->attributeMock->expects($this->once())->method('setWebsite')->with($websiteMock)->willReturnSelf();
        $this->attributeMock->expects($this->once())
            ->method('loadByCode')
            ->with($this->entityTypeMock, $attributeCode)
            ->willReturn($this->attributeMock);
        $this->attributeMock->expects($this->once())->method('getId')->willReturn(1);

        $this->attributeDuplicationValidator->validate($newAttribute);
    }
}
