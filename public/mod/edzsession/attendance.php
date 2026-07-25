<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Per-occurrence attendance detail: title block (session / host / timing),
 * summary stats, a paginated participant table with inline reconcile, and
 * CSV / PDF export links. Handles large sessions (100+ participants).
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_edzsession\local\attendance\attendance_engine;
use mod_edzsession\local\attendance\report;

$id = required_param('id', PARAM_INT);         // Course module id.
$occid = required_param('occ', PARAM_INT);      // Occurrence id.
$page = optional_param('page', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

const EDZSESSION_PERPAGE = 50;

$cm = get_coursemodule_from_id('edzsession', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$edzsession = $DB->get_record('edzsession', ['id' => $cm->instance], '*', MUST_EXIST);
$occ = $DB->get_record('edzsession_occurrence',
    ['id' => $occid, 'edzsessionid' => $edzsession->id], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/edzsession:viewall', $context);

$pageurl = new moodle_url('/mod/edzsession/attendance.php', ['id' => $cm->id, 'occ' => $occid]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(format_string($edzsession->name));
$PAGE->set_heading(format_string($course->fullname));

// ---- Reconcile: assign an unmatched participant to a user ----------------
if ($action === 'assign' && confirm_sesskey()) {
    require_capability('mod/edzsession:reconcile', $context);
    $attendanceid = required_param('attendanceid', PARAM_INT);
    $userid = required_param('userid', PARAM_INT);
    if ($userid && is_enrolled($context, $userid)) {
        attendance_engine::assign_user($attendanceid, $userid);
        redirect(new moodle_url($pageurl, ['page' => $page]),
            get_string('reconcile_assigned', 'mod_edzsession'));
    }
    redirect(new moodle_url($pageurl, ['page' => $page]),
        get_string('reconcile_notenrolled', 'mod_edzsession'), null,
        \core\output\notification::NOTIFY_ERROR);
}

$meta = report::session_meta($cm, $course, $edzsession);
$summary = report::occurrence_summary($occid);
$total = report::count($occid);
$canreconcile = has_capability('mod/edzsession:reconcile', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading($meta->sessionname);

// ---- Title block ---------------------------------------------------------
$mettable = new html_table();
$mettable->attributes['class'] = 'generaltable w-auto';
$mettable->data = [
    [get_string('col_host', 'mod_edzsession'), s($meta->host)],
    [get_string('col_when', 'mod_edzsession'), userdate($occ->starttime)],
    [get_string('col_duration', 'mod_edzsession'), get_string('nminutes', 'mod_edzsession', (int) $occ->duration)],
    [get_string('col_present', 'mod_edzsession'), $summary->present . ' / ' . $summary->total],
    [get_string('col_avg', 'mod_edzsession'),
        $summary->total > 0 ? format_float($summary->avgpercent, 1) . '%' : '-'],
];
echo html_writer::table($mettable);

// ---- Toolbar: back + exports --------------------------------------------
$toolbar = html_writer::link(new moodle_url('/mod/edzsession/manage.php', ['id' => $cm->id]),
    get_string('backtooverview', 'mod_edzsession'), ['class' => 'btn btn-secondary btn-sm']);
if ($total > 0) {
    $toolbar .= ' ' . html_writer::link(
        new moodle_url('/mod/edzsession/export.php', ['id' => $cm->id, 'occ' => $occid, 'format' => 'csv']),
        get_string('export_csv', 'mod_edzsession'), ['class' => 'btn btn-outline-primary btn-sm']);
    $toolbar .= ' ' . html_writer::link(
        new moodle_url('/mod/edzsession/export.php', ['id' => $cm->id, 'occ' => $occid, 'format' => 'pdf']),
        get_string('export_pdf', 'mod_edzsession'), ['class' => 'btn btn-outline-primary btn-sm']);
}
if ($canreconcile) {
    $toolbar .= ' ' . html_writer::link(
        new moodle_url('/mod/edzsession/manage.php',
            ['id' => $cm->id, 'action' => 'repoll', 'occurrenceid' => $occid, 'sesskey' => sesskey()]),
        get_string('reconcile_repoll', 'mod_edzsession'), ['class' => 'btn btn-outline-secondary btn-sm']);
}
echo html_writer::div($toolbar, 'mb-3');

if ($total == 0) {
    echo $OUTPUT->notification(get_string('noattendanceyet', 'mod_edzsession'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// ---- Paginated participant table ----------------------------------------
echo $OUTPUT->paging_bar($total, $page, EDZSESSION_PERPAGE, $pageurl);

$rows = report::rows($occid, $page * EDZSESSION_PERPAGE, EDZSESSION_PERPAGE);

// Enrolled-user menu only needed when there are unmatched rows on this page.
$usermenu = null;
$hasunmatched = false;
foreach ($rows as $r) {
    if ($canreconcile && (!$r->userid || $r->matchstate === 'unmatched')) {
        $hasunmatched = true;
        break;
    }
}
if ($hasunmatched) {
    $usermenu = [0 => get_string('choose')];
    foreach (get_enrolled_users($context, '', 0, 'u.*', 'lastname ASC') as $u) {
        $usermenu[$u->id] = fullname($u) . ' (' . $u->email . ')';
    }
}

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = [
    get_string('col_num', 'mod_edzsession'),
    get_string('col_participant', 'mod_edzsession'),
    get_string('col_email', 'mod_edzsession'),
    get_string('col_user', 'mod_edzsession'),
    get_string('col_minutes', 'mod_edzsession'),
    get_string('col_percent', 'mod_edzsession'),
    get_string('col_match', 'mod_edzsession'),
];
$rownum = $page * EDZSESSION_PERPAGE;
foreach ($rows as $r) {
    $rownum++;
    if ($r->userid && $r->username !== '') {
        $usercell = s($r->username);
    } else if ($canreconcile && $usermenu !== null) {
        $form = html_writer::start_tag('form',
            ['method' => 'post', 'action' => $pageurl->out(false), 'class' => 'd-flex gap-1']);
        $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'assign']);
        $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'page', 'value' => $page]);
        $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attendanceid', 'value' => $r->id]);
        $form .= html_writer::select($usermenu, 'userid', 0, false, ['class' => 'form-select form-select-sm']);
        $form .= ' ' . html_writer::empty_tag('input',
            ['type' => 'submit', 'value' => get_string('reconcile_assign', 'mod_edzsession'),
                'class' => 'btn btn-secondary btn-sm']);
        $form .= html_writer::end_tag('form');
        $usercell = $form;
    } else {
        $usercell = html_writer::span(get_string('unmatched', 'mod_edzsession'), 'badge bg-warning text-dark');
    }

    $badgeclass = $r->matchstate === 'unmatched' ? 'bg-warning text-dark'
        : ($r->matchstate === 'manual' ? 'bg-info' : 'bg-light text-dark');
    $table->data[] = [
        $rownum,
        s($r->participant),
        s($r->email),
        $usercell,
        $r->minutes,
        format_float($r->percent, 1) . '%',
        html_writer::span(s($r->matchstate), 'badge ' . $badgeclass),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->paging_bar($total, $page, EDZSESSION_PERPAGE, $pageurl);
echo $OUTPUT->footer();
