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
     * Loads the user's persisted theme, or falls back to system preference.
     *
     * @returns {string} 'dark' | 'light'
     */
    const loadTheme = () => {
        let stored = null;
        try {
            stored = localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            stored = null;
        }

        if (stored === DARK_VALUE || stored === LIGHT_VALUE) {
            return stored;
        }

        // No preference saved — honour OS setting.
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return DARK_VALUE;
        }

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
        // Apply saved / system theme immediately.
        applyTheme(loadTheme());

        // Wire up dark mode toggle button.
        const toggle = document.getElementById('edz-darkmode-toggle');
        if (toggle) {
            toggle.addEventListener('click', toggleDarkMode);
        }

        // Watch for OS theme changes while the page is open.
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                // Only follow OS changes if the user hasn't set an explicit preference.
                let stored = null;
                try { stored = localStorage.getItem(STORAGE_KEY); } catch (_) { /* noop */ }
                if (!stored) {
                    applyTheme(e.matches ? DARK_VALUE : LIGHT_VALUE);
                }
            });
        }

        // Enhancement features.
        initSmoothScroll();
        initCardHover();
    };

    return { init };
});
