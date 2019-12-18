<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Observer;

/**
 * Test cases for \Magento\GiftWrapping\Observer\PrepareGiftOptions observer.
 */
class PrepareGiftOptionsTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftWrapping\Observer\PrepareGiftOptions */
    private $model;

    /**
     * @var \Magento\GiftWrapping\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperDataMock;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $observerMock;

    /**
     * @var \Magento\Framework\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventMock;

    /**
     * @var \Magento\Quote\Model\Quote\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityMock;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->helperDataMock = $this->createMock(\Magento\GiftWrapping\Helper\Data::class);
        $this->observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->eventMock = $this->createPartialMock(
            \Magento\Framework\Event::class,
            [
                'getEntity',
                '__wakeup'
            ]
        );
        $this->entityMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            [
                'getQuote',
                'setIsGiftOptionsAvailable',
                '__wakeup'
            ]
        );
        $this->quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'getIsVirtual',
                '__wakeup'
            ]
        );
        $this->model = $objectManagerHelper->getObject(
            \Magento\GiftWrapping\Observer\PrepareGiftOptions::class,
            [
                'giftWrappingData' => $this->helperDataMock
            ]
        );
    }

    /**
     * Test the most expected case when we need to enable gift wrapping.
     *
     * @return void
     */
    public function testPrepareGiftOptions()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getEntity')->will($this->returnValue($this->entityMock));
        $this->entityMock->expects($this->exactly(2))->method('getQuote')->will($this->returnValue($this->quoteMock));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')->will($this->returnValue(true));
        $this->quoteMock->expects($this->once())->method('getIsVirtual')->will($this->returnValue(false));
        $this->entityMock->expects($this->once())->method('setIsGiftOptionsAvailable')->with(true);

        $this->model->execute($this->observerMock);
    }

    /**
     * Test with virtual quote and enabled gift wrapping setting.
     * In this case we don't need to enable gift wrapping option for frontend.
     *
     * @return void
     */
    public function testPrepareGiftOptionsWithVirtualQuote()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getEntity')->will($this->returnValue($this->entityMock));
        $this->entityMock->expects($this->exactly(2))->method('getQuote')->will($this->returnValue($this->quoteMock));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')->will($this->returnValue(true));
        $this->quoteMock->expects($this->once())->method('getIsVirtual')->will($this->returnValue(true));
        $this->entityMock->expects($this->never())->method('setIsGiftOptionsAvailable')->with(true);

        $this->model->execute($this->observerMock);
    }

    /**
     * Test with disabled gift wrapping setting.
     *
     * @return void
     */
    public function testPrepareGiftOptionsWithGiftWrappingSettingDisabled()
    {
        $this->observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($this->eventMock));
        $this->eventMock->expects($this->once())->method('getEntity')->will($this->returnValue($this->entityMock));
        $this->entityMock->expects($this->never())->method('getQuote')->will($this->returnValue($this->quoteMock));
        $this->helperDataMock->expects($this->once())
            ->method('isGiftWrappingAvailableForOrder')->will($this->returnValue(false));
        $this->quoteMock->expects($this->never())->method('getIsVirtual')->will($this->returnValue(true));
        $this->entityMock->expects($this->never())->method('setIsGiftOptionsAvailable')->with(true);

        $this->model->execute($this->observerMock);
    }
}
