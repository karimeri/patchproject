<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerCustomAttributes\Test\Unit\Controller\Adminhtml\Customer\Attribute;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\AttributeFactory;
use Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute\Validate;
use Magento\CustomerCustomAttributes\Model\Customer\Attribute\CompositeValidator;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Magento\Store\Model\WebsiteFactory;

/**
 * Unit tests for \Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Attribute\Validate class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValidateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Validate
     */
    private $controller;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistry;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfig;

    /**
     * @var AttributeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attrFactory;

    /**
     * @var SetFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attrSetFactory;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /** @var  WebsiteFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $websiteFactory;

    /**
     * @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $view;

    /**
     * @var CompositeValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $compositeValidatorMock;

    /**
     * @var NotProtectedExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    private $notProtectedExtensionMock;

    /**
     * @var Type|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityTypeMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->coreRegistry = $this->createMock(Registry::class);
        $this->entityTypeMock = $this->createMock(Type::class);
        $this->eavConfig = $this->createMock(Config::class);
        $this->eavConfig->expects($this->once())
            ->method('getEntityType')
            ->with('customer')
            ->willReturn($this->entityTypeMock);
        $this->attrFactory = $this->createPartialMock(AttributeFactory::class, ['create']);
        $this->attrSetFactory = $this->createPartialMock(SetFactory::class, ['create']);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam', 'getPostValue'])
            ->getMockForAbstractClass();
        $this->response = $this->getMockForAbstractClass(
            ResponseInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setBody']
        );

        $this->websiteFactory = $this->getMockBuilder(WebsiteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->view = $this->getMockForAbstractClass(ViewInterface::class, [], '', false);

        $objectHelper = new ObjectManager($this);
        $this->context = $objectHelper->getObject(
            Context::class,
            [
                'request' => $this->request,
                'response' => $this->response,
                'view' => $this->view,
            ]
        );
        $this->compositeValidatorMock = $this->createMock(CompositeValidator::class);
        $this->notProtectedExtensionMock = $this->createMock(NotProtectedExtension::class);

        $this->controller = $objectHelper->getObject(
            Validate::class,
            [
                'context' => $this->context,
                'coreRegistry' => $this->coreRegistry,
                'eavConfig' => $this->eavConfig,
                'attrFactory' => $this->attrFactory,
                'attrSetFactory' => $this->attrSetFactory,
                'websiteFactory' => $this->websiteFactory,
                'extensionValidator' => $this->notProtectedExtensionMock,
                'compositeValidator' => $this->compositeValidatorMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $attributeObjectMock = $this->prepareAttributeData();

        $this->compositeValidatorMock->expects($this->once())->method('validate')->with($attributeObjectMock);
        $this->response->expects($this->once())
            ->method('setBody')
            ->with('{"error":false}')
            ->willReturnSelf();

        $this->controller->execute();
    }

    /**
     * @return void
     */
    public function testExecuteWithException()
    {
        $attributeObjectMock = $this->prepareAttributeData();

        $this->compositeValidatorMock->expects($this->once())
            ->method('validate')
            ->with($attributeObjectMock)
            ->willThrowException(new LocalizedException(__('Attribute code already exists')));
        $this->request->expects($this->at(2))
            ->method('getParam')
            ->with('message_key', 'message')
            ->willReturn('message');
        $this->response->expects($this->once())
            ->method('setBody')
            ->with('{"error":true,"message":"Attribute code already exists"}')
            ->willReturnSelf();

        $this->controller->execute();
    }

    /**
     * @return Attribute|\PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareAttributeData()
    {
        $data = [
            'attribute_code' => 'test',
            'entity_type' => 1,
            'entity_type_id' => 1,
        ];
        $attributeObjectMock = $this->createMock(Attribute::class);

        $this->request->expects($this->once())->method('getPostValue')->willReturn($data);
        $this->entityTypeMock->expects($this->once())->method('getId')->willReturn($data['entity_type_id']);
        $this->attrFactory->expects($this->once())->method('create')->willReturn($attributeObjectMock);
        $this->request->expects($this->at(1))->method('getParam')->with('website')->willReturn(1);
        $attributeObjectMock->expects($this->once())->method('addData')->with($data)->willReturnSelf();

        return $attributeObjectMock;
    }
}
