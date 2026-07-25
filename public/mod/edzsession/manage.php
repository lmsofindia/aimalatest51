<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Teacher overview: session header + one card per occurrence with attendance
 * summary and recording status. Detailed, paginated attendance lives in
 * attendance.php; CSV/PDF export in export.php.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_edzsession\local\attendance\attendance_engine;
use mod_edzsession\local\attendance\report;
use mod_edzsession\local\provider_manager;
use mod_edzsession\local\account_vault;
use mod_edzsession\local\meeting\remote_meeting;
use mod_edzsession\local\pipeline\pipeline_manager;
use mod_edzsession\local\pipeline\states;

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

// ---- Re-poll attendance --------------------------------------------------
if ($action === 'repoll' && confirm_sesskey()) {
    require_capability('mod/edzsession:reconcile', $context);
    $occurrenceid = required_param('occurrenceid', PARAM_INT);
    $sql = "SELECT o.*, e.accountid, e.meetingprovider, e.remotemeetingid AS parentmeetingid
              FROM {edzsession_occurrence} o
              JOIN {edzsession} e ON e.id = o.edzsessionid
             WHERE o.id = :oid";
    $occ = $DB->get_record_sql($sql, ['oid' => $occurrenceid], MUST_EXIST);
    try {
        attendance_engine::poll_occurrence($occ);
        redirect($baseurl, get_string('reconcile_repolled', 'mod_edzsession'));
    } catch (\Throwable $e) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(
            get_string('reconcile_repollfailed', 'mod_edzsession', $e->getMessage()), 'error');
        if ($e instanceof \moodle_exception && !empty($e->debuginfo)) {
            echo html_writer::tag('pre', s($e->debuginfo),
                ['class' => 'bg-light p-3 border rounded', 'style' => 'white-space:pre-wrap;']);
        }
        echo html_writer::div(html_writer::link($baseurl, get_string('back'),
            ['class' => 'btn btn-secondary']), 'mt-2');
        echo $OUTPUT->footer();
        exit;
    }
}

// ---- Sync recording (discover + advance pipeline + re-file) ---------------
if ($action === 'syncrec' && confirm_sesskey()) {
    require_capability('mod/edzsession:reconcile', $context);
    $occurrenceid = required_param('occurrenceid', PARAM_INT);
    $sql = "SELECT o.*, e.accountid, e.meetingprovider, e.remotemeetingid AS parentmeetingid,
                   e.storageprovider AS actstorage
              FROM {edzsession_occurrence} o
              JOIN {edzsession} e ON e.id = o.edzsessionid
             WHERE o.id = :oid";
    $occ = $DB->get_record_sql($sql, ['oid' => $occurrenceid], MUST_EXIST);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('rec_sync_title', 'mod_edzsession'));
    try {
        $storagename = provider_manager::storage_name_for_activity(
            (object) ['storageprovider' => $occ->actstorage]);
        if ($storagename === 'none' || $storagename === '') {
            echo $OUTPUT->notification(get_string('rec_storagenone', 'mod_edzsession'), 'warning');
        } else {
            $account = account_vault::get((int) $occ->accountid);
            $provider = provider_manager::get_meeting($occ->meetingprovider);
            $meetingid = (string) ($occ->remotemeetingid ?: $occ->parentmeetingid);

            $uuid = $occ->remoteuuid;
            if (empty($uuid)) {
                $uuid = $provider->resolve_occurrence_uuid(
                    new remote_meeting($meetingid, '', null), (int) $occ->starttime, $account);
                if (!empty($uuid)) {
                    $DB->set_field('edzsession_occurrence', 'remoteuuid', $uuid, ['id' => $occ->id]);
                }
            }
            if (empty($uuid)) {
                throw new \moodle_exception('nomeetinginstance', 'mod_edzsession');
            }

            $meeting = new remote_meeting($meetingid, '', $uuid);
            $all = $provider->list_recordings($meeting, $account);
            $videos = array_filter($all, fn($r) => $r->is_video());
            echo $OUTPUT->notification(get_string('rec_found', 'mod_edzsession',
                (object) ['files' => count($all), 'videos' => count($videos)]), 'info');
            foreach ($videos as $asset) {
                pipeline_manager::enqueue((int) $occ->id, $asset, $storagename);
            }

            $recs = $DB->get_records('edzsession_recording', ['occurrenceid' => $occ->id]);
            foreach ($recs as $rec) {
                for ($i = 0; $i < 12; $i++) {
                    $fresh = $DB->get_record('edzsession_recording', ['id' => $rec->id]);
                    if (!$fresh || states::is_terminal($fresh->state)) {
                        break;
                    }
                    if (!pipeline_manager::advance($fresh)) {
                        break;
                    }
                }
            }

            $moved = 0;
            $refileerror = '';
            foreach ($DB->get_records('edzsession_recording', ['occurrenceid' => $occ->id]) as $rec) {
                if (in_array($rec->state, ['finalized', 'source_deleted'], true)) {
                    try {
                        if (pipeline_manager::refile($rec)) {
                            $moved++;
                        }
                    } catch (\Throwable $e) {
                        $refileerror = $e->getMessage();
                    }
                }
            }
            if ($moved) {
                echo $OUTPUT->notification(get_string('rec_moved', 'mod_edzsession', $moved), 'success');
            } else if ($refileerror !== '') {
                echo $OUTPUT->notification(
                    get_string('rec_movefailed', 'mod_edzsession', $refileerror), 'warning');
            }
            echo $OUTPUT->notification(get_string('rec_synced', 'mod_edzsession'), 'success');
        }
    } catch (\Throwable $e) {
        echo $OUTPUT->notification(
            get_string('rec_syncfailed', 'mod_edzsession', $e->getMessage()), 'error');
        if ($e instanceof \moodle_exception && !empty($e->debuginfo)) {
            echo html_writer::tag('pre', s($e->debuginfo),
                ['class' => 'bg-light p-3 border rounded', 'style' => 'white-space:pre-wrap;']);
        }
    }
    echo html_writer::div(html_writer::link($baseurl, get_string('back'),
        ['class' => 'btn btn-secondary']), 'mt-2');
    echo $OUTPUT->footer();
    exit;
}

// ---- Overview ------------------------------------------------------------
$meta = report::session_meta($cm, $course, $edzsession);
$canreconcile = has_capability('mod/edzsession:reconcile', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_title', 'mod_edzsession', $meta->sessionname));

// Session info strip.
$info = html_writer::tag('span',
    html_writer::tag('strong', get_string('col_host', 'mod_edzsession') . ': ') . s($meta->host), ['class' => 'me-3']);
$info .= html_writer::tag('span',
    html_writer::tag('strong', get_string('meetingprovider', 'mod_edzsession') . ': ') . s($meta->provider), ['class' => 'me-3']);
echo html_writer::div($info, 'alert alert-light border');

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

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = [
    get_string('col_when', 'mod_edzsession'),
    get_string('col_duration', 'mod_edzsession'),
    get_string('col_status', 'mod_edzsession'),
    get_string('col_present', 'mod_edzsession'),
    get_string('col_avg', 'mod_edzsession'),
    get_string('rec_heading', 'mod_edzsession'),
    get_string('actions'),
];
$now = time();
foreach ($occurrences as $occ) {
    $endtime = (int) $occ->starttime + ((int) $occ->duration * 60);
    if ($now < (int) $occ->starttime) {
        $statebadge = html_writer::span(get_string('occ_upcoming', 'mod_edzsession'), 'badge bg-secondary');
        $ended = false;
    } else if ($now < $endtime) {
        $statebadge = html_writer::span(get_string('occ_inprogress', 'mod_edzsession'), 'badge bg-warning text-dark');
        $ended = false;
    } else {
        $statebadge = html_writer::span(get_string('occ_ended', 'mod_edzsession'), 'badge bg-success');
        $ended = true;
    }

    // Attendance/recording only make sense once the session has ended.
    if ($ended) {
        $summary = report::occurrence_summary((int) $occ->id);
        $detailurl = new moodle_url('/mod/edzsession/attendance.php', ['id' => $cm->id, 'occ' => $occ->id]);
        $actions = html_writer::link($detailurl, get_string('viewattendance', 'mod_edzsession'),
            ['class' => 'btn btn-primary btn-sm mb-1']);
        if ($canreconcile) {
            $actions .= ' ' . html_writer::link(
                new moodle_url($baseurl, ['action' => 'repoll', 'occurrenceid' => $occ->id, 'sesskey' => sesskey()]),
                get_string('reconcile_repoll', 'mod_edzsession'), ['class' => 'btn btn-outline-secondary btn-sm mb-1']);
            $actions .= ' ' . html_writer::link(
                new moodle_url($baseurl, ['action' => 'syncrec', 'occurrenceid' => $occ->id, 'sesskey' => sesskey()]),
                get_string('rec_sync', 'mod_edzsession'), ['class' => 'btn btn-outline-secondary btn-sm mb-1']);
        }
        $present = $summary->present . ' / ' . $summary->total;
        $avg = $summary->total > 0 ? format_float($summary->avgpercent, 1) . '%' : '-';
        $recording = mod_edzsession_recording_badge((int) $occ->id);
    } else {
        // Not held yet — no attendance actions to avoid confusion.
        $actions = html_writer::span(get_string('occ_notheld', 'mod_edzsession'), 'text-muted');
        $present = '-';
        $avg = '-';
        $recording = html_writer::span('-', 'text-muted');
    }

    $table->data[] = [
        userdate($occ->starttime),
        get_string('nminutes', 'mod_edzsession', (int) $occ->duration),
        $statebadge,
        $present,
        $avg,
        $recording,
        $actions,
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();

/**
 * Compact recording status badge (+ watch link) for the overview row.
 *
 * @param int $occurrenceid
 * @return string HTML
 */
function mod_edzsession_recording_badge(int $occurrenceid): string {
    global $DB;
    $recs = $DB->get_records('edzsession_recording', ['occurrenceid' => $occurrenceid], 'timecreated ASC');
    if (empty($recs)) {
        return html_writer::span(get_string('rec_state_none', 'mod_edzsession'), 'text-muted');
    }
    $out = [];
    foreach ($recs as $rec) {
        if (!empty($rec->embedjson)) {
            $embed = json_decode($rec->embedjson, true) ?: [];
            if (!empty($embed['url'])) {
                $out[] = html_writer::link($embed['url'], get_string('rec_watch', 'mod_edzsession'),
                    ['class' => 'btn btn-success btn-sm', 'target' => '_blank', 'rel' => 'noopener']);
                continue;
            }
        }
        if ($rec->state === 'failed') {
            $out[] = html_writer::span(get_string('rec_state_failed', 'mod_edzsession'), 'badge bg-danger');
        } else {
            $out[] = html_writer::span(get_string('rec_state_processing', 'mod_edzsession'), 'badge bg-info');
        }
    }
    return implode(' ', $out);
}
