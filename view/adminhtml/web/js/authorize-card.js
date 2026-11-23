/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 * The "Authorization" card on the settings page (download edition only). Talks only to the shop's own admin.
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element),
            t = config.texts,
            card = config.card,
            pending = config.pending,
            message = '',
            timer = null,
            busy = false,
            fill,
            p,
            btn,
            render,
            call,
            handle,
            schedule;

        /**
         * @param {String} template with %1 %2
         * @param {Array} values
         * @return {String}
         */
        fill = function (template, values) {
            return values.reduce(function (text, value, i) {
                return text.replace('%' + (i + 1), value);
            }, template);
        };

        /**
         * @param {String} text
         * @param {String} [cls]
         * @return {jQuery}
         */
        p = function (text, cls) {
            return $('<p></p>', cls ? {'class': cls} : {}).text(text);
        };

        /**
         * @param {String} label
         * @param {Function} onClick
         * @param {Boolean} [primary]
         * @return {jQuery}
         */
        btn = function (label, onClick, primary) {
            return $('<button type="button" class="action-default scalable"></button>')
                .toggleClass('primary', !!primary).text(label).prop('disabled', busy).on('click', onClick);
        };

        /**
         * @param {Object} data
         * @return {jQuery}
         */
        call = function (data) {
            busy = true;
            render();
            return $.ajax({
                url: config.url,
                type: 'POST',
                dataType: 'json',
                global: false,
                data: $.extend({'form_key': config.formKey}, data),
                error: function () {
                    return;
                }
            }).always(function () {
                busy = false;
            });
        };

        /**
         * Applies an answer of the endpoint.
         *
         * @param {Object} data
         * @param {String} op
         */
        handle = function (data, op) {
            var r = data && data.result;

            if (!data || !data.card) {
                message = t.r_unreachable;
                pending = null;
                render();
                return;
            }
            card = data.card;
            if (r === 'waiting' && data.url) {
                pending = {'user_code': data.user_code, url: data.url, 'expires_in': data.expires_in, interval: data.interval};
                message = '';
                if (op === 'start') {
                    window.open(data.url, '_blank', 'noopener');
                }
                schedule();
            } else if (r === 'waiting') {
                schedule();
            } else if (r === 'unreachable' && pending && op === 'poll') {
                schedule();
            } else {
                pending = null;
                message = t['r_' + r] || '';
            }
            render();
        };

        /**
         * Asks the shop again after the interval, until the merchant has decided.
         */
        schedule = function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                call({op: 'poll'}).done(function (d) {
                    handle(d, 'poll');
                }).fail(function () {
                    handle(null, 'poll');
                });
            }, ((pending && pending.interval) || 3) * 1000);
        };

        render = function () {
            var box = $('<div class="gdf-fsb-card"></div>'),
                adv;

            if (pending) {
                box.append($('<strong></strong>').text(t.waitingTitle))
                    .append(p(fill(t.waitingText, [pending['user_code']])))
                    .append($('<p class="gdf-fsb-code"></p>').append($('<strong></strong>').text(pending['user_code'])))
                    .append(btn(t.openAgain, function () {
                        window.open(pending.url, '_blank', 'noopener');
                    }))
                    .append(' ')
                    .append(btn(t.cancel, function () {
                        window.clearTimeout(timer);
                        pending = null;
                        call({op: 'cancel'}).done(function (d) {
                            handle(d, 'cancel');
                        });
                    }));
            } else if (card.authorized) {
                box.append($('<strong></strong>').text(t.authorizedTitle));
                if (card.plan) {
                    box.append(p(fill(t.plan, [card.plan])));
                }
                if (card.offline) {
                    box.append(p(fill(t.offlineText, [String(card.offline_days_left)]), 'message message-warning'));
                }
                if (card.lapsed) {
                    box.append(p(fill(t.lapsedText, [card.lapsed.plan, card.lapsed.ended]), 'message message-notice gdf-fsb-lapsed'));
                }
                box.append(btn(t.checkNow, function () {
                    call({op: 'check'}).done(function (d) {
                        handle(d, 'check');
                    });
                })).append(' ').append(btn(t.disconnect, function () {
                    if (window.confirm(t.disconnectConfirm)) {
                        call({op: 'disconnect'}).done(function (d) {
                            handle(d, 'disconnect');
                        });
                    }
                }));
            } else {
                box.append($('<strong></strong>').text(t.lockedTitle));
                if (card.rejected) {
                    box.append(p(t.rejectedText, 'message message-warning'));
                }
                box.append(p(t.lockedText))
                    .append(btn(t.login, function () {
                        call({op: 'start'}).done(function (d) {
                            handle(d, 'start');
                        });
                    }, true))
                    .append(p(t.privacy, 'note'));
                adv = $('<details class="gdf-fsb-advanced"></details>').append($('<summary></summary>').text(t.advanced));
                adv.append($('<label></label>').text(t.keyLabel + ' '))
                    .append($('<input type="password" class="input-text" autocomplete="off" maxlength="200">'))
                    .append(' ')
                    .append(btn(t.keySave, function () {
                        var val = String(adv.find('input').val() || '');

                        call({op: 'key', key: val}).done(function (d) {
                            handle(d, 'key');
                        });
                    }));
                box.append(adv);
            }
            if (busy) {
                box.append(p(t.working, 'note'));
            }
            if (message) {
                box.append(p(message, 'gdf-fsb-result'));
            }
            $root.empty().append(box);
        };

        render();
        if (pending) {
            schedule();
        }
    };
});
