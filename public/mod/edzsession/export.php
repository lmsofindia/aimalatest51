<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Export one occurrence's attendance as CSV or PDF. Both formats lead with a
 * title block (session name, course, host, date/time, duration, totals) so the
 * file is self-describing.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_edzsession\local\attendance\report;

$id = required_param('id', PARAM_INT);         // Course module id.
$occid = required_param('occ', PARAM_INT);      // Occurrence id.
$format = required_param('format', PARAM_ALPHA); // csv | pdf.

$cm = get_coursemodule_from_id('edzsession', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$edzsession = $DB->get_record('edzsession', ['id' => $cm->instance], '*', MUST_EXIST);
$occ = $DB->get_record('edzsession_occurrence',
    ['id' => $occid, 'edzsessionid' => $edzsession->id], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/edzsession:viewall', $context);

$meta = report::session_meta($cm, $course, $edzsession);
$summary = report::occurrence_summary($occid);
$rows = report::rows($occid); // All rows for export.

// Metadata pairs shown in the title block of both formats.
$metapairs = [
    [get_string('sessionname', 'mod_edzsession'), $meta->sessionname],
    [get_string('course'), $meta->coursename],
    [get_string('col_host', 'mod_edzsession'), $meta->host],
    [get_string('meetingprovider', 'mod_edzsession'), $meta->provider],
    [get_string('col_when', 'mod_edzsession'), userdate($occ->starttime)],
    [get_string('col_duration', 'mod_edzsession'), get_string('nminutes', 'mod_edzsession', (int) $occ->duration)],
    [get_string('col_present', 'mod_edzsession'), $summary->present . ' / ' . $summary->total],
    [get_string('col_avg', 'mod_edzsession'),
        $summary->total > 0 ? format_float($summary->avgpercent, 1) . '%' : '-'],
    [get_string('exported_on', 'mod_edzsession'), userdate(time())],
];

$colheads = [
    get_string('col_num', 'mod_edzsession'),
    get_string('col_participant', 'mod_edzsession'),
    get_string('col_email', 'mod_edzsession'),
    get_string('col_user', 'mod_edzsession'),
    get_string('col_minutes', 'mod_edzsession'),
    get_string('col_percent', 'mod_edzsession'),
    get_string('col_match', 'mod_edzsession'),
];

$filebase = clean_filename(format_string($edzsession->name) . '_' . userdate($occ->starttime, '%Y%m%d_%H%M'));

if ($format === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');
    // Neutralise spreadsheet formula injection: a cell beginning with = + - @
    // (or tab/CR) is prefixed with an apostrophe so Excel/Sheets treats it as text.
    $safe = function ($v) {
        $s = (string) $v;
        if ($s !== '' && preg_match('/^[=+\-@\t\r]/', $s)) {
            return "'" . $s;
        }
        return $s;
    };
    $csv = new \csv_export_writer();
    $csv->set_filename($filebase);
    foreach ($metapairs as $pair) {
        $csv->add_data(array_map($safe, $pair));
    }
    $csv->add_data(['']);
    $csv->add_data($colheads);
    $n = 0;
    foreach ($rows as $r) {
        $n++;
        $csv->add_data([
            $n,
            $safe($r->participant),
            $safe($r->email),
            $safe($r->username !== '' ? $r->username : get_string('unmatched', 'mod_edzsession')),
            $r->minutes,
            format_float($r->percent, 1) . '%',
            $safe($r->matchstate),
        ]);
    }
    $csv->download_file();
    exit;
}

if ($format === 'pdf') {
    require_once($CFG->libdir . '/pdflib.php');
    $pdf = new \pdf();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetTitle(format_string($edzsession->name));
    $pdf->SetMargins(12, 12, 12);
    $pdf->AddPage();

    $html = html_writer::tag('h2', s($meta->sessionname));

    // Title block.
    $metahtml = '';
    foreach ($metapairs as $pair) {
        $metahtml .= '<tr><td width="30%" style="background-color:#f2f2f2;"><b>' . s($pair[0]) . '</b></td>'
            . '<td>' . s($pair[1]) . '</td></tr>';
    }
    $html .= '<table border="1" cellpadding="4" cellspacing="0">' . $metahtml . '</table><br><br>';

    // Participant table.
    $rowshtml = '<tr style="background-color:#e6e6e6;">';
    foreach ($colheads as $h) {
        $rowshtml .= '<th><b>' . s($h) . '</b></th>';
    }
    $rowshtml .= '</tr>';
    $n = 0;
    foreach ($rows as $r) {
        $n++;
        $user = $r->username !== '' ? $r->username : get_string('unmatched', 'mod_edzsession');
        $rowshtml .= '<tr>'
            . '<td>' . $n . '</td>'
            . '<td>' . s($r->participant) . '</td>'
            . '<td>' . s($r->email) . '</td>'
            . '<td>' . s($user) . '</td>'
            . '<td>' . $r->minutes . '</td>'
            . '<td>' . format_float($r->percent, 1) . '%</td>'
            . '<td>' . s($r->matchstate) . '</td>'
            . '</tr>';
    }
    $html .= '<table border="1" cellpadding="4" cellspacing="0">' . $rowshtml . '</table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($filebase . '.pdf', 'D');
    exit;
}

throw new \moodle_exception('invalidformat', 'error');
