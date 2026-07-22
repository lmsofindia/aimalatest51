// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp sidebar AMD module.
 *
 * Responsibilities:
 *  1. Desktop collapse: toggle .sidenav-collapsed on <body>, persist to localStorage.
 *  2. Mobile slide-in/out: toggle .mobile-open on .edzcorp-sidenav + .active on overlay.
 *  3. Bootstrap 5 tooltips on nav links (active only when sidebar is collapsed).
 *  4. User footer dropdown: toggle .open on #edz-user-dropdown, close on outside click.
 *
 * @module     theme_edzcorp/sidebar
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/log'], function(Log) {
    'use strict';

    const STORAGE_KEY = 'edzcorp_sidenav_collapsed';
    const MOBILE_BP   = 992;

    // ---- DOM references (resolved in init) ----
    let sidenav        = null;
    let desktopToggle  = null;
    let mobileToggle   = null;
    let overlay        = null;
    let userBtn        = null;
    let userDropdown   = null;

    // ---- Tooltip instances cache ----
    let tooltipInstances = [];

    const isMobile    = () => window.innerWidth < MOBILE_BP;
    const isCollapsed = () => document.body.classList.contains('sidenav-collapsed');

    // =========================================================================
    // Tooltip management
    // =========================================================================

    const initTooltips = () => {
        if (typeof window.bootstrap === 'undefined' || !window.bootstrap.Tooltip) {
            return;
        }
        tooltipInstances.forEach(t => { try { t.dispose(); } catch (e) { /* noop */ } });
        tooltipInstances = [];

        document.querySelectorAll('.edzcorp-sidenav .sidenav-link[data-bs-toggle="tooltip"]')
            .forEach(el => {
                const instance = new window.bootstrap.Tooltip(el, {
                    trigger: 'hover',
                    boundary: document.body,
                });
                if (!isCollapsed() || isMobile()) {
                    instance.disable();
                }
                tooltipInstances.push(instance);
            });
    };

    const setTooltips = (enable) => {
        tooltipInstances.forEach(t => {
            try { enable ? t.enable() : t.disable(); } catch (e) {
                Log.debug('EdzCorp sidebar: tooltip toggle error', e);
            }
        });
    };

    // =========================================================================
    // Desktop collapse toggle
    // =========================================================================

    const toggleDesktop = () => {
        const willCollapse = !isCollapsed();
        document.body.classList.toggle('sidenav-collapsed', willCollapse);

        if (desktopToggle) {
            desktopToggle.setAttribute('aria-expanded', String(!willCollapse));
        }

        setTooltips(willCollapse);

        try {
            localStorage.setItem(STORAGE_KEY, willCollapse ? '1' : '0');
        } catch (e) {
            Log.debug('EdzCorp sidebar: localStorage unavailable', e);
        }

        // Close user dropdown when collapsing (icons-only mode has no room).
        if (willCollapse) {
            closeUserDropdown();
        }
    };

    const restoreDesktopState = () => {
        if (isMobile()) {
            return;
        }
        let stored = null;
        try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) { /* noop */ }

        if (stored === '1') {
            document.body.classList.add('sidenav-collapsed');
            if (desktopToggle) {
                desktopToggle.setAttribute('aria-expanded', 'false');
            }
            setTooltips(true);
        }
    };

    // =========================================================================
    // Mobile slide-in/out
    // =========================================================================

    const openMobile = () => {
        if (!sidenav) { return; }
        sidenav.classList.add('mobile-open');
        if (overlay) { overlay.classList.add('active'); }
        if (mobileToggle) { mobileToggle.setAttribute('aria-expanded', 'true'); }
        const firstLink = sidenav.querySelector('.sidenav-link');
        if (firstLink) { firstLink.focus(); }
    };

    const closeMobile = () => {
        if (!sidenav) { return; }
        sidenav.classList.remove('mobile-open');
        if (overlay) { overlay.classList.remove('active'); }
        if (mobileToggle) {
            mobileToggle.setAttribute('aria-expanded', 'false');
            mobileToggle.focus();
        }
        closeUserDropdown();
    };

    const toggleMobile = () => {
        if (!sidenav) { return; }
        sidenav.classList.contains('mobile-open') ? closeMobile() : openMobile();
    };

    // =========================================================================
    // User footer dropdown
    // =========================================================================

    const openUserDropdown = () => {
        if (!userDropdown || !userBtn) { return; }
        userDropdown.classList.add('open');
        userDropdown.setAttribute('aria-hidden', 'false');
        userBtn.setAttribute('aria-expanded', 'true');
    };

    const closeUserDropdown = () => {
        if (!userDropdown || !userBtn) { return; }
        userDropdown.classList.remove('open');
        userDropdown.setAttribute('aria-hidden', 'true');
        userBtn.setAttribute('aria-expanded', 'false');
    };

    const toggleUserDropdown = () => {
        if (!userDropdown) { return; }
        userDropdown.classList.contains('open') ? closeUserDropdown() : openUserDropdown();
    };

    // =========================================================================
    // Keyboard + resize handlers
    // =========================================================================

    const onKeydown = (e) => {
        if (e.key === 'Escape') {
            if (isMobile() && sidenav && sidenav.classList.contains('mobile-open')) {
                closeMobile();
            } else {
                closeUserDropdown();
            }
        }
    };

    const onResize = () => {
        if (!isMobile()) {
            if (sidenav) { sidenav.classList.remove('mobile-open'); }
            if (overlay) { overlay.classList.remove('active'); }
            setTooltips(isCollapsed());
        } else {
            setTooltips(false);
            // Remove desktop-only collapsed class so mobile always shows labels.
            document.body.classList.remove('sidenav-collapsed');
            closeUserDropdown();
        }
    };

    // =========================================================================
    // Init
    // =========================================================================

    const init = () => {
        sidenav       = document.getElementById('edzcorp-sidenav');
        desktopToggle = document.getElementById('sidenav-toggle-btn');
        mobileToggle  = document.getElementById('edz-mobile-nav-btn');
        overlay       = document.getElementById('edz-sidenav-overlay');
        userBtn       = document.getElementById('edz-user-menu-btn');
        userDropdown  = document.getElementById('edz-user-dropdown');

        if (!sidenav) {
            Log.debug('EdzCorp sidebar: #edzcorp-sidenav not found.');
            return;
        }

        // Restore persisted desktop collapsed state.
        restoreDesktopState();

        // Desktop toggle (inside sidebar header).
        if (desktopToggle) {
            desktopToggle.addEventListener('click', () => {
                isMobile() ? toggleMobile() : toggleDesktop();
            });
        }

        // Mobile hamburger button (outside sidebar, fixed position).
        if (mobileToggle) {
            mobileToggle.addEventListener('click', toggleMobile);
        }

        // Overlay click closes mobile sidebar.
        if (overlay) {
            overlay.addEventListener('click', closeMobile);
        }

        // User dropdown toggle — works in both expanded and collapsed mode.
        // In collapsed mode the dropdown is wider (set via CSS) so labels remain legible.
        if (userBtn) {
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleUserDropdown();
            });
        }

        // Close user dropdown when clicking anywhere outside it.
        document.addEventListener('click', (e) => {
            if (
                userBtn && userDropdown &&
                !userBtn.contains(e.target) &&
                !userDropdown.contains(e.target)
            ) {
                closeUserDropdown();
            }
        });

        // Keyboard.
        document.addEventListener('keydown', onKeydown);

        // Resize (debounced).
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(onResize, 150);
        });

        // Bootstrap 5 tooltips (delay so Bootstrap is loaded).
        setTimeout(initTooltips, 300);

        // Remove the no-transition guard that was applied by the inline script
        // in the page template.  We use two rAF frames so the browser has had
        // a chance to paint the already-correct collapsed/expanded state before
        // transitions are re-enabled.  This prevents the "collapse animation"
        // that would otherwise fire on every page load for users who keep the
        // sidebar collapsed.
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                document.body.classList.remove('edz-no-trans');
            });
        });
    };

    return { init };
});
