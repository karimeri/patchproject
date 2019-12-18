<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Test\Unit\Model\Config\ContentType\AdditionalData\Provider;

use Magento\BannerPageBuilder\Model\Config\ContentType\AdditionalData\Provider\DynamicBlockDataUrl;

/**
 * Test basic functionality of the DynamicBlockDataUrl class
 */
class DynamicBlockDataUrlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     */
    public function testGetData()
    {
        $urlMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)->getMock();
        $urlMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('pagebuilder/contenttype_dynamicblock/metadata')
            ->willReturn('foo');

        $dataUrl = new DynamicBlockDataUrl($urlMock);
        $actual = $dataUrl->getData('bar');

        $this->assertSame(['bar' => 'foo'], $actual);
    }
}
