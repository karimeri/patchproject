/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_PageBuilder/js/form/element/block-chooser',
    'mage/translate'
], function (BlockChooser, $t) {
    'use strict';

    return BlockChooser.extend({
        defaults: {
            metaRowIndex: 1
        },

        /**
         * Retrieves the classes that should be applied to the next created meta row
         * @return {String}
         */
        getNextMetaRowClasses: function () {
            return 'data-row' + (this.metaRowIndex++ % 2 === 0 ? '' : ' _odd-row');
        },

        /**
         * Determines the status label for the currently loaded block
         *
         * @returns {String}
         */
        getStatusLabel: function () {
            return this.meta()['is_enabled'] === '1' ? $t('Enabled') : $t('Disabled');
        },

        /**
         * Generates the customer segments display value
         * @return {String}
         */
        getCustomerSegments: function () {
            return this.meta()['customer_segments'].length > 0
                ? this.meta()['customer_segments'].join(',<br />')
                : $t('All Segments');
        },

        /**
         * Generates the catalog rules display value
         * @return {String}
         */
        getRelatedCatalogRules: function () {
            return this.meta()['related_catalog_rules'].join(',<br />');
        },

        /**
         * Generates the cart rules display value
         * @return {String}
         */
        getRelatedCartRules: function () {
            return this.meta()['related_cart_rules'].join(',<br />');
        }
    });
});
