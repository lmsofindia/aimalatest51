<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Hook listener: inject the time-tracking heartbeat on course-module view pages.
 *
 * This is the key to theme independence — the original local_gemui_timetracker
 * relied on theme_gemui's page_init to fire the heartbeat. Here the plugin
 * injects its own AMD, so tracking works under ANY theme.
 *
 * @package    local_trackmytime
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trackmytime\hook\output;

/**
 * Listener for core\hook\output\before_http_headers.
 */
class before_http_headers {

    /**
     * Inject the heartbeat AMD on mod-*-view pages when tracking is enabled.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function callback(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;

        // Respect the admin toggle (default ON — only skip when explicitly '0').
        if (get_config('local_trackmytime', 'enabletracking') === '0') {
            return;
        }

        // Only track real logged-in users.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        // Only on course-module VIEW pages (mod-xxx-view).
        $pagetype = $PAGE->pagetype ?? '';
        if (strpos($pagetype, 'mod-') !== 0 || strpos($pagetype, '-view') === false) {
            return;
        }

        // Resolve the course-module id from the query string.
        $cmid = optional_param('id', 0, PARAM_INT);
        if ($cmid <= 0) {
            return;
        }

        // Queue the heartbeat; require.js outputs it in the footer as normal.
        $PAGE->requires->js_call_amd('local_trackmytime/timetracker', 'init', [$cmid]);
    }
}
