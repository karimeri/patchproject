<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Banner\Test\Constraint;

use Magento\Mtf\Util\Command\Cli\Cache;
use Magento\CatalogSearch\Test\Page\AdvancedSearch;
use Magento\Cms\Test\Page\CmsIndex;
use Magento\Widget\Test\Fixture\Widget;
use Magento\Mtf\Constraint\AbstractConstraint;
use Magento\Mtf\Client\BrowserInterface;

/**
 * Check that created Banner Rotator widget displayed on frontend on Home page and on Advanced Search.
 */
class AssertWidgetBannerRotatorNotVisible extends AbstractConstraint
{
    /* tags */
    const SEVERITY = 'low';
    /* end tags */

    /**
     * Assert that created Banner Rotator widget displayed on frontent on Home page and on Advanced Search.
     *
     * @param CmsIndex $cmsIndex
     * @param AdvancedSearch $advancedSearch
     * @param Widget $widget
     * @param Cache $cache
     * @param BrowserInterface $browser
     * @return void
     * @throws \Exception
     */
    public function processAssert(
        CmsIndex $cmsIndex,
        AdvancedSearch $advancedSearch,
        Widget $widget,
        Cache $cache,
        BrowserInterface $browser
    ) {
        // Flush cache
        $cache->flush();

        $cmsIndex->open();
        $widgetText = $widget->getParameters()['entities'][0]->getStoreContents()['value_0'];
        \PHPUnit\Framework\Assert::assertFalse(
            $browser->waitUntil(
                function () use ($cmsIndex, $widget, $widgetText) {
                    return $cmsIndex->getWidgetView()->isWidgetVisible($widget, $widgetText) ? true : false;
                }
            ),
            'Widget with type ' . $widget->getCode() . ' is present on Home page.'
        );
        $cmsIndex->getFooterBlock()->openAdvancedSearch();
        $cmsIndex->getLinksBlock()->waitWelcomeMessage();
        $cmsIndex->getCmsPageBlock()->waitPageInit();
        \PHPUnit\Framework\Assert::assertFalse(
            $advancedSearch->getWidgetView()->isWidgetVisible($widget, $widgetText),
            'Widget with type ' . $widget->getCode() . ' is present on Advanced Search page.'
        );
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return "Widget is absent on Home page and on Advanced Search.";
    }
}
