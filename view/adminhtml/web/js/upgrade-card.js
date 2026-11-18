/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 * The "Updates" card on the settings page (download edition only). Talks only to the shop's own admin.
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element),
            t = config.texts,
            lang = (document.documentElement.getAttribute('lang') || 'en').split('-')[0].toLowerCase(),
            timer = null,
            state = {};

        /**
         * @param {String} text
         * @param {Object} [attrs]
         * @return {jQuery}
         */
        function line(text, attrs) {
            return $('<p></p>', attrs || {}).text(text);
        }

        /**
         * @param {String} template with %1 %2
         * @param {Array} values
         * @return {String}
         */
        function fill(template, values) {
            return values.reduce(function (text, value, i) {
                return text.replace('%' + (i + 1), value);
            }, template);
        }

        /**
         * @param {Object} data
         * @return {String}
         */
        function reasonText(data) {
            var code = (data.blocked_by && data.blocked_by[0]) || data.error ||
                (data.status && data.status.reason) || '';

            return t.errors[code] || '';
        }

        /**
         * Draws the card for the data the server sent.
         *
         * @param {Object} data
         */
        function render(data) {
            var s = data.status || {},
                running = s.state === 'running' && !s.stale,
                $buttons = $('<p></p>'),
                $check = $('<button type="button" class="action-default scalable"></button>').text(t.check),
                note = data.notes && (data.notes[lang] || data.notes.en),
                why;

            state = data;
            $root.empty().append($('<strong></strong>').text(t.title));
            $root.append(line(t.current + ' ' + (data.current || config.current)));
            if (!config.enabled) {
                $root.append(line(t.off));

                return;
            }
            if (data.latest) {
                $root.append(line(t.latest + ' ' + data.latest));
            }
            if (running) {
                $root.append(line(t.working + ' ' + fill(t.step, [s.step, s.total]) + ' ' + (s.label || '')));
                schedule();

                return;
            }
            if (s.state === 'done') {
                $root.append(line(fill(t.done, [s.to || data.latest])));
            } else if (s.state === 'rolled_back') {
                $root.append(line(t.rolledBack + ' ' + (t.errors[s.reason] || '')));
            } else if (s.state === 'needs_manual') {
                $root.append(line(t.needsManual));
            }
            if (data.newer && note) {
                $root.append(line(t.notes + ' ' + note));
            }
            why = reasonText(data);
            if (why && s.state !== 'rolled_back') {
                $root.append(line(why));
            }
            if (!data.checked_at && !data.error) {
                $root.append(line(t.notChecked));
            } else if (!data.newer && !data.error) {
                $root.append(line(t.upToDate));
            }
            $check.on('click', check);
            $buttons.append($check);
            if (data.newer && !(data.blocked_by && data.blocked_by.length) && s.state !== 'done') {
                $buttons.append(' ', $('<button type="button" class="action-primary"></button>')
                    .text(t.upgrade).on('click', start));
            } else if (data.blocked_by && data.blocked_by.length) {
                $root.append(line(t.manual));
            }
            $root.append($buttons);
        }

        /**
         * @param {String} url
         * @param {Function} done
         */
        function post(url, done) {
            $.ajax({
                url: url, type: 'POST', dataType: 'json', data: {form_key: config.formKey}
            }).done(done).fail(function () {
                // while the shop is in maintenance mode the answer can be an error page: keep asking
                if (state.status && state.status.state === 'running') {
                    schedule();
                } else {
                    render($.extend({}, state, {error: 'DOWNLOAD_FAILED'}));
                }
            });
        }

        /**
         * Asks the server for the newest version (the only place that goes to the internet).
         */
        function check() {
            $root.find('button').prop('disabled', true).first().text(t.checking);
            post(config.checkUrl, render);
        }

        /**
         * Starts the upgrade after a confirmation.
         */
        function start() {
            if (!window.confirm(t.confirm)) { // eslint-disable-line no-alert
                return;
            }
            post(config.startUrl, function (data) {
                render(data);
            });
        }

        /**
         * Polls the progress every few seconds while an upgrade runs.
         */
        function schedule() {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                post(config.statusUrl, render);
            }, 4000);
        }

        render({current: config.current, status: {state: 'idle'}, blocked_by: []});
        post(config.statusUrl, render);
    };
});
