<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Reward\Model\Reward;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CustomerRegisterTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerRegisterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerRegistryMock;

    /**
     * @var \Magento\Reward\Model\Plugin\CustomerRegister
     */
    protected $subject;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $accountManagementMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerModelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerResourceMock;

    /**
     * Set up test
     */
    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rewardDataMock = $this->createMock(
            \Magento\Reward\Helper\Data::class
        );
        $this->storeManagerMock = $this->createMock(
            \Magento\Store\Model\StoreManagerInterface::class
        );
        $this->rewardFactoryMock = $this->createPartialMock(
            \Magento\Reward\Model\RewardFactory::class,
            ['create']
        );
        $this->loggerMock = $this->createMock(
            \Psr\Log\LoggerInterface::class
        );
        $this->customerRegistryMock = $this->createMock(
            \Magento\Customer\Model\CustomerRegistry::class
        );
        $this->accountManagementMock = $this->createMock(
            \Magento\Customer\Model\AccountManagement::class
        );

        $this->customerMock = $this->createMock(
            \Magento\Customer\Model\Data\Customer::class
        );

        $this->storeMock = $this->createMock(
            \Magento\Store\Model\Store::class
        );

        $this->customerModelMock = $this->createPartialMock(
            \Magento\Customer\Model\Customer::class,
            [
                'setRewardUpdateNotification',
                'setRewardWarningNotification',
                'getResource'
            ]
        );

        $this->customerResourceMock = $this->createMock(
            \Magento\Customer\Model\ResourceModel\Customer::class
        );

        $this->subject = $objectManager->getObject(
            \Magento\Reward\Model\Plugin\CustomerRegister::class,
            [
                'rewardData' => $this->rewardDataMock,
                'storeManager' => $this->storeManagerMock,
                'rewardFactory' => $this->rewardFactoryMock,
                'logger' => $this->loggerMock,
                'customerRegistry' => $this->customerRegistryMock
            ]
        );
    }

    public function testUpdateRewardPointsWhenRewardDisabledInFront()
    {
        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->will($this->returnValue(false));

        $this->assertEquals(
            $this->customerMock,
            $this->subject->afterCreateAccountWithPasswordHash(
                $this->accountManagementMock,
                $this->customerMock
            )
        );
    }

    public function testUpdateRewardPointsSuccess()
    {
        $notificationConfig = 1;
        $websiteId = 74;
        $customerEmail = 'test@test.tst';

        $this->customerMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($customerEmail);
        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->willReturn(true);
        $this->storeManagerMock->expects($this->atLeastOnce())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->rewardDataMock->expects($this->once())
            ->method('getNotificationConfig')
            ->with('subscribe_by_default', $websiteId)
            ->willReturn($notificationConfig);
        $this->customerRegistryMock->expects($this->once())
            ->method('retrieveByEmail')
            ->with($customerEmail)
            ->willReturn($this->customerModelMock);
        $this->customerModelMock->expects($this->once())
            ->method('setRewardUpdateNotification')
            ->with($notificationConfig);
        $this->customerModelMock->expects($this->once())
            ->method('setRewardWarningNotification')
            ->with($notificationConfig);
        $this->customerModelMock->expects($this->exactly(2))
            ->method('getResource')
            ->willReturn($this->customerResourceMock);

        $this->customerResourceMock->expects($this->exactly(2))
            ->method('saveAttribute')
            ->withConsecutive(
                [$this->customerModelMock, 'reward_update_notification'],
                [$this->customerModelMock, 'reward_warning_notification']
            );

        $this->rewardFactoryTest();

        $this->assertEquals(
            $this->customerMock,
            $this->subject->afterCreateAccountWithPasswordHash(
                $this->accountManagementMock,
                $this->customerMock
            )
        );
    }

    private function rewardFactoryTest()
    {
        $storeId = 42;
        $rewardMock = $this->createPartialMock(
            \Magento\Reward\Model\Reward::class,
            [
                'setCustomer',
                'setActionEntity',
                'setStore',
                'setAction',
                'updateRewardPoints',
                '__wakeup'
            ]
        );

        $this->rewardFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rewardMock);
        $rewardMock->expects($this->once())
            ->method('setCustomer')
            ->with($this->customerMock)
            ->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setActionEntity')
            ->with($this->customerMock)
            ->willReturnSelf();
        $this->storeMock->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $rewardMock->expects($this->once())
            ->method('setStore')
            ->with($storeId)
            ->willReturnSelf();
        $rewardMock->expects($this->once())
            ->method('setAction')
            ->with(Reward::REWARD_ACTION_REGISTER)
            ->willReturnSelf();
        $rewardMock->expects($this->once())->method('updateRewardPoints');
    }

    public function testUpdateRewardsThrowsException()
    {
        $notificationConfig = 1;
        $websiteId = 74;
        $exception = new \Exception('Something went wrong');

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->willReturn(true);
        $this->storeManagerMock->expects($this->atLeastOnce())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->rewardDataMock->expects($this->once())
            ->method('getNotificationConfig')
            ->with('subscribe_by_default', $websiteId)
            ->willReturn($notificationConfig);

        $this->customerRegistryMock->expects($this->once())
            ->method('retrieveByEmail')
            ->willReturn($this->customerModelMock);
        $this->customerModelMock->expects($this->exactly(2))
            ->method('getResource')
            ->willReturn($this->customerResourceMock);
        $this->rewardFactoryMock->expects($this->once())
            ->method('create')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->assertEquals(
            $this->customerMock,
            $this->subject->afterCreateAccountWithPasswordHash(
                $this->accountManagementMock,
                $this->customerMock
            )
        );
    }
}
