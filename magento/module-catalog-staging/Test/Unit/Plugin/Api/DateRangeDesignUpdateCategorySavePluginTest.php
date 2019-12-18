<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Plugin\Api;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\CatalogStaging\Plugin\Api\DateRangeDesignUpdateCategorySavePlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;

class DateRangeDesignUpdateCategorySavePluginTest extends \PHPUnit\Framework\TestCase
{
    private const CREATED_IN = '1548707040';
    private const UPDATED_IN = '1548707280';

    /**
     * @var UpdateRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var DateRangeDesignUpdateCategorySavePlugin
     */
    private $plugin;

    public function setUp()
    {
        $this->updateRepositoryMock = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->plugin = $objectManager->getObject(
            DateRangeDesignUpdateCategorySavePlugin::class,
            ['updateRepository' => $this->updateRepositoryMock]
        );
    }

    /**
     * @param array $data
     * @param int $expectedCustomDesignFrom
     * @param int $expectedCustomDesignTo
     * @dataProvider beforeSaveDataProvider
     */
    public function testBeforeSave(array $data, int $setCustomDesignFromAt, int $setCustomDesignToAt)
    {
        $startTime = '1970-01-01 00:00:00';
        $endTime = '1971-01-01 00:00:00';

        $categoryMock = $this->createPartialMock(Category::class, ['setCustomAttribute']);
        $categoryMock->setData($data);

        $startVersionMock = $this->createMock(UpdateInterface::class);
        $startVersionMock->method('getStartTime')
            ->willReturn($startTime);
        $endVersionMock = $this->createMock(UpdateInterface::class);
        $endVersionMock->method('getStartTime')
            ->willReturn($endTime);
        $this->updateRepositoryMock->method('get')
            ->willReturnMap(
                [
                    [self::CREATED_IN, $startVersionMock],
                    [self::UPDATED_IN, $endVersionMock]
                ]
            );

        if ($setCustomDesignFromAt > 0) {
            $categoryMock->expects($this->at($setCustomDesignFromAt))
                ->method('setCustomAttribute')
                ->with(
                    'custom_design_from',
                    $startTime
                );
        }

        if ($setCustomDesignToAt > 0) {
            $categoryMock->expects($this->at($setCustomDesignToAt))
                ->method('setCustomAttribute')
                ->with(
                    'custom_design_to',
                    $endTime
                );
        }

        $this->plugin->beforeSave(
            $this->createMock(CategoryRepositoryInterface::class),
            $categoryMock
        );
    }

    public function beforeSaveDataProvider()
    {
        return [
            'no_update' => [[], -1, -1],
            'origin_entity' => [
                [
                    'created_in' => VersionManager::MIN_VERSION,
                    'updated_in' => self::UPDATED_IN
                ],
                -1,
                0
            ],
            'mid_update_entity' => [
                [
                    'created_in' => self::CREATED_IN,
                    'updated_in' => self::UPDATED_IN
                ],
                0,
                1
            ],
            'last_update_entity' => [
                [
                    'created_in' => self::CREATED_IN,
                    'updated_in' => VersionManager::MAX_VERSION
                ],
                0,
                -1
            ],
        ];
    }
}
