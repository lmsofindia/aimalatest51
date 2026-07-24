<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Teacher management page: occurrences, attendance grid, reconcile unmatched
 * participants, and re-poll attendance. Server-side (no AMD) for robustness.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_edzsession\local\attendance\attendance_engine;

$id = required_param('id', PARAM_INT);            // Course module id.
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('edzsession', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$edzsession = $DB->get_record('edzsession', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/edzsession:viewall', $context);

$baseurl = new moodle_url('/mod/edzsession/manage.php', ['id' => $cm->id]);
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title(format_string($edzsession->name));
$PAGE->set_heading(format_string($course->fullname));

// ---- Actions -------------------------------------------------------------
if ($action === 'assign' && confirm_sesskey()) {
    require_capability('mod/edzsession:reconcile', $context);
    $attendanceid = required_param('attendanceid', PARAM_INT);
    $userid = required_param('userid', PARAM_INT);
    // Guard: the target user must be enrolled here.
    if ($userid && is_enrolled($context, $userid)) {
        attendance_engine::assign_user($attendanceid, $userid);
        redirect($baseurl, get_string('reconcile_assigned', 'mod_edzsession'));
    }
    redirect($baseurl, get_string('reconcile_notenrolled', 'mod_edzsession'), null,
        \core\output\notification::NOTIFY_ERROR);
}

if ($action === 'repoll' && confirm_sesskey()) {
    require_capability('mod/edzsession:reconcile', $context);
    $occurrenceid = required_param('occurrenceid', PARAM_INT);
    $sql = "SELECT o.*, e.accountid, e.meetingprovider
              FROM {edzsession_occurrence} o
              JOIN {edzsession} e ON e.id = o.edzsessionid
             WHERE o.id = :oid";
    $occ = $DB->get_record_sql($sql, ['oid' => $occurrenceid], MUST_EXIST);
    try {
        attendance_engine::poll_occurrence($occ);
        redirect($baseurl, get_string('reconcile_repolled', 'mod_edzsession'));
    } catch (\Throwable $e) {
        redirect($baseurl, get_string('reconcile_repollfailed', 'mod_edzsession', $e->getMessage()),
            null, \core\output\notification::NOTIFY_ERROR);
    }
}

// ---- Enrolled-user menu for assigning unmatched participants -------------
$usermenu = [0 => get_string('choose')];
foreach (get_enrolled_users($context) as $u) {
    $usermenu[$u->id] = fullname($u) . ' (' . $u->email . ')';
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_title', 'mod_edzsession', format_string($edzsession->name)));

if ($edzsession->meetingstatus === 'error') {
    echo $OUTPUT->notification(get_string('meeting_error', 'mod_edzsession'), 'error');
} else if ($edzsession->meetingstatus === 'pending') {
    echo $OUTPUT->notification(get_string('meeting_pending', 'mod_edzsession'), 'info');
}

$occurrences = $DB->get_records('edzsession_occurrence', ['edzsessionid' => $edzsession->id], 'starttime ASC');
if (empty($occurrences)) {
    echo $OUTPUT->notification(get_string('nooccurrencesyet', 'mod_edzsession'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$canreconcile = has_capability('mod/edzsession:reconcile', $context);

foreach ($occurrences as $occ) {
    echo $OUTPUT->heading(userdate($occ->starttime), 4);

    // Re-poll button.
    if ($canreconcile) {
        echo html_writer::div($OUTPUT->single_button(
            new moodle_url($baseurl, ['action' => 'repoll', 'occurrenceid' => $occ->id, 'sesskey' => sesskey()]),
            get_string('reconcile_repoll', 'mod_edzsession'), 'get'), 'mb-2');
    }

    $attendance = $DB->get_records('edzsession_attendance', ['occurrenceid' => $occ->id], 'attendedpercent DESC');
    if (empty($attendance)) {
        echo html_writer::div(get_string('noattendanceyet', 'mod_edzsession'), 'text-muted mb-3');
        continue;
    }

    $table = new html_table();
    $table->head = [
        get_string('col_participant', 'mod_edzsession'),
        get_string('col_user', 'mod_edzsession'),
        get_string('col_minutes', 'mod_edzsession'),
        get_string('col_percent', 'mod_edzsession'),
        get_string('col_match', 'mod_edzsession'),
    ];
    foreach ($attendance as $a) {
        $participant = s($a->matchedname) . ($a->matchedemail ? ' <' . s($a->matchedemail) . '>' : '');
        if ($a->matchstate === 'unmatched' || !$a->userid) {
            if ($canreconcile) {
                // Inline assign form.
                $form = html_writer::start_tag('form',
                    ['method' => 'post', 'action' => $baseurl->out(false), 'class' => 'form-inline']);
                $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'assign']);
                $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
                $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'attendanceid', 'value' => $a->id]);
                $form .= html_writer::select($usermenu, 'userid', 0, false);
                $form .= ' ' . html_writer::empty_tag('input',
                    ['type' => 'submit', 'value' => get_string('reconcile_assign', 'mod_edzsession'), 'class' => 'btn btn-secondary btn-sm']);
                $form .= html_writer::end_tag('form');
                $usercell = $form;
            } else {
                $usercell = html_writer::span(get_string('unmatched', 'mod_edzsession'), 'badge bg-warning text-dark');
            }
        } else {
            $u = \core_user::get_user($a->userid);
            $usercell = $u ? fullname($u) : ('#' . $a->userid);
        }
        $table->data[] = [
            $participant,
            $usercell,
            floor($a->joinseconds / 60),
            format_float($a->attendedpercent, 1) . '%',
            html_writer::span(s($a->matchstate), 'badge bg-light text-dark'),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
