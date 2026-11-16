/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 * Shows the free shipping progress. Only reads the "free-shipping-bar" customer-data section.
 */
define([
    'uiComponent',
    'jquery',
    'ko',
    'Magento_Customer/js/customer-data',
    'mage/cookies'
], function (Component, $, ko, customerData) {
    'use strict';

    var SECTION = 'free-shipping-bar';

    return Component.extend({
        defaults: {
            template: 'GetDropFlow_FreeShippingBar/bar',
            position: ''
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.bar = customerData.get(SECTION);
            this.isVisible = this.visibleComputed();
            this.refreshIfStale(this.bar());

            return this;
        },

        /**
         * @return {Function}
         */
        visibleComputed: function () {
            var self = this;

            return ko.pureComputed(function () {
                var data = self.bar();

                return !!(data && data.visible && data.positions && data.positions[self.position]);
            });
        },

        /**
         * Reload when nothing is stored yet, or when the visitor switched store view
         * (language / currency) since the data was saved in the browser.
         *
         * @param {Object} data
         */
        refreshIfStale: function (data) {
            var storeCookie = $.mage.cookies.get('store'),
                lang = (document.documentElement.getAttribute('lang') || '').split('-')[0].toLowerCase();

            if (!data || !data.store_code ||
                (storeCookie && storeCookie !== data.store_code) ||
                (lang && data.lang && lang !== data.lang)
            ) {
                customerData.reload([SECTION], false);
            }
        }
    });
});
