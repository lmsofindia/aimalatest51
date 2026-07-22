<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Live proctoring monitor — real-time wall of active exam takers (Option A).
 *
 * Two scopes:
 *   - Per-quiz   : ?cmid=X  (capability quizaccess/edproctoring:viewreport on module)
 *   - Site-wide  : no cmid  (capability quizaccess/edproctoring:viewallreports on system)
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../config.php');

use quizaccess_edproctoring\helper\live_monitor;

$cmid = optional_param('cmid', 0, PARAM_INT);

if ($cmid > 0) {
    $cm      = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
    $course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $quiz    = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

    require_login($course, false, $cm);
    $context = context_module::instance($cmid);
    require_capability('quizaccess/edproctoring:viewreport', $context);

    $PAGE->set_cm($cm, $course);
    $PAGE->set_context($context);
    $heading = get_string('livemonitor_for', 'quizaccess_edproctoring', format_string($quiz->name));
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($quiz->name, new moodle_url('/mod/quiz/view.php', ['id' => $cmid]));
    $PAGE->navbar->add(get_string('livemonitor', 'quizaccess_edproctoring'));
    $pageurl = new moodle_url('/mod/quiz/accessrule/edproctoring/report/live.php', ['cmid' => $cmid]);
} else {
    require_login();
    $context = context_system::instance();
    require_capability('quizaccess/edproctoring:viewallreports', $context);

    $PAGE->set_context($context);
    $PAGE->set_pagelayout('admin');
    $heading = get_string('livemonitor_all', 'quizaccess_edproctoring');
    $PAGE->set_heading($heading);
    $pageurl = new moodle_url('/mod/quiz/accessrule/edproctoring/report/live.php');
}

$PAGE->set_url($pageurl);
$PAGE->set_title($heading);

$refresh = live_monitor::get_refresh_interval();

$PAGE->requires->js_call_amd('quizaccess_edproctoring/live_monitor', 'init', [[
    'cmid'    => (int) $cmid,
    'refresh' => (int) $refresh,
]]);

/** @var \quizaccess_edproctoring\output\renderer $renderer */
$renderer = $PAGE->get_renderer('quizaccess_edproctoring');

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo $renderer->render_live_monitor((int) $cmid, (int) $refresh);
echo $OUTPUT->footer();
