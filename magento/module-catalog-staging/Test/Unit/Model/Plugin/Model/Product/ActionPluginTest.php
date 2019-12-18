<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\Model\Product;

use Magento\CatalogStaging\Model\Plugin\Model\Product\ActionPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ActionPluginTest extends \PHPUnit\Framework\TestCase
{
    const DATE = '2018-09-20 01:01:01';
    /** @var  ActionPlugin */
    private $plugin;

    /**
     * Set up
     *
     */
    protected function setUp()
    {
        /** @var \Magento\Staging\Api\Data\UpdateInterface|MockObject $versionMock */
        $versionMock = $this->getMockBuilder(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var VersionManager | \MockObject $versionManagerMock */
        $versionManagerMock = $this->getMockBuilder(\Magento\Staging\Model\VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentVersion'])
            ->getMock();
        $versionManagerMock->expects($this->any())
            ->method('getCurrentVersion')
            ->willReturn($versionMock);
        $dateTimeMock = $this->getMockBuilder(\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dateTimeMock->expects($this->any())
            ->method('format')
            ->willReturn(self::DATE);

        /** @var TimezoneInterface| MockObject $localeDateMock */
        $localeDateMock = $this->getMockBuilder(TimezoneInterface::class)
            ->getMock();
        $localeDateMock->expects($this->any())
            ->method('date')
            ->willReturn($dateTimeMock);

        $this->plugin = new ActionPlugin($versionManagerMock, $localeDateMock, ['news_from_date' => 'news_to_date']);
        parent::setUp();
    }

    /**
     * @dataProvider provideAttributes
     * @param array $attrData
     * @param array $expectedResult
     */
    public function testUpdateAttributes(array $attrData, array $expectedResult): void
    {
        /** @var \Magento\Catalog\Model\Product\Action | MockObject $productActionMock */
        $productActionMock = $this->getMockBuilder(\Magento\Catalog\Model\Product\Action::class)
            ->disableOriginalConstructor()
            ->getMock();
        $return = $this->plugin->beforeUpdateAttributes($productActionMock, [], $attrData, 0);
        $this->assertEquals($expectedResult, $return[1]);
    }

    /**
     * @return array
     */
    public function provideAttributes()
    {
        return [
            [['news_from_date' => 1], ['news_from_date' => self::DATE, 'news_to_date' => null]],
            [
                ['news_from_date' => 1, 'test' => 'test data'],
                ['news_from_date' => self::DATE, 'test' => 'test data', 'news_to_date' => null]
            ],
            [['news_from_date' => 0], ['news_from_date' => null]],
            [['fake' => 'fake data'], ['fake' => 'fake data']],
        ];
    }
}
