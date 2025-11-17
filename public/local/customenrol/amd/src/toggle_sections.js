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
 * TODO describe module toggle_sections
 *
 * @module     local_customenrol/toggle_sections
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        init: function(rootSelector) {
            const root = rootSelector ? document.querySelector(rootSelector) : document;
            if (!root) return;

            /** Tabs Logic (unchanged) */
            const tabs = root.querySelectorAll('.lce-tab');
            const panels = root.querySelectorAll('.lce-tabpanel');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const id = tab.getAttribute('data-tab');
                    tabs.forEach(t => t.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    panels.forEach(p => {
                        p.classList.toggle('is-active', p.getAttribute('data-panel') === id);
                    });
                });
            });

            /** Section Toggle Logic */
            const sections = root.querySelectorAll('.lce-section-head');
            sections.forEach(head => {
                head.addEventListener('click', () => {
                    const section = head.closest('.border') || head.parentElement;
                    const items = section.querySelector('.lce-items');
                    const icon = head.querySelector('i.fa-chevron-down, i.fa-chevron-up');

                    items.classList.toggle('is-open');
                    if (icon) {
                        icon.classList.toggle('fa-chevron-down', !items.classList.contains('is-open'));
                        icon.classList.toggle('fa-chevron-up', items.classList.contains('is-open'));
                    }
                });
            });

            /** Expand All button */
            const expandBtn = root.querySelector('#expand-all');
            if (expandBtn) {
                expandBtn.addEventListener('click', () => {
                    root.querySelectorAll('.lce-items').forEach(el => el.classList.add('is-open'));
                    root.querySelectorAll('.lce-section-head i.fa-chevron-down, .lce-section-head i.fa-chevron-up')
                        .forEach(icon => {
                            icon.classList.remove('fa-chevron-down');
                            icon.classList.add('fa-chevron-up');
                        });
                });
            }

            /** Collapse All button */
            const collapseBtn = root.querySelector('#collapse-all');
            if (collapseBtn) {
                collapseBtn.addEventListener('click', () => {
                    root.querySelectorAll('.lce-items').forEach(el => el.classList.remove('is-open'));
                    root.querySelectorAll('.lce-section-head i.fa-chevron-down, .lce-section-head i.fa-chevron-up')
                        .forEach(icon => {
                            icon.classList.add('fa-chevron-down');
                            icon.classList.remove('fa-chevron-up');
                        });
                });
            }

            /** Default: Expand all on load */
            root.querySelectorAll('.lce-items').forEach(el => el.classList.add('is-open'));
            root.querySelectorAll('.lce-section-head i.fa-chevron-down, .lce-section-head i.fa-chevron-up')
                .forEach(icon => {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                });
        }
    };
});



