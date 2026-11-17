/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 * Shows the free shipping progress. Only reads the "free-shipping-bar" customer-data section.
 *
 * kind "region": the standard mini cart region.  kind "page": cart / product page mount (moves itself when a
 * custom position is set).  kind "footer": mini cart mount used only when a custom position is set.
 * With a custom position the bar is shown only once that element is found; if it is never found nothing is shown.
 * Nothing in here may throw: a missing element or a bad selector simply means "show nothing".
 */
define([
    'uiComponent',
    'jquery',
    'ko',
    'Magento_Customer/js/customer-data',
    'mage/cookies'
], function (Component, $, ko, customerData) {
    'use strict';

    var SECTION = 'free-shipping-bar',
        MAX_TRIES = 40,
        RETRY_MS = 500;

    return Component.extend({
        defaults: {
            template: 'GetDropFlow_FreeShippingBar/bar',
            position: '',
            kind: 'page'
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.bar = customerData.get(SECTION);
            this.placed = ko.observable(false);
            this.isVisible = this.visibleComputed();
            this.refreshIfStale(this.bar());
            this.bar.subscribe(this.startPlacing.bind(this));
            this.startPlacing();

            return this;
        },

        /**
         * @return {Function}
         */
        visibleComputed: function () {
            var self = this;

            return ko.pureComputed(function () {
                var data = self.bar(),
                    custom = !!self.selector(),
                    show = !!(data && data.visible && data.positions && data.positions[self.position]);

                if (!show) {
                    return false;
                }
                // The mini cart never shows the "free shipping on orders over X" hint of an empty cart.
                if (self.position === 'minicart' && data.state === 'empty') {
                    return false;
                }
                if (self.kind === 'region') {
                    return !custom;
                }
                if (self.kind === 'footer') {
                    // spare mount, used only when a custom mini cart position is set and found
                    return custom && self.placed();
                }

                return !custom || self.placed();
            });
        },

        /**
         * @return {String} custom CSS selector for this position, or ''
         */
        selector: function () {
            var data = this.bar();

            return data && data.selectors && data.selectors[this.position] ? String(data.selectors[this.position]) : '';
        },

        /**
         * Looks for the custom element now and a few more times (themes render some parts late).
         */
        startPlacing: function () {
            var self = this,
                tries = 0,
                selector = this.selector();

            this.placed(false);
            if (!selector || this.kind === 'region') {
                return;
            }
            (function attempt() {
                if (self.selector() !== selector) {
                    return;
                }
                if (self.placeOnce(selector)) {
                    self.placed(true);

                    return;
                }
                tries += 1;
                if (tries < MAX_TRIES) {
                    window.setTimeout(attempt, RETRY_MS);
                }
            }());
        },

        /**
         * @param {String} selector
         * @return {Boolean} true when the bar now sits at the custom position
         */
        placeOnce: function (selector) {
            var data = this.bar(),
                placement = data && data.placement ? data.placement : 'after',
                mount,
                target;

            try {
                mount = document.querySelector('[data-gdf-mount="fsb_' + this.position + '"]');
                target = document.querySelector(selector);
                if (!mount || !target || target === mount || mount.contains(target)) {
                    return false;
                }
                if (placement === 'before') {
                    target.parentNode.insertBefore(mount, target);
                } else if (placement === 'prepend') {
                    target.insertBefore(mount, target.firstChild);
                } else if (placement === 'append') {
                    target.appendChild(mount);
                } else {
                    target.parentNode.insertBefore(mount, target.nextSibling);
                }

                return true;
            } catch (e) {
                return false;
            }
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
