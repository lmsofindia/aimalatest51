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
 * TODO describe module lce_tabs
 *
 * @module     local_customenrol/lce_tabs
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// File: amd/src/lce_tabs.js
// amd/src/lce_tabs.js
define([], function() {
    return {
        init: function(rootSelector) {
            const root = rootSelector
                ? document.querySelector(rootSelector)
                : document.body; // fallback, since currentScript won't work in AMD

            if (!root) return;

            // Tabs
            const tabs = root.querySelectorAll('.lce-tab');
            const panels = root.querySelectorAll('.lce-tabpanel');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const id = tab.getAttribute('data-tab');
                    tabs.forEach(t => t.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    panels.forEach(p => 
                        p.classList.toggle('is-active', p.getAttribute('data-panel') === id)
                    );
                });
            });

            // Section toggle
            root.querySelectorAll('.lce-section-head').forEach(head => {
                head.addEventListener('click', () => {
                    const items = head.nextElementSibling;
                    const arrow = head.querySelector('.lce-toggle-arrow');
                    const isHidden = items.style.display === 'none';
                    items.style.display = isHidden ? 'block' : 'none';
                    arrow.classList.toggle('fa-chevron-right', !isHidden);
                    arrow.classList.toggle('fa-chevron-down', isHidden);
                });
            });

            // Expand/Collapse all
            const expandBtn = root.querySelector('#expand-all');
            const collapseBtn = root.querySelector('#collapse-all');
            const sections = root.querySelectorAll('.lce-items');
            const arrows = root.querySelectorAll('.lce-toggle-arrow');

            expandBtn?.addEventListener('click', () => {
                sections.forEach(s => s.style.display = 'block');
                arrows.forEach(a => {
                    a.classList.remove('fa-chevron-right');
                    a.classList.add('fa-chevron-down');
                });
            });

            collapseBtn?.addEventListener('click', () => {
                sections.forEach(s => s.style.display = 'none');
                arrows.forEach(a => {
                    a.classList.remove('fa-chevron-down');
                    a.classList.add('fa-chevron-right');
                });
            });
        }
    };
});

