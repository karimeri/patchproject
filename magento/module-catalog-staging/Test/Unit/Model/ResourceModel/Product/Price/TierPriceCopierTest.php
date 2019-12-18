<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\ResourceModel\Product\Price;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\CatalogStaging\Model\ResourceModel\Product\Price\TierPriceCopier;

class TierPriceCopierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\DB\Select|MockObject
     */
    private $selectMock;

    /**
     * @var \Magento\Catalog\Model\Product|MockObject
     */
    private $entityMock;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var \Magento\Framework\EntityManager\EntityManager|MockObject
     */
    private $metadataMock;

    /**
     * @var \Magento\Framework\App\ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var \Magento\Framework\DB\Statement\Pdo\Mysql|MockObject
     */
    private $resultMock;

    /**
     * @var \Magento\CatalogStaging\Model\ResourceModel\Product\Price\TierPriceCopier
     */
    private $tierPriceCopier;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->metadataPoolMock = $this->getMockBuilder(\Magento\Framework\EntityManager\MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(\Magento\Framework\EntityManager\EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMockBuilder(\Magento\Framework\DB\Statement\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tierPriceCopier = $this->objectManager->getObject(
            TierPriceCopier::class,
            [
                'metadataPool' => $this->metadataPoolMock,
                'resourceConnection' => $this->resourceMock
            ]
        );
    }

    public function testCopy()
    {
        $linkField = "row_id";
        $fromRowId = 1;
        $toRowId = 2;
        $entityConnectionName = 'default';
        $tableName = 'catalog_product_entity_tier_price';
        $query = "INSERT INTO `catalog_product_entity_tier_price` (`all_groups`, `customer_group_id`"
            . ", `qty`, `value`, `website_id`, `percentage_value`, `row_id`)VALUES('','2','1','10','1','','1')";
        $insertColumns = [
            'all_groups' => 'all_groups',
            'customer_group_id' => 'customer_group_id',
            'qty' => 'qty',
            'value' => 'value',
            'website_id' => 'website_id',
            'percentage_value' => 'percentage_value',
            'row_id' => new \Zend_Db_Expr($toRowId)
        ];

        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->willReturn($this->metadataMock);

        $this->metadataMock->expects($this->exactly(3))
            ->method('getLinkField')
            ->willReturn($linkField);

        $this->entityMock->expects($this->once())
            ->method('getOrigData')
            ->with($linkField)
            ->willReturn($fromRowId);

        $this->entityMock->expects($this->once())
            ->method('getData')
            ->with($linkField)
            ->willReturn($toRowId);

        $this->metadataMock->expects($this->once())
            ->method('getEntityConnectionName')
            ->willReturn($entityConnectionName);

        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with($entityConnectionName)
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->resourceMock->expects($this->exactly(2))
            ->method('getTableName')
            ->with($tableName)
            ->willReturn($tableName);

        $this->selectMock->expects($this->once())->method('from')->with(
            $tableName,
            ''
        )->willReturnSelf();

        $this->selectMock->expects($this->once())->method('where')->with(
            $linkField . ' = ?',
            $fromRowId
        )->willReturnSelf();

        $this->selectMock->expects($this->once())
            ->method('columns')
            ->with($insertColumns)
            ->willReturnSelf();

        $this->selectMock->expects($this->once())
            ->method('insertFromSelect')
            ->with('catalog_product_entity_tier_price', array_keys($insertColumns))
            ->willReturn($query);

        $this->connectionMock->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn($this->resultMock);

        $this->assertTrue($this->tierPriceCopier->copy($this->entityMock));
    }
}
