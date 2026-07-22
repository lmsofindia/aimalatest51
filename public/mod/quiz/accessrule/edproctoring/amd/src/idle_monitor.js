// This file is part of Moodle - http://moodle.org/
//
// quizaccess_edproctoring — Idle activity monitor.
// Fires a callback when the student has no mouse/keyboard/touch activity
// for N seconds. Re-arms only after activity resumes, so a student left
// idle gets ONE warning per idle period, not one every N seconds.
//
// @package   quizaccess_edproctoring
// @copyright 2025 EDZLMS
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define([], function() {

    'use strict';

    const EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'];

    let timer       = null;
    let thresholdMs = 30000;
    let onIdle      = null;
    let idleFired   = false;
    let running     = false;

    function fire() {
        timer = null;
        idleFired = true;
        if (onIdle) {
            onIdle();
        }
    }

    function arm() {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(fire, thresholdMs);
    }

    function onActivity() {
        if (!running) {
            return;
        }
        idleFired = false;
        arm();
    }

    /**
     * Start idle monitoring.
     *
     * @param {Object} opts
     * @param {number} opts.idleSeconds seconds of inactivity before onIdle fires
     * @param {Function} opts.onIdle
     */
    function init(opts) {
        thresholdMs = (opts.idleSeconds || 30) * 1000;
        onIdle      = opts.onIdle;
        running     = true;

        EVENTS.forEach(function(ev) {
            document.addEventListener(ev, onActivity, {passive: true});
        });
        arm();
    }

    /**
     * Stop idle monitoring.
     */
    function stop() {
        running = false;
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        EVENTS.forEach(function(ev) {
            document.removeEventListener(ev, onActivity);
        });
    }

    return {init, stop};
});
