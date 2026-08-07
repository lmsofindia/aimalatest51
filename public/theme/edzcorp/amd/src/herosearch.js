// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Frontpage hero search — live typeahead over courses + categories.
 *
 * Debounces input, calls local_edzallcourse_search_suggest_v2, and shows a
 * keyboard-navigable dropdown of results. Selecting a result opens it;
 * pressing Enter with nothing selected submits the form to the catalogue
 * (pre-filtered by the typed query).
 *
 * @module     theme_edzcorp/herosearch
 * @copyright  2026 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax'], function(Ajax) {
    'use strict';

    var SEL = {
        FORM:    '[data-region="edz-hero-search"]',
        INPUT:   '[data-region="edz-hero-search-input"]',
        SUGGEST: '[data-region="edz-hero-suggest"]'
    };

    var ICON = {
        course:   'fa-graduation-cap',
        category: 'fa-folder-open'
    };

    var DEBOUNCE = 220;   // ms.
    var MINCHARS = 2;

    /**
     * Wire a single hero search form.
     *
     * @param {HTMLElement} form
     * @param {object} config
     */
    var setup = function(form, config) {
        var input = form.querySelector(SEL.INPUT);
        var box   = form.querySelector(SEL.SUGGEST);
        if (!input || !box) {
            return;
        }

        var fallbackUrl = (config && config.fallbackUrl) || form.getAttribute('action') || '';
        var timer = null;
        var seq = 0;             // Request sequence — ignore stale responses.
        var items = [];          // Current result data.
        var active = -1;         // Highlighted index, -1 = none.

        /**
         * Hide and empty the dropdown.
         */
        var close = function() {
            box.hidden = true;
            box.innerHTML = '';
            items = [];
            active = -1;
            input.setAttribute('aria-expanded', 'false');
        };

        /**
         * Move the highlight and reflect it in the DOM.
         *
         * @param {number} next
         */
        var setActive = function(next) {
            var options = box.querySelectorAll('.edz-top__suggest-item');
            if (!options.length) {
                return;
            }
            if (next < 0) {
                next = options.length - 1;
            }
            if (next >= options.length) {
                next = 0;
            }
            active = next;
            options.forEach(function(el, i) {
                var on = (i === active);
                el.classList.toggle('is-active', on);
                el.setAttribute('aria-selected', on ? 'true' : 'false');
            });
        };

        /**
         * Navigate to a result URL.
         *
         * @param {string} url
         */
        var go = function(url) {
            if (url) {
                window.location.href = url;
            }
        };

        /**
         * Submit to the catalogue with the current query.
         */
        var submitFallback = function() {
            var q = (input.value || '').trim();
            if (!fallbackUrl) {
                return;
            }
            var join = fallbackUrl.indexOf('?') === -1 ? '?' : '&';
            go(fallbackUrl + join + 'q=' + encodeURIComponent(q));
        };

        /**
         * Render the results list.
         *
         * @param {Array} data
         */
        var render = function(data) {
            items = data || [];
            active = -1;
            box.innerHTML = '';

            if (!items.length) {
                close();
                return;
            }

            items.forEach(function(it, i) {
                var a = document.createElement('a');
                a.className = 'edz-top__suggest-item';
                a.setAttribute('href', it.url);
                a.setAttribute('role', 'option');
                a.setAttribute('aria-selected', 'false');
                a.setAttribute('data-index', i);

                var ico = document.createElement('span');
                ico.className = 'edz-top__suggest-ico';
                var faicon = ICON[it.type] || ICON.course;
                ico.innerHTML = '<i class="fa-solid ' + faicon + '" aria-hidden="true"></i>';

                var textwrap = document.createElement('span');
                textwrap.className = 'edz-top__suggest-text';

                var name = document.createElement('span');
                name.className = 'edz-top__suggest-name';
                name.textContent = it.name;
                textwrap.appendChild(name);

                if (it.meta) {
                    var meta = document.createElement('span');
                    meta.className = 'edz-top__suggest-meta';
                    meta.textContent = it.meta;
                    textwrap.appendChild(meta);
                }

                a.appendChild(ico);
                a.appendChild(textwrap);

                // Use mousedown (fires before blur) so the click isn't lost.
                a.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    go(it.url);
                });

                box.appendChild(a);
            });

            box.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        };

        /**
         * Query the server for suggestions.
         *
         * @param {string} q
         */
        var fetch = function(q) {
            var mine = ++seq;
            Ajax.call([{
                methodname: 'local_edzallcourse_search_suggest_v2',
                args: {q: q, limit: 8}
            }], true, false)[0].then(function(resp) {   // loginrequired=false → public no-login endpoint
                if (mine !== seq) {
                    return; // Superseded.
                }
                render(resp.items);
                return;
            }).catch(function() {
                if (mine === seq) {
                    close();
                }
            });
        };

        input.addEventListener('input', function() {
            var q = (input.value || '').trim();
            window.clearTimeout(timer);
            if (q.length < MINCHARS) {
                close();
                return;
            }
            timer = window.setTimeout(function() {
                fetch(q);
            }, DEBOUNCE);
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown') {
                if (!box.hidden && items.length) {
                    e.preventDefault();
                    setActive(active + 1);
                }
            } else if (e.key === 'ArrowUp') {
                if (!box.hidden && items.length) {
                    e.preventDefault();
                    setActive(active - 1);
                }
            } else if (e.key === 'Enter') {
                if (!box.hidden && active >= 0 && items[active]) {
                    e.preventDefault();
                    go(items[active].url);
                }
                // Otherwise let the form submit naturally (fallback to catalogue).
            } else if (e.key === 'Escape') {
                close();
            }
        });

        // Close when focus leaves the widget (slight delay for click-through).
        input.addEventListener('blur', function() {
            window.setTimeout(close, 150);
        });

        // Guarantee the catalogue fallback even if the browser blocks native submit.
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitFallback();
        });
    };

    return {
        /**
         * Initialise every hero search form on the page.
         *
         * @param {object} config {fallbackUrl}
         */
        init: function(config) {
            var forms = document.querySelectorAll(SEL.FORM);
            forms.forEach(function(form) {
                setup(form, config || {});
            });
        }
    };
});
