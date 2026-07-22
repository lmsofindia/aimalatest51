<?php
// Ed Proctoring — consolidated PDF report for one proctoring session.

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/pdflib.php');

use quizaccess_edproctoring\helper\trust_score;

$sessionid = required_param('sessionid', PARAM_INT);

$session = $DB->get_record('quizaccess_edproctoring_session', ['id' => $sessionid], '*', MUST_EXIST);
$cm      = get_coursemodule_from_id('quiz', $session->cmid, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$quiz    = $DB->get_record('quiz',   ['id' => $session->quizid], '*', MUST_EXIST);
$student = core_user::get_user($session->userid, '*', MUST_EXIST);
$attempt = $DB->get_record('quiz_attempts', ['id' => $session->attemptid]);

require_login($course, false, $cm);
$context = context_module::instance($session->cmid);
require_capability('quizaccess/edproctoring:viewreport', $context);

$violations = $DB->get_records('quizaccess_edproctoring_violation',
    ['sessionid' => $sessionid], 'timecreated ASC');
$snaps = $DB->get_records('quizaccess_edproctoring_snap',
    ['sessionid' => $sessionid], 'timecaptured ASC');

// ---------------------------------------------------------------------------
// Assemble data.
// ---------------------------------------------------------------------------
$trust = $session->trust_score !== null ? number_format((float)$session->trust_score, 1) . '%' : 'Pending';

$attemptno = '-'; $quizscore = '-'; $started = '-'; $completed = '-'; $duration = '-';
if ($attempt) {
    $attemptno = $attempt->attempt;
    $grade     = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
    $quizscore = ($grade !== null ? format_float($grade, 2) : '-') . ' / ' . format_float($quiz->grade, 2);
    $started   = userdate($attempt->timestart, '%d %b %Y, %H:%M');
    if ($attempt->timefinish) {
        $completed = userdate($attempt->timefinish, '%d %b %Y, %H:%M');
        $duration  = format_time($attempt->timefinish - $attempt->timestart);
    }
}

$sm = get_string_manager();

// ---------------------------------------------------------------------------
// Build PDF.
// ---------------------------------------------------------------------------
$pdf = new pdf();
$pdf->SetCreator('quizaccess_edproctoring');
$pdf->SetAuthor(format_string($course->fullname));
$pdf->SetTitle('Proctoring report — ' . fullname($student));
$pdf->SetMargins(14, 16, 14);
$pdf->SetAutoPageBreak(true, 18);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->AddPage();

// Title.
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 9, 'Proctoring Report', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(110, 110, 110);
$pdf->Cell(0, 5, format_string($course->fullname) . '  —  ' . format_string($quiz->name)
    . '  •  Generated ' . userdate(time(), '%d %b %Y, %H:%M'), 0, 1);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

// Summary block (two-column table).
$sev = ['critical' => '#a51c30', 'warning' => '#b35900', 'info' => '#6c757d'];
$summaryhtml = '
<table cellpadding="6" cellspacing="0" border="0" style="background-color:#f5f6f8;">
 <tr>
  <td width="25%"><span style="color:#777;font-size:8pt;">STUDENT</span><br/><b>' . s(fullname($student)) . '</b><br/><span style="font-size:8pt;">' . s($student->email) . '</span></td>
  <td width="25%"><span style="color:#777;font-size:8pt;">ATTEMPT #</span><br/><b>' . $attemptno . '</b></td>
  <td width="25%"><span style="color:#777;font-size:8pt;">QUIZ SCORE</span><br/><b>' . $quizscore . '</b></td>
  <td width="25%"><span style="color:#777;font-size:8pt;">TRUST SCORE</span><br/><b style="font-size:13pt;">' . $trust . '</b></td>
 </tr>
 <tr>
  <td width="25%"><span style="color:#777;font-size:8pt;">STARTED</span><br/><b>' . $started . '</b></td>
  <td width="25%"><span style="color:#777;font-size:8pt;">COMPLETED</span><br/><b>' . $completed . '</b></td>
  <td width="25%"><span style="color:#777;font-size:8pt;">DURATION</span><br/><b>' . $duration . '</b></td>
  <td width="25%"><span style="color:#777;font-size:8pt;">VIOLATIONS</span><br/>
      <b style="color:' . $sev['critical'] . ';">Critical: ' . (int)$session->critical_violations . '</b><br/>
      <b style="color:' . $sev['warning'] . ';">Warning: ' . (int)$session->warning_violations . '</b>
      &nbsp; Snapshots: ' . (int)$session->total_snapshots . '</td>
 </tr>
</table>';
$pdf->writeHTML($summaryhtml, true, false, true, false, '');
$pdf->Ln(2);

// Violation timeline.
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Violation Timeline', 0, 1);
$pdf->SetFont('helvetica', '', 9);

if ($violations) {
    $vhtml = '<table cellpadding="5" cellspacing="0" border="0.5" style="border-color:#cccccc;">
      <tr style="background-color:#eceef4;">
        <th width="10%"><b>Time</b></th>
        <th width="22%"><b>Type</b></th>
        <th width="13%"><b>Severity</b></th>
        <th width="45%"><b>Details</b></th>
        <th width="10%"><b>Status</b></th>
      </tr>';
    foreach ($violations as $v) {
        $colour = $sev[$v->severity] ?? '#000000';
        $msgkey   = 'warnmsg_' . $v->violation_type;
        $friendly = $sm->string_exists($msgkey, 'quizaccess_edproctoring')
            ? get_string($msgkey, 'quizaccess_edproctoring')
            : $v->violation_type;
        $extra = (!empty($v->details) && $v->details !== '{}')
            ? '<br/><span style="color:#888;font-size:7.5pt;">' . s($v->details) . '</span>' : '';
        $vhtml .= '<tr>
          <td width="10%">' . (int)$v->elapsed_seconds . 's</td>
          <td width="22%"><b>' . s($v->violation_type) . '</b></td>
          <td width="13%"><b style="color:' . $colour . ';">' . ucfirst(s($v->severity)) . '</b></td>
          <td width="45%">' . s($friendly) . $extra . '</td>
          <td width="10%">' . ($v->dismissed ? 'Dismissed' : 'Active') . '</td>
        </tr>';
    }
    $vhtml .= '</table>';
    $pdf->writeHTML($vhtml, true, false, true, false, '');
} else {
    $pdf->Cell(0, 6, 'No violations recorded.', 0, 1);
}
$pdf->Ln(4);

// Captured images grid with severity borders.
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Captured Images', 0, 1);

if ($snaps) {
    $fs = get_file_storage();
    $imgw = 42; $imgh = 31.5; $gap = 4; $labelh = 5;
    $perrow = 4;
    $x0 = 14; $col = 0;

    foreach ($snaps as $snap) {
        $file = $fs->get_file_by_hash($snap->pathnamehash);
        if (!$file) {
            continue;
        }

        // Severity: direct link, else nearest violation within 8s, else critical for bursts.
        $severity = null;
        if ($snap->is_violation) {
            // Direct snap_id link wins; unlinked violation snaps are critical bursts.
            $severity = 'critical';
            foreach ($violations as $v) {
                if ((int)$v->snap_id === (int)$snap->id) { $severity = $v->severity; break; }
            }
        }

        if ($col === 0 && $pdf->GetY() + $imgh + $labelh + $gap > 279) {
            $pdf->AddPage();
        }
        $x = $x0 + $col * ($imgw + $gap);
        $y = $pdf->GetY();

        $pdf->Image('@' . $file->get_content(), $x, $y, $imgw, $imgh, 'PNG');

        // Border colour by severity.
        if ($severity === 'critical')      { $pdf->SetDrawColor(165, 28, 48);  $pdf->SetLineWidth(0.8); }
        else if ($severity === 'warning')  { $pdf->SetDrawColor(179, 89, 0);   $pdf->SetLineWidth(0.8); }
        else if ($severity !== null)       { $pdf->SetDrawColor(108, 117, 125); $pdf->SetLineWidth(0.8); }
        else                               { $pdf->SetDrawColor(200, 200, 200); $pdf->SetLineWidth(0.2); }
        $pdf->Rect($x, $y, $imgw, $imgh);

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($x, $y + $imgh);
        $pdf->Cell($imgw, $labelh, $snap->capture_type . ' +' . (int)$snap->elapsed_seconds . 's'
            . ($severity ? ' (' . $severity . ')' : ''), 0, 0, 'C');

        $col++;
        if ($col >= $perrow) {
            $col = 0;
            $pdf->SetY($y + $imgh + $labelh + 2);
        } else {
            $pdf->SetY($y);
        }
    }
    if ($col !== 0) {
        $pdf->SetY($pdf->GetY() + $imgh + $labelh + 2);
    }
} else {
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 6, 'No snapshots stored for this session.', 0, 1);
}

$filename = clean_filename('proctoring_report_' . fullname($student) . '_attempt' . $attemptno . '.pdf');
$pdf->Output($filename, 'D');
