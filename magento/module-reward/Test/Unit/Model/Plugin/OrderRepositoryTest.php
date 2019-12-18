<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Reward\Model\Plugin\OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderExtensionInterface;

/**
 * Class for testing plugin to order repository.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class OrderRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrderRepository
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var \Magento\Sales\Api\Data\OrderExtensionInterface
     */
    private $orderExtensionMock;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->orderMock = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [
                '__wakeup',
                'canUnhold',
                'isCanceled',
                'getState',
                'setForcedCanCreditmemo',
                'getBaseRwrdCrrncyAmntRefnded',
                'getBaseRwrdCrrncyAmtInvoiced',
                'getExtensionAttributes',
                'getData',
                'setExtensionAttributes',
                'getRewardPointsBalance',
                'getRewardCurrencyAmount',
                'getBaseRewardCurrencyAmount'
            ]
        );
        $this->orderRepositoryMock = $this->createMock(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $this->orderExtensionMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderExtensionInterface::class)
            ->setMethods([
                'setRewardPointsBalance',
                'setRewardCurrencyAmount',
                'setBaseRewardCurrencyAmount'
            ])
            ->getMockForAbstractClass();
        $this->model = new OrderRepository();
    }

    /**
     * Method for testing after get does not force.
     *
     * @param bool $canUnhold
     * @param bool $isCanceled
     * @param string $state
     * @dataProvider nonrefundableOrderStateDataProvider
     * @return void
     */
    public function testAfterGetDoesNotForceCreditmemoIfOrderStateDoesNotAllowIt(
        $canUnhold,
        $isCanceled,
        $state,
        $rewardAmountInvoiced,
        $rewardAmountRefunded
    ) {
        $orderMock = $this->orderMock;
        $orderMock->expects($this->any())->method('canUnhold')->willReturn($canUnhold);
        $orderMock->expects($this->any())->method('isCanceled')->willReturn($isCanceled);
        $orderMock->expects($this->any())->method('getState')->willReturn($state);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn($rewardAmountInvoiced);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn($rewardAmountRefunded);
        $orderMock->expects($this->never())->method('setForcedCanCreditmemo')->with(true);
        $orderMock->expects($this->atLeastOnce())->method('getExtensionAttributes')
            ->willReturn($this->orderExtensionMock);
        $orderMock->expects($this->atLeastOnce())->method('getData')->willReturn(10);
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setRewardPointsBalance')->willReturnSelf();
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setRewardCurrencyAmount')->willReturnSelf();
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setBaseRewardCurrencyAmount')
            ->willReturnSelf();
        $orderMock->expects($this->atLeastOnce())->method('setExtensionAttributes')->with($this->orderExtensionMock);

        $this->assertEquals($orderMock, $this->model->afterGet($this->orderRepositoryMock, $orderMock, 1));
    }

    /**
     * Data provider for method GetDoesNotForce.
     *
     * @return array
     */
    public function nonrefundableOrderStateDataProvider()
    {
        return [
            [false, false, Order::STATE_NEW, 10, 10],
            [false, false, Order::STATE_CLOSED, 20, 10],
            [false, true, Order::STATE_CLOSED, 10, 10],
            [true, false, Order::STATE_CLOSED, 20, 10],
            [true, true, Order::STATE_CLOSED, 10, 10],
        ];
    }

    /**
     * Method for testing after get does force.
     *
     * @return void
     */
    public function testAfterGetForcesCreditmemoIfOrderStateAllowsIt()
    {
        $orderMock = $this->orderMock;
        $orderMock->expects($this->any())->method('canUnhold')->willReturn(false);
        $orderMock->expects($this->any())->method('isCanceled')->willReturn(false);
        $orderMock->expects($this->any())->method('getState')->willReturn(Order::STATE_NEW);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmtInvoiced')->willReturn(100);
        $orderMock->expects($this->any())->method('getBaseRwrdCrrncyAmntRefnded')->willReturn(50);

        $orderMock->expects($this->once())->method('setForcedCanCreditmemo')->with(true);

        $orderMock->expects($this->once())->method('getExtensionAttributes')->willReturn($this->orderExtensionMock);
        $orderMock->expects($this->atLeastOnce())->method('getData')->willReturn(10);
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setRewardPointsBalance')->willReturnSelf();
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setRewardCurrencyAmount')->willReturnSelf();
        $this->orderExtensionMock->expects($this->atLeastOnce())->method('setBaseRewardCurrencyAmount')
            ->willReturnSelf();
        $orderMock->expects($this->atLeastOnce())->method('setExtensionAttributes')->with($this->orderExtensionMock);

        $this->assertEquals($orderMock, $this->model->afterGet($this->orderRepositoryMock, $orderMock, 1));
    }
}
