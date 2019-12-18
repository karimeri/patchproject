<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogStaging\Observer\UpdateProductDateAttributes;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\DateTime;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Intl\DateTimeFactory;

/**
 * Class UpdateProductDateAttributesTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateProductDateAttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpdateProductDateAttributes
     */
    private $observer;

    /**
     * @var VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManager;

    /**
     * @var TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localeDate;

    /**
     * @var DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeFactory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentVersion'])
            ->getMock();

        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->setMethods(['date'])
            ->getMockForAbstractClass();

        $this->dateTimeFactory = $this->getMockBuilder(DateTimeFactory::class)
            ->setMethods(['create'])
            ->getMock();

        $this->observer = new UpdateProductDateAttributes(
            $this->versionManager,
            $this->localeDate,
            $this->dateTimeFactory
        );
    }

    /**
     * Checks execute() method logic in cases when is_new is equal to '1'
     *
     * Test cases:
     *   - update is not created, is_new='1'
     *
     * @return void
     */
    public function testExecuteWithoutExistingUpdateAndIsNewOn(): void
    {
        $isNewProduct = '1';

        $updateMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $updateMock->expects($this->once())
            ->method('getStartTime')
            ->willReturn(null);

        $currentDateTime = (new \DateTime('now', new \DateTimeZone('UTC')));
        $formatedDateTime = $currentDateTime->format(DateTime::DATETIME_PHP_FORMAT);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods([
                'getData',
                'setData',
            ])
            ->getMockForAbstractClass();
        $productMock->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['is_new', $isNewProduct],
                ['news_from_date', null],
            ]);
        $productMock->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['news_from_date', $formatedDateTime],
                ['news_to_date', null]
            );

        $this->versionManager->expects(static::once())
            ->method('getCurrentVersion')
            ->willReturn($updateMock);

        $this->localeDate->expects($this->any())
            ->method('date')
            ->willReturn($currentDateTime);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['product' => $productMock]));

        $this->observer->execute($observerMock);
    }

    /**
     * Checks execute() method logic in cases when is_new is equal to '0'
     *
     * Test cases:
     *   - update is not created, is_new='0'
     *
     * @return void
     */
    public function testExecuteWithoutExistingUpdateAndIsNewOff(): void
    {
        $currentDateTime = (new \DateTime('now', new \DateTimeZone('UTC')));
        $formatedDateTime = $currentDateTime->format(DateTime::DATETIME_PHP_FORMAT);

        $isNewProduct = '0';

        $updateMock = $this->getMockBuilder(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->getMockForAbstractClass();
        $updateMock->expects($this->once())
            ->method('getStartTime')
            ->willReturn(null);

        $productMock = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->setMethods([
                'getData',
                'setData',
            ])
            ->getMockForAbstractClass();
        $productMock->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['is_new', $isNewProduct],
                ['news_from_date', $formatedDateTime],
            ]);
        $productMock->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['news_from_date', null],
                ['news_to_date', null]
            );

        $this->versionManager->expects(static::once())
            ->method('getCurrentVersion')
            ->willReturn($updateMock);

        $this->localeDate->expects($this->any())
            ->method('date')
            ->willReturn($currentDateTime);

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['product' => $productMock]));

        $this->observer->execute($observerMock);
    }

    /**
     * Checks execute() method logic in cases when is_new value is not NULL
     *
     * Test cases:
     *   - update is already created, is_new='0'
     *   - update is already created, is_new='1'
     *
     * @dataProvider dataProviderTestExecuteWithExistingUpdate
     * @param string $isNewProduct
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @param string|null $expectedStartTime
     * @param string|null $expectedEndTime
     *
     * @return void
     */
    public function testExecuteWithExistingUpdate(
        $isNewProduct,
        $startTime,
        $endTime,
        $expectedStartTime,
        $expectedEndTime
    ): void {
        $startTimeTimestamp = $startTime ? $startTime->getTimestamp() : null;
        $endTimeTimestamp = $endTime ? $endTime->getTimestamp() : null;

        $updateMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $updateMock->method('getStartTime')
            ->willReturn($startTimeTimestamp);
        $updateMock->method('getEndTime')
            ->willReturn($endTimeTimestamp);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods([
                'getData',
                'setData',
            ])
            ->getMockForAbstractClass();
        $productMock->method('getData')
            ->willReturnMap([
                ['is_new', $isNewProduct],
            ]);
        $productMock->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['news_from_date', $expectedStartTime],
                ['news_to_date', $expectedEndTime]
            );

        $this->versionManager->method('getCurrentVersion')
            ->willReturn($updateMock);

        $this->dateTimeFactory->method('create')
            ->willReturnMap(
                [
                    [$startTimeTimestamp, null, $startTime],
                    [$endTimeTimestamp, null, $endTime],
                ]
            );

        $this->localeDate->method('date')
            ->willReturnMap(
                [
                    [$startTime, null, true, true, $startTime],
                    [$endTime, null, true, true, $endTime],
                ]
            );

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['product' => $productMock]));

        $this->observer->execute($observerMock);
    }

    /**
     * Data provider for testExecuteWithExistingUpdate() method
     *
     * @return array
     */
    public function dataProviderTestExecuteWithExistingUpdate(): array
    {
        $startTime = new \DateTime('+1 day');
        $endTime = new \DateTime('+3 days');

        return [
            [
                'is_new' => '1',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'expected_start_time' => $startTime->format(DateTime::DATETIME_PHP_FORMAT),
                'expected_end_time' => $endTime->format(DateTime::DATETIME_PHP_FORMAT),
            ],
            [
                'is_new' => '0',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'expected_start_time' => null,
                'expected_end_time' => null,
            ],
        ];
    }

    /**
     * Checks execute() method logic in cases when is_new value is NULL
     *
     * Test cases:
     *   - update is already created, is_new is NULL, news_from date is NULL
     *   - update is already created, is_new is NULL, news_from date is not NULL
     *
     * @dataProvider dataProviderTestExecuteWithExistingUpdateAndDbData
     * @param bool|string|null $isNewProduct
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @param string|null $newsFromDate
     * @param string|null $newsToDate
     * @param string|null $expectedStartTime
     * @param string|null $expectedEndTime
     *
     * @return void
     */
    public function testExecuteWithExistingUpdateAndDbData(
        $isNewProduct,
        \DateTime $startTime,
        \DateTime $endTime,
        $newsFromDate,
        $newsToDate,
        $expectedStartTime,
        $expectedEndTime
    ): void {
        $startTimeTimestamp = $startTime ? $startTime->getTimestamp() : null;
        $endTimeTimestamp = $endTime ? $endTime->getTimestamp() : null;

        $updateMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $updateMock->method('getStartTime')
            ->willReturn($startTimeTimestamp);
        $updateMock->method('getEndTime')
            ->willReturn($endTimeTimestamp);

        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods([
                'getData',
                'setData',
            ])
            ->getMockForAbstractClass();
        $productMock->method('getData')
            ->willReturnMap([
                ['is_new', $isNewProduct],
                ['news_from_date', $newsFromDate],
                ['news_to_date', $newsToDate],
            ]);
        $productMock->method('setData')
            ->withConsecutive(
                ['news_from_date', $expectedStartTime],
                ['news_to_date', $expectedEndTime]
            );

        $this->versionManager->method('getCurrentVersion')
            ->willReturn($updateMock);

        $this->dateTimeFactory->method('create')
            ->willReturnMap(
                [
                    [$startTimeTimestamp, null, $startTime],
                    [$endTimeTimestamp, null, $endTime],
                ]
            );

        $this->localeDate->method('date')
            ->willReturnMap(
                [
                    [$startTime, null, true, true, $startTime],
                    [$endTime, null, true, true, $endTime],
                ]
            );

        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observerMock->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['product' => $productMock]));

        $this->observer->execute($observerMock);
    }

    /**
     * Data provider for testExecuteWithExistingUpdateAndDbData() method
     *
     * @return array
     */
    public function dataProviderTestExecuteWithExistingUpdateAndDbData(): array
    {
        $startTime = new \DateTime('+1 day');
        $endTime = new \DateTime('+3 days');

        return [
            [
                'is_new' => null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'news_from_date' => null,
                'news_to_date' => null,
                'expected_start_time' => null,
                'expected_end_time' => null,
            ],
            [
                'is_new' => null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'news_from_date' => $startTime->format(DateTime::DATETIME_PHP_FORMAT),
                'news_to_date' => $endTime->format(DateTime::DATETIME_PHP_FORMAT),
                'expected_start_time' => $startTime->format(DateTime::DATETIME_PHP_FORMAT),
                'expected_end_time' => $endTime->format(DateTime::DATETIME_PHP_FORMAT),
            ],
        ];
    }
}
