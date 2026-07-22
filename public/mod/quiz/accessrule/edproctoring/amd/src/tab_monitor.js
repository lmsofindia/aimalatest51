// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — Tab switch + fullscreen exit monitor.
// Uses Page Visibility API and Fullscreen API.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define([], function() {

    'use strict';

    let callbacks    = {};
    let reenterBtn   = null;
    let fullscreenRequested = false;

    /**
     * Handle page visibility change (tab switch, minimise, etc.)
     */
    function onVisibilityChange() {
        if (document.hidden && callbacks.onTabSwitch) {
            callbacks.onTabSwitch();
        }
    }

    /**
     * Handle fullscreen change event.
     */
    function onFullscreenChange() {
        const isFullscreen = !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement
        );

        if (!isFullscreen && fullscreenRequested && callbacks.onFullscreenExit) {
            callbacks.onFullscreenExit();
            // Show re-enter button in overlay.
            if (reenterBtn) {
                reenterBtn.style.display = 'block';
            }
        }

        if (isFullscreen && reenterBtn) {
            reenterBtn.style.display = 'none';
        }
    }

    /**
     * Request fullscreen on the document element.
     */
    function requestFullscreen() {
        const el = document.documentElement;
        fullscreenRequested = true;
        if (el.requestFullscreen) {
            el.requestFullscreen().catch(err => {
                console.warn('EDP tab-monitor: fullscreen request failed', err);
            });
        } else if (el.webkitRequestFullscreen) {
            el.webkitRequestFullscreen();
        } else if (el.mozRequestFullScreen) {
            el.mozRequestFullScreen();
        }
    }

    /**
     * Initialise tab and fullscreen monitoring.
     *
     * @param {Object} opts
     * @param {boolean}  opts.requireFullscreen
     * @param {Function} opts.onTabSwitch
     * @param {Function} opts.onFullscreenExit
     * @param {HTMLElement} [opts.overlayReenterButton]
     */
    function init(opts) {
        callbacks  = opts;
        reenterBtn = opts.overlayReenterButton || null;

        document.addEventListener('visibilitychange', onVisibilityChange);
        document.addEventListener('fullscreenchange',       onFullscreenChange);
        document.addEventListener('webkitfullscreenchange', onFullscreenChange);
        document.addEventListener('mozfullscreenchange',    onFullscreenChange);
    }

    /**
     * Remove event listeners.
     */
    function stop() {
        document.removeEventListener('visibilitychange', onVisibilityChange);
        document.removeEventListener('fullscreenchange',       onFullscreenChange);
        document.removeEventListener('webkitfullscreenchange', onFullscreenChange);
        document.removeEventListener('mozfullscreenchange',    onFullscreenChange);
    }

    return {init, stop, requestFullscreen};
});
