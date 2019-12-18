<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Constraint;

use Magento\Mtf\Constraint\AbstractConstraint;
use Magento\Catalog\Test\Page\Product\CatalogProductView;
use Magento\Catalog\Test\Fixture\CatalogProductSimple;
use Magento\Staging\Test\Fixture\Update;
use Magento\Mtf\Client\BrowserInterface;
use Magento\Store\Test\Fixture\Website;
use Magento\Mtf\Util\Command\Cli\Cron;

/**
 * Assert that product info is correct in frontend according to specified update time and website.
 */
class AssertProductCorrectAfterUpdate extends AbstractConstraint
{
    /**
     * Assert that product info is correct in frontend according to specified update time and website.
     *
     * @param CatalogProductSimple $product
     * @param Update $update
     * @param CatalogProductView $catalogProductView
     * @param Cron $cron
     * @param BrowserInterface $browser
     * @param Website|null $customWebsite [optional]
     * @param CatalogProductSimple|null $productUpdate [optional]
     * @param CatalogProductSimple|null $productUpdateForCustomWebsite [optional]
     * @return void
     */
    public function processAssert(
        CatalogProductSimple $product,
        Update $update,
        CatalogProductView $catalogProductView,
        Cron $cron,
        BrowserInterface $browser,
        Website $customWebsite = null,
        CatalogProductSimple $productUpdate = null,
        CatalogProductSimple $productUpdateForCustomWebsite = null
    ) {
        $timeToSleep = strtotime($update->getStartTime()) - time();

        if ($timeToSleep > 0) {
            // Wait for product update time comes
            sleep($timeToSleep);
        }

        // Run cron twice to force the update
        $cron->run();
        $cron->run();
        $browser->open($_ENV['app_frontend_url'] . $product->getUrlKey() . '.html');

        $expectedPrice = $productUpdate->getPrice() ?? $product->getPrice();
        \PHPUnit\Framework\Assert::assertEquals(
            $expectedPrice,
            $catalogProductView->getViewBlock()->getPriceBlock()->getPrice(),
            $update->getName() . ' expected price is not correct.'
        );

        $expectedName = $productUpdate->getName() ?? $product->getName();
        \PHPUnit\Framework\Assert::assertEquals(
            $expectedName,
            $catalogProductView->getViewBlock()->getProductName(),
            $expectedName . ' expected name is not correct.'
        );

        if ($customWebsite && $productUpdateForCustomWebsite) {
            $websiteCode = 'websites/' . $customWebsite->getCode() . '/';
            $browser->open($_ENV['app_frontend_url'] . $websiteCode . $product->getUrlKey() . '.html');

            $expectedPrice = $productUpdateForCustomWebsite->getPrice() ?? $product->getPrice();

            \PHPUnit\Framework\Assert::assertEquals(
                $expectedPrice,
                $catalogProductView->getViewBlock()->getPriceBlock()->getPrice(),
                $update->getName() . ' expected price is not correct for website ' . $websiteCode . '.'
            );

            $expectedName = $productUpdateForCustomWebsite->getName() ?? $product->getName();
            \PHPUnit\Framework\Assert::assertEquals(
                $expectedName,
                $catalogProductView->getViewBlock()->getProductName(),
                $expectedName . ' expected name is not correct for website ' . $websiteCode . '.'
            );
        }
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return 'Expected product info is correct.';
    }
}
