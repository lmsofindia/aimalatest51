<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * PSR-14 Hook callback class for output hooks.
 *
 * Used by Moodle 4.4+ / 5.x via the Hooks API (db/hooks.php).
 * For Moodle 4.1–4.3, the equivalent local_edzaiaxisfront_before_footer()
 * function in lib.php is used instead.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront\hook;

use moodle_url;
use navigation_node;

/**
 * Handles Moodle output hooks for local_edzaiaxisfront.
 */
class output_callbacks
{

    /**
     * Inject the floating chatbot panel HTML just before </body>.
     *
     * This is the PSR-14 Hooks API equivalent of local_edzaiaxisfront_before_footer()
     * in lib.php. It is called on Moodle 4.4+ via db/hooks.php. On Moodle 4.1–4.3
     * the lib.php function is used and this class is not loaded at all.
     *
     * Conditions before injecting:
     *   - Plugin is enabled in site admin
     *   - User is logged in and is not a guest
     *   - Page layout is not maintenance / embedded / print / redirect
     *   - Not an admin page (pagetype starts with "admin-")
     *   - At least one chatbot mode is enabled at the site level
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer(
        \core\hook\output\before_footer_html_generation $hook
    ): void {
        global $PAGE, $USER, $OUTPUT;

        if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
            return;
        }
        if (!isloggedin() || isguestuser()) {
            return;
        }
        if (in_array($PAGE->pagelayout, ['maintenance', 'embedded', 'print', 'redirect'])) {
            return;
        }
        if (strpos($PAGE->pagetype, 'admin-') === 0) {
            return;
        }

        // Course page: decorate activities that have learner-visible AI content with a
        // small "AI" badge linking straight into the activity's AI Learning Assistant.
        if (strpos($PAGE->pagetype, 'course-view-') === 0
                && !empty($PAGE->course->id) && (int) $PAGE->course->id !== (int) SITEID) {
            $PAGE->requires->js_call_amd(
                'local_edzaiaxisfront/course_badges',
                'init',
                [(int) $PAGE->course->id]
            );
        }

        $study_enabled    = (bool) get_config('local_edzaiaxisfront', 'feature_chatbot');
        $support_enabled  = (bool) get_config('local_edzaiaxisfront', 'feature_kb_chat');
        $analysis_enabled = true; // always on — all Moodle-DB data, no Python needed

        if (!$study_enabled && !$support_enabled && !$analysis_enabled) {
            return;
        }

        // Enqueue CSS using moodle_url (works in both old flat layout and Moodle 5.1
        // /public directory structure — Moodle resolves the correct absolute URL).
        //$PAGE->requires->css(new \moodle_url('/local/edzaiaxisfront/styles.css'));

        // Boot the chatbot AMD module.
        $PAGE->requires->js_call_amd(
            'local_edzaiaxisfront/chatbot',
            'init',
            [[
                'userid'           => (int) $USER->id,
                'firstname'        => $USER->firstname,
                'sesskey'          => sesskey(),
                'wwwroot'          => (new \moodle_url('/'))->out(false),
                'study_enabled'    => $study_enabled,
                'support_enabled'  => $support_enabled,
                'analysis_enabled' => $analysis_enabled,
            ]]
        );

        // Render and inject the chatbot HTML shell.
        $html = $OUTPUT->render_from_template('local_edzaiaxisfront/chatbot_panel', [
            'userid'    => (int) $USER->id,
            'firstname' => $USER->firstname,
            'sesskey'   => sesskey(),
        ]);


        // MIHIR THE STUDENT PANEL ALSO HERE 1 APRIL 2026
        // Only inject the student panel when this activity actually has AI content
        // marked 'ready'. Without this gate the "AI Learning Assistant" shell is
        // injected on EVERY module page (even fresh, unprocessed ones) and renders
        // an empty panel. Mirrors the readiness check in lib.php::after_config().
        global $DB;
        $cmid = $PAGE->cm ? $PAGE->cm->id : null;
        if ($cmid) {
            $cfg = $DB->get_record(
                'local_edzaiaxisfront_cm_config',
                ['cmid' => $cmid],
                'id, status'
            );
            $context = \context_module::instance($cmid);
            if ($cfg && $cfg->status === 'ready'
                    && has_capability('local/edzaiaxisfront:viewstudent', $context)) {
                $PAGE->requires->js_call_amd(
                    'local_edzaiaxisfront/student_panel',
                    'init',
                    [[
                        'cmid'       => $cmid,
                        'courseid'   => $PAGE->course->id,
                        'userid'     => $USER->id,
                        'sesskey'    => sesskey(),
                        'wwwroot'    => (new \moodle_url('/'))->out(false),
                    ]]
                );
            }
        }
        // END OF STUDENT PANEL INJECTION

        // PSR-14 hooks API: add HTML to the footer rather than returning it.
        $hook->add_html($html);
    }
}
