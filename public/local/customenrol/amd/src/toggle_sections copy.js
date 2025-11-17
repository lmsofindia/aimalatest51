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
            // Root container: scope everything
            const root = rootSelector
                ? document.querySelector(rootSelector)
                : document;

            if (!root) return;

            /** =======================
             * Tabs Logic
             * ======================= */
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

            /** =======================
             * Section Toggle Logic
             * ======================= */
            const sections = root.querySelectorAll('.lce-section-head');
            const toggleAllBtn = root.querySelector('#toggle-all');

            function updateToggleAllText(forceOpen) {
                const allOpen = typeof forceOpen !== 'undefined'
                    ? forceOpen
                    : [...root.querySelectorAll('.lce-itemss')]
                        .every(el => el.style.display === 'block');
                if (toggleAllBtn) toggleAllBtn.textContent = allOpen ? 'Collapse All' : 'Expand All';
            }

            // Individual section toggle
            sections.forEach(head => {
                head.addEventListener('click', () => {
                    const section = head.closest('.border') || head.parentElement;
                    const items = section.querySelector('.lce-itemss');
                    const icon = head.querySelector('i');

                    const isOpen = items.style.display === 'block';
                    items.style.display = isOpen ? 'none' : 'block';

                    if (icon) {
                        icon.classList.toggle('bi-chevron-down', isOpen);
                        icon.classList.toggle('bi-chevron-up', !isOpen);
                    }

                    updateToggleAllText();
                });
            });

            // Toggle all sections
            if (toggleAllBtn) {
                toggleAllBtn.addEventListener('click', () => {
                    const allItems = [...root.querySelectorAll('.lce-itemss')];
                    const allOpen = allItems.every(el => el.style.display === 'block');

                    allItems.forEach(el => el.style.display = allOpen ? 'none' : 'block');

                    // Update all icons
                    root.querySelectorAll('.lce-section-head i').forEach(icon => {
                        icon.classList.toggle('bi-chevron-down', allOpen);
                        icon.classList.toggle('bi-chevron-up', !allOpen);
                    });

                    updateToggleAllText(!allOpen);
                });
            }

            // Set initial button text
            updateToggleAllText();
        }
    };
});

