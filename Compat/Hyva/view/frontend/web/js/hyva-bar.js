/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 * Alpine.js component "gdfFsb" for Hyva themes. Reads the "free-shipping-bar" customer section data
 * (delivered by the main module) and falls back to the empty-cart state rendered into the page.
 * Nothing in here may throw: anything unexpected means "show nothing".
 */
(function () {
    'use strict';

    var SECTION = 'free-shipping-bar',
        MAX_TRIES = 40,
        RETRY_MS = 500,
        reloadAsked = false;

    function component() {
        return {
            position: '',
            kind: 'page',
            initial: null,
            data: null,
            placed: false,

            init: function () {
                var el = this.$el;

                this.position = el.getAttribute('data-position') || '';
                this.kind = el.getAttribute('data-kind') || 'page';
                try {
                    this.initial = JSON.parse(el.getAttribute('data-initial') || 'null');
                } catch (e) {
                    this.initial = null;
                }
                this.data = this.initial;
                this.read();
                this.startPlacing();
            },

            onPrivateContent: function (event) {
                var all = event && event.detail ? event.detail.data : null;

                this.read(all);
            },

            /**
             * @param {Object} [all] all section data of the event; defaults to the Alpine privateContent store
             */
            read: function (all) {
                var section = null;

                try {
                    if (!all && this.$store && this.$store.privateContent && this.$store.privateContent.loaded) {
                        all = this.$store.privateContent;
                    }
                    section = all ? all[SECTION] : null;
                } catch (e) {
                    section = null;
                }
                if (section && typeof section === 'object' && 'visible' in section) {
                    this.data = section;
                    this.refreshIfStale(section);
                } else {
                    this.data = this.initial;
                }
                this.startPlacing();
            },

            /** Visitor switched store view (language/currency) since the data was saved in the browser. */
            refreshIfStale: function (section) {
                var init = this.initial;

                if (reloadAsked || !init || !section.store_code || !init.store_code || section.store_code === init.store_code) {
                    return;
                }
                reloadAsked = true;
                try {
                    if (window.hyva && window.hyva.setCookie) {
                        window.hyva.setCookie('mage-cache-sessid', '', -1, true);
                    }
                    window.dispatchEvent(new Event('reload-customer-section-data'));
                } catch (e) {
                    // nothing to do: the next page load refreshes the data
                }
            },

            get hasData() {
                return !!(this.data && this.data.visible && this.data.positions && this.data.positions[this.position]);
            },

            get selector() {
                var d = this.data;

                return d && d.selectors && d.selectors[this.position] ? String(d.selectors[this.position]) : '';
            },

            get isVisible() {
                if (!this.hasData) {
                    return false;
                }
                // The standard mini cart region does not exist in Hyva, so the mini cart only shows at a custom position.
                if (this.kind === 'footer') {
                    return !!this.selector && this.placed;
                }

                return !this.selector || this.placed;
            },

            get message() {
                return this.data && this.data.message ? this.data.message : '';
            },
            get percent() {
                return this.data ? Number(this.data.percent) || 0 : 0;
            },
            get percentText() {
                return this.percent + '%';
            },
            get showTrack() {
                return !!(this.data && this.data.show_bar && this.data.state !== 'empty');
            },
            get showPercent() {
                return !!(this.data && this.data.show_percent && this.data.state !== 'empty');
            },
            get barClass() {
                return {'gdf-fsb-bar--done': !!(this.data && this.data.state === 'done')};
            },
            get fillStyle() {
                return {width: this.percent + '%', backgroundColor: this.data && this.data.bar_color ? this.data.bar_color : ''};
            },

            startPlacing: function () {
                var self = this,
                    tries = 0,
                    selector = this.selector;

                this.placed = false;
                if (!selector) {
                    return;
                }
                (function attempt() {
                    if (self.selector !== selector) {
                        return;
                    }
                    if (self.placeOnce(selector)) {
                        self.placed = true;

                        return;
                    }
                    tries += 1;
                    if (tries < MAX_TRIES) {
                        window.setTimeout(attempt, RETRY_MS);
                    }
                }());
            },

            placeOnce: function (selector) {
                var placement = this.data && this.data.placement ? this.data.placement : 'after',
                    mount,
                    target;

                try {
                    mount = this.$el;
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
            }
        };
    }

    if (window.Alpine && window.Alpine.data) {
        window.Alpine.data('gdfFsb', component);
    } else {
        window.addEventListener('alpine:init', function () {
            window.Alpine.data('gdfFsb', component);
        }, {once: true});
    }
}());
