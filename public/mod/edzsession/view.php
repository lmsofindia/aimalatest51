<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Student view of an EDZ Session activity.
 *
 * P1: renders the activity intro + an occurrences placeholder. The join button,
 * attendance summary and recording embeds are wired in P2–P4.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/edzsession/lib.php');

$id = optional_param('id', 0, PARAM_INT);      // Course module id.
$e  = optional_param('e', 0, PARAM_INT);       // edzsession instance id.

if ($id) {
    $cm = get_coursemodule_from_id('edzsession', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $edzsession = $DB->get_record('edzsession', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $edzsession = $DB->get_record('edzsession', ['id' => $e], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $edzsession->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('edzsession', $edzsession->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/edzsession:view', $context);

// Log the view + trigger completion-on-view.
$event = \mod_edzsession\event\course_module_viewed::create([
    'objectid' => $edzsession->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('edzsession', $edzsession);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/edzsession/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($edzsession->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($edzsession->name));

// Teacher entry point to the attendance / reconcile manager.
if (has_capability('mod/edzsession:viewall', $context)) {
    echo html_writer::div(
        $OUTPUT->single_button(
            new moodle_url('/mod/edzsession/manage.php', ['id' => $cm->id]),
            get_string('managelink', 'mod_edzsession'), 'get'),
        'mb-3');
}

if (!empty($edzsession->intro)) {
    echo $OUTPUT->box(format_module_intro('edzsession', $edzsession, $cm->id), 'generalbox', 'intro');
}

// Occurrence list.
$occurrences = $DB->get_records('edzsession_occurrence',
    ['edzsessionid' => $edzsession->id], 'starttime ASC');

if (empty($occurrences)) {
    echo $OUTPUT->notification(get_string('nooccurrencesyet', 'mod_edzsession'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('col_when', 'mod_edzsession'),
        get_string('col_status', 'mod_edzsession'),
        get_string('col_join', 'mod_edzsession'),
        get_string('col_recording', 'mod_edzsession'),
    ];
    $canhost = has_capability('mod/edzsession:host', $context);
    $hasmeeting = !empty($edzsession->remotemeetingid) && !empty($edzsession->accountid);

    foreach ($occurrences as $occ) {
        $occhasmeeting = $hasmeeting || !empty($occ->remotemeetingid);
        if ($canhost && $occhasmeeting) {
            // Host: start the meeting as host (fresh token minted server-side).
            $starturl = new moodle_url('/mod/edzsession/start.php',
                ['id' => $cm->id, 'occ' => $occ->id, 'sesskey' => sesskey()]);
            $join = html_writer::link($starturl, get_string('startashost', 'mod_edzsession'),
                ['class' => 'btn btn-success btn-sm', 'target' => '_blank', 'rel' => 'noopener']);
        } else if (!empty($occ->joinurl)) {
            $join = html_writer::link($occ->joinurl, get_string('join', 'mod_edzsession'),
                ['class' => 'btn btn-primary btn-sm', 'target' => '_blank', 'rel' => 'noopener']);
        } else {
            $join = '-';
        }
        $table->data[] = [
            userdate($occ->starttime),
            s($occ->status),
            $join,
            mod_edzsession_render_recording_cell($occ->id),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();

/**
 * Render the recording cell for an occurrence: an embedded player once the
 * offload is finalized, otherwise a status label. Provider-neutral — reads the
 * embed_info the storage provider produced.
 *
 * @param int $occurrenceid
 * @return string HTML
 */
function mod_edzsession_render_recording_cell(int $occurrenceid): string {
    global $DB;
    $recordings = $DB->get_records('edzsession_recording', ['occurrenceid' => $occurrenceid], 'timecreated ASC');
    if (empty($recordings)) {
        return get_string('recording_pending', 'mod_edzsession');
    }
    $out = '';
    foreach ($recordings as $rec) {
        if (!empty($rec->embedjson)) {
            $embed = json_decode($rec->embedjson, true) ?: [];
            $url = $embed['url'] ?? '';
            if ($url === '') {
                continue;
            }
            if (($embed['kind'] ?? 'iframe') === 'video') {
                $out .= html_writer::tag('video', '', [
                    'src' => $url, 'controls' => 'controls',
                    'width' => 400, 'controlslist' => 'nodownload',
                ]);
            } else {
                $out .= html_writer::tag('iframe', '', [
                    'src' => $url, 'width' => 400, 'height' => 225,
                    'frameborder' => 0, 'allowfullscreen' => 'allowfullscreen',
                    'allow' => 'fullscreen; picture-in-picture',
                ]);
            }
        } else {
            // Not finalized yet — show a friendly status.
            $label = ($rec->state === 'failed')
                ? get_string('recording_failed', 'mod_edzsession')
                : get_string('recording_processing', 'mod_edzsession');
            $out .= html_writer::span($label, 'badge bg-light text-dark');
        }
    }
    return $out !== '' ? $out : get_string('recording_pending', 'mod_edzsession');
}
