// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — Copy/paste detection monitor.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define([], function() {

    'use strict';

    let callbacks    = {};
    let lastFired    = 0;
    const THROTTLE_MS = 10000; // Don't fire more than once per 10s for the same type.

    function fireIfNotThrottled(action) {
        const now = Date.now();
        if (now - lastFired > THROTTLE_MS) {
            lastFired = now;
            if (callbacks.onCopyPaste) {
                callbacks.onCopyPaste(action);
            }
        }
    }

    function onKeydown(e) {
        const ctrl = e.ctrlKey || e.metaKey;
        if (!ctrl) {
            return;
        }
        // C=67, V=86, X=88, A=65 (select all)
        if ([67, 86, 88, 65].includes(e.keyCode)) {
            const action = {67:'copy', 86:'paste', 88:'cut', 65:'select_all'}[e.keyCode];
            fireIfNotThrottled(action);
        }
    }

    function onContextMenu(e) {
        // Only fire if right-click is on quiz body (not in teacher/admin areas).
        const quizBody = document.querySelector('#responseform') || document.querySelector('.que');
        if (quizBody && quizBody.contains(e.target)) {
            fireIfNotThrottled('right_click');
        }
    }

    /**
     * Initialise copy-paste monitoring.
     *
     * @param {Object} opts
     * @param {Function} opts.onCopyPaste  called with action string ('copy'|'paste'|'cut'|'right_click')
     */
    function init(opts) {
        callbacks = opts;
        document.addEventListener('keydown',     onKeydown);
        document.addEventListener('contextmenu', onContextMenu);
    }

    function stop() {
        document.removeEventListener('keydown',     onKeydown);
        document.removeEventListener('contextmenu', onContextMenu);
    }

    return {init, stop};
});
