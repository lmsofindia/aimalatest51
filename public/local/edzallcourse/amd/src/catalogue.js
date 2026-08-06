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
 * EDZ Catalogue front end.
 *
 * Handles the hybrid drilldown: root/chip selection, search, sort and
 * numbered pagination. Each interaction asks the server to rebuild the
 * drilldown, grid and pager (via local_edzallcourse_get_view) and swaps the
 * three regions in place. State is mirrored to the URL for deep links.
 *
 * @module     local_edzallcourse/catalogue
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    var SELECTORS = {
        ROOT: '#edzcat',
        ROOTS: '[data-region="roots"]',
        SEARCH: '[data-region="search"]',
        SORT: '[data-region="sort"]',
        DRILL: '[data-region="drilldown"]',
        GRID: '[data-region="grid"]',
        PAGER: '[data-region="pager"]',
        CONTROLS: '.edzcat-controls'
    };

    var root = null;
    var state = {categoryid: 0, q: '', sort: '', page: 1};
    var seq = 0;          // Request sequence to ignore stale responses.
    var searchTimer = null;
    var railEl = null;        // Mobile collapsible rail container.
    var railCurrentEl = null; // Label showing the selected programme on mobile.

    /**
     * Sync the mobile rail toggle label to the active programme.
     */
    var updateRailLabel = function() {
        if (!railCurrentEl || !root) {
            return;
        }
        var active = root.querySelector('[data-region="roots"] .edzcat-root.is-active .edzcat-root-name');
        if (active) {
            railCurrentEl.textContent = active.textContent.trim();
        }
    };

    /**
     * Build the query string reflecting the current state.
     *
     * @return {string}
     */
    var buildUrl = function() {
        var params = [];
        if (state.categoryid) {
            params.push('category=' + encodeURIComponent(state.categoryid));
        }
        if (state.q) {
            params.push('q=' + encodeURIComponent(state.q));
        }
        if (state.sort) {
            params.push('sort=' + encodeURIComponent(state.sort));
        }
        if (state.page && state.page > 1) {
            params.push('page=' + encodeURIComponent(state.page));
        }
        return window.location.pathname + (params.length ? ('?' + params.join('&')) : '');
    };

    /**
     * Fetch the rebuilt regions and swap them in.
     *
     * @param {boolean} pushHistory Whether to push a new history entry.
     */
    var navigate = function(pushHistory) {
        var mine = ++seq;
        var grid = root.querySelector(SELECTORS.GRID);
        if (grid) {
            grid.setAttribute('aria-busy', 'true');
            grid.classList.add('edzcat-is-loading');
        }

        Ajax.call([{
            methodname: 'local_edzallcourse_get_view',
            args: {
                categoryid: state.categoryid,
                q: state.q,
                sort: state.sort,
                page: state.page
            }
        }])[0].then(function(resp) {
            if (mine !== seq) {
                return; // A newer request superseded this one.
            }
            state.categoryid = resp.categoryid;
            state.page = resp.page;

            root.querySelector(SELECTORS.DRILL).innerHTML = resp.drilldownhtml;
            var gridRegion = root.querySelector(SELECTORS.GRID);
            gridRegion.innerHTML = resp.gridhtml;
            gridRegion.setAttribute('aria-busy', 'false');
            gridRegion.classList.remove('edzcat-is-loading');
            root.querySelector(SELECTORS.PAGER).innerHTML = resp.pagerhtml;

            highlightRoot(resp.rootid);
            updateRailLabel();

            if (pushHistory !== false) {
                window.history.pushState({edzcat: Object.assign({}, state)}, '', buildUrl());
            }
            return;
        }).catch(function(err) {
            if (grid) {
                grid.setAttribute('aria-busy', 'false');
                grid.classList.remove('edzcat-is-loading');
            }
            Notification.exception(err);
        });
    };

    /**
     * Mark the active root in the left rail.
     *
     * @param {number} rootid
     */
    var highlightRoot = function(rootid) {
        var buttons = root.querySelectorAll(SELECTORS.ROOTS + ' .edzcat-root');
        buttons.forEach(function(btn) {
            var active = parseInt(btn.getAttribute('data-catid'), 10) === parseInt(rootid, 10);
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    /**
     * Select a category (root or chip). Resets to page 1.
     *
     * @param {number} categoryid
     */
    var selectCategory = function(categoryid) {
        state.categoryid = categoryid;
        state.page = 1;
        navigate(true);
    };

    /**
     * Wire delegated event handlers on the root container.
     */
    var bindEvents = function() {
        // Mobile: toggle the collapsible programmes dropdown.
        var railToggle = root.querySelector('[data-region="rail-toggle"]');
        if (railToggle && railEl) {
            railToggle.addEventListener('click', function() {
                var open = railEl.classList.toggle('is-open');
                railToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        // Root + chip clicks (delegated — regions get replaced).
        root.addEventListener('click', function(e) {
            var catEl = e.target.closest('[data-catid]');
            if (catEl && root.contains(catEl)) {
                e.preventDefault();
                var cid = parseInt(catEl.getAttribute('data-catid'), 10);
                if (!isNaN(cid)) {
                    selectCategory(cid);
                    // On mobile, collapse the programmes dropdown after choosing.
                    if (railEl) {
                        railEl.classList.remove('is-open');
                        var rt = root.querySelector('[data-region="rail-toggle"]');
                        if (rt) { rt.setAttribute('aria-expanded', 'false'); }
                    }
                }
                return;
            }
            var pageEl = e.target.closest('[data-page]');
            if (pageEl && root.contains(pageEl)) {
                e.preventDefault();
                if (pageEl.classList.contains('is-disabled') || pageEl.classList.contains('is-active')) {
                    return;
                }
                var p = parseInt(pageEl.getAttribute('data-page'), 10);
                if (!isNaN(p)) {
                    state.page = p;
                    navigate(true);
                    var controls = root.querySelector(SELECTORS.CONTROLS);
                    if (controls) {
                        window.scrollTo({top: controls.getBoundingClientRect().top + window.pageYOffset - 80, behavior: 'smooth'});
                    }
                }
            }
        });

        // Debounced search.
        var search = root.querySelector(SELECTORS.SEARCH);
        if (search) {
            search.addEventListener('input', function() {
                var val = search.value || '';
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(function() {
                    state.q = val.trim();
                    state.page = 1;
                    navigate(true);
                }, 250);
            });
        }

        // Sort.
        var sort = root.querySelector(SELECTORS.SORT);
        if (sort) {
            sort.addEventListener('change', function() {
                state.sort = sort.value || '';
                state.page = 1;
                navigate(true);
            });
        }

        // Back / forward.
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.edzcat) {
                state = Object.assign({categoryid: 0, q: '', sort: '', page: 1}, e.state.edzcat);
                if (search) {
                    search.value = state.q;
                }
                if (sort && state.sort) {
                    sort.value = state.sort;
                }
                navigate(false);
            }
        });
    };

    return {
        /**
         * Initialise the catalogue.
         *
         * @param {object} config {categoryid, q, sort, page}
         */
        init: function(config) {
            root = document.querySelector(SELECTORS.ROOT);
            if (!root) {
                return;
            }
            railEl = root.querySelector('[data-region="rail"]');
            railCurrentEl = root.querySelector('[data-region="rail-current"]');
            config = config || {};
            state.categoryid = parseInt(config.categoryid, 10) || 0;
            state.q = config.q || '';
            state.sort = config.sort || '';
            state.page = parseInt(config.page, 10) || 1;

            // Seed history so the first Back returns to the initial view.
            window.history.replaceState({edzcat: Object.assign({}, state)}, '', buildUrl());

            bindEvents();
            updateRailLabel();
        }
    };
});
