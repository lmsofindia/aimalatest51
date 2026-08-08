// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp general theme AMD module.
 *
 * Responsibilities:
 *  - Dark / light mode toggle: adds [data-edz-theme="dark"] to <html>.
 *  - Persists theme preference in localStorage.
 *  - Respects prefers-color-scheme on first visit.
 *
 * NOTE: The dark theme is NOT production-ready (many text colours are not yet
 * overridden for dark panels, which left dark text on dark backgrounds). Until
 * it is finished the site is intentionally LIGHT-ONLY: loadTheme() always
 * returns light and the OS prefers-color-scheme watcher never applies dark.
 * To re-enable dark later, restore the original loadTheme()/watcher logic
 * (kept below in comments) and un-hide the .edz-darkmode-toggle button.
 *
 * @module     theme_edzcorp/theme
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/log'], function(Log) {
    'use strict';

    const STORAGE_KEY  = 'edzcorp_theme';
    const DARK_VALUE   = 'dark';
    const LIGHT_VALUE  = 'light';
    const DATA_ATTR    = 'data-edz-theme';

    // -------------------------------------------------------------------------
    // Dark mode
    // -------------------------------------------------------------------------

    /**
     * Applies the given theme to the <html> element and updates the toggle button.
     *
     * @param {string} theme  'dark' or 'light'
     */
    const applyTheme = (theme) => {
        const html   = document.documentElement;
        const toggle = document.getElementById('edz-darkmode-toggle');

        if (theme === DARK_VALUE) {
            html.setAttribute(DATA_ATTR, DARK_VALUE);
        } else {
            html.removeAttribute(DATA_ATTR);
        }

        if (toggle) {
            toggle.setAttribute(
                'aria-pressed',
                String(theme === DARK_VALUE)
            );
        }
    };

    /**
     * Saves the theme preference to localStorage.
     *
     * @param {string} theme
     */
    const saveTheme = (theme) => {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {
            Log.debug('EdzCorp theme: localStorage unavailable', e);
        }
    };

    /**
     * Resolve which theme to apply on load.
     *
     * Dark mode is not ready, so this is forced to light regardless of any
     * saved preference or the OS prefers-color-scheme setting. This is what
     * stops a dark-mode browser from flipping on the half-built dark styling
     * (dark text on dark panels).
     *
     * Original logic (restore to re-enable dark):
     *   if (stored === DARK_VALUE || stored === LIGHT_VALUE) { return stored; }
     *   if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
     *       return DARK_VALUE;
     *   }
     *   return LIGHT_VALUE;
     *
     * @returns {string} always 'light'
     */
    const loadTheme = () => {
        return LIGHT_VALUE;
    };

    /**
     * Toggles between dark and light mode.
     */
    const toggleDarkMode = () => {
        const html       = document.documentElement;
        const isDark     = html.getAttribute(DATA_ATTR) === DARK_VALUE;
        const nextTheme  = isDark ? LIGHT_VALUE : DARK_VALUE;

        applyTheme(nextTheme);
        saveTheme(nextTheme);
    };

    // -------------------------------------------------------------------------
    // Smooth anchor scroll
    // -------------------------------------------------------------------------

    /**
     * Adds smooth scrolling to hash-link clicks within the main content.
     * Only activates for links that point to an anchor on the same page.
     */
    const initSmoothScroll = () => {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const target = document.querySelector(anchor.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    };

    // -------------------------------------------------------------------------
    // Card hover animation helper
    // -------------------------------------------------------------------------

    /**
     * Adds a subtle tilt effect to course/dashboard cards on mouse move.
     * The effect is mild (max ±4 degrees) and respects reduced-motion.
     */
    const initCardHover = () => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const cards = document.querySelectorAll('.dashboard-card, .course-info-container');
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect   = card.getBoundingClientRect();
                const x      = e.clientX - rect.left - rect.width / 2;
                const y      = e.clientY - rect.top  - rect.height / 2;
                const rotX   = (-y / rect.height) * 4;
                const rotY   = ( x / rect.width)  * 4;
                card.style.transform = `perspective(600px) rotateX(${rotX}deg) rotateY(${rotY}deg) translateY(-1px)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    };

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------

    /**
     * Entry point called from each layout PHP via $PAGE->requires->js_call_amd().
     */
    const init = () => {
        // Apply resolved theme immediately (currently always light).
        applyTheme(loadTheme());

        // Wire up dark mode toggle button (hidden while dark mode is disabled).
        const toggle = document.getElementById('edz-darkmode-toggle');
        if (toggle) {
            toggle.addEventListener('click', toggleDarkMode);
        }

        // Dark mode is disabled, so we intentionally do NOT watch
        // prefers-color-scheme changes — the site stays light regardless of
        // the OS/browser theme. (Re-add the matchMedia 'change' listener here
        // when the dark theme is completed.)

        // Enhancement features.
        initSmoothScroll();
        initCardHover();
    };

    return { init };
});
