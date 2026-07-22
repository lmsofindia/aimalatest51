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
 * Report filter behaviour: auto-submit on dropdown change + remember the last filter
 * per report page (via localStorage), so re-opening a report restores what you last saw.
 *
 * @module     local_reportpanel/filters
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    var storeKey = function() {
        return 'rp-filters:' + window.location.pathname;
    };

    var save = function() {
        if (window.location.search) {
            try {
                window.localStorage.setItem(storeKey(), window.location.search.substring(1));
            } catch (e) {
                // Ignore storage failures (private mode, quota, etc.).
            }
        }
    };

    return {
        init: function() {
            var forms = document.querySelectorAll('.rp-eng-filters');
            if (!forms.length) {
                return;
            }

            // Restore the last filter when the page is opened with no query string.
            if (!window.location.search) {
                var saved = null;
                try {
                    saved = window.localStorage.getItem(storeKey());
                } catch (e) {
                    saved = null;
                }
                if (saved) {
                    window.location.replace(window.location.pathname + '?' + saved);
                    return;
                }
            }

            // Remember whatever we are showing now.
            save();

            // Auto-submit the form as soon as a dropdown changes.
            forms.forEach(function(form) {
                form.querySelectorAll('select').forEach(function(sel) {
                    sel.addEventListener('change', function() {
                        form.submit();
                    });
                });
            });
        }
    };
});
