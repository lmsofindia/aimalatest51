<?php
// Ed Proctoring — attempt detail: consolidated summary, violation timeline + snapshot gallery.

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

use quizaccess_edproctoring\helper\trust_score;
use quizaccess_edproctoring\helper\image_store;

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

$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/edproctoring/report/attempt_detail.php',
    ['sessionid' => $sessionid]));
$PAGE->set_context($context);
$PAGE->set_title(fullname($student) . ' — ' . $quiz->name);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($quiz->name, new moodle_url('/mod/quiz/view.php', ['id' => $session->cmid]));
$PAGE->navbar->add(get_string('viewreport', 'quizaccess_edproctoring'),
    new moodle_url('/mod/quiz/accessrule/edproctoring/report/index.php', ['cmid' => $session->cmid]));
$PAGE->navbar->add(fullname($student));

$PAGE->requires->js_call_amd('quizaccess_edproctoring/report_dismiss', 'init');

echo $OUTPUT->header();

// Heading with Download PDF button.
$pdfurl = new moodle_url('/mod/quiz/accessrule/edproctoring/report/download_pdf.php',
    ['sessionid' => $sessionid]);
echo html_writer::div(
    $OUTPUT->heading(fullname($student) . ' — ' . $quiz->name, 2, 'd-inline-block me-3') .
    html_writer::link($pdfurl, '⬇ ' . get_string('downloadpdf', 'quizaccess_edproctoring'),
        ['class' => 'btn btn-primary float-end']),
    'clearfix mb-2'
);

// ---------------------------------------------------------------------------
// Consolidated attempt summary.
// ---------------------------------------------------------------------------
$score     = $session->trust_score !== null ? number_format((float)$session->trust_score, 1) . '%' : 'Pending';
$band      = $session->trust_score !== null ? trust_score::get_band((float)$session->trust_score) : 'low_risk';
$bandclass = trust_score::get_band_class($band);

$quizscore = '-';
$attemptno = '-';
$started   = '-';
$completed = '-';
$duration  = '-';
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

$rows = [
    [get_string('student', 'quizaccess_edproctoring'), fullname($student) . ' (' . $student->email . ')'],
    ['Course / Quiz',  format_string($course->fullname) . ' — ' . format_string($quiz->name)],
    ['Attempt #',      $attemptno],
    ['Quiz score',     $quizscore],
    ['Started',        $started],
    ['Completed',      $completed],
    ['Duration',       $duration],
];
$summary = html_writer::start_div('row');
foreach ($rows as $r) {
    $summary .= html_writer::div(
        html_writer::div($r[0], 'small text-muted') . html_writer::div($r[1], 'fw-bold'),
        'col-md-3 col-6 mb-3'
    );
}
$summary .= html_writer::div(
    html_writer::div(get_string('trustscore', 'quizaccess_edproctoring'), 'small text-muted') .
    html_writer::div($score, $bandclass . ' fw-bold fs-4'),
    'col-md-3 col-6 mb-3'
);
$summary .= html_writer::div(
    html_writer::div('Violations', 'small text-muted') .
    html_writer::div(
        html_writer::span('Critical: ' . $session->critical_violations, 'text-danger fw-bold me-2') .
        html_writer::span('Warning: ' . $session->warning_violations, 'fw-bold me-2', ['style' => 'color:#b35900']) .
        html_writer::span('Snapshots: ' . $session->total_snapshots, 'text-muted fw-bold')
    ),
    'col-md-6 col-12 mb-3'
);
$summary .= html_writer::end_div();
echo html_writer::div($summary, 'card card-body mb-4');

// ---------------------------------------------------------------------------
// Violation timeline.
// ---------------------------------------------------------------------------
echo $OUTPUT->heading(get_string('violationtimeline', 'quizaccess_edproctoring'), 4);
$violations = $DB->get_records('quizaccess_edproctoring_violation',
    ['sessionid' => $sessionid], 'timecreated ASC');

$sm = get_string_manager();

if ($violations) {
    $vt = new html_table();
    $vt->head = ['Time (s)', get_string('status', 'quizaccess_edproctoring'), 'Severity', 'Details', 'Action'];
    $vt->attributes['class'] = 'generaltable table table-sm';
    foreach ($violations as $v) {
        $sevclass = $v->severity === 'critical' ? 'text-danger'
            : ($v->severity === 'warning' ? 'text-warning' : 'text-muted');

        // Human-friendly description (same message the student saw in the modal).
        $msgkey   = 'warnmsg_' . $v->violation_type;
        $friendly = $sm->string_exists($msgkey, 'quizaccess_edproctoring')
            ? get_string($msgkey, 'quizaccess_edproctoring')
            : get_string('violation_' . $v->violation_type, 'quizaccess_edproctoring');
        $detail = html_writer::div($friendly);
        if (!empty($v->details) && $v->details !== '{}') {
            $detail .= html_writer::div(s($v->details), 'small text-muted');
        }

        $dismissed = $v->dismissed
            ? html_writer::span('Dismissed', 'badge bg-secondary')
            : html_writer::link('#', 'Dismiss',
                ['class' => 'btn btn-xs btn-outline-secondary edp-dismiss', 'data-id' => $v->id]);
        $vt->data[] = [
            $v->elapsed_seconds . 's',
            html_writer::tag('strong', $v->violation_type),
            html_writer::span(ucfirst($v->severity), $sevclass),
            $detail,
            $dismissed,
        ];
    }
    echo html_writer::table($vt);
} else {
    echo $OUTPUT->notification('No violations recorded.', 'success');
}

// ---------------------------------------------------------------------------
// Snapshot gallery (most recent 20) with severity-coloured borders.
// ---------------------------------------------------------------------------
echo $OUTPUT->heading(get_string('imagegallery', 'quizaccess_edproctoring'), 4);
$snaps = $DB->get_records('quizaccess_edproctoring_snap',
    ['sessionid' => $sessionid], 'timecaptured DESC', '*', 0, 20);

/**
 * Resolve the severity for a snapshot: direct snap_id link first, then
 * nearest violation within 8 seconds, else 'critical' for burst captures.
 *
 * @param stdClass $snap snapshot record
 * @param array $violations all violation records for the session
 * @return string|null severity or null when the snap is a routine capture
 */
function quizaccess_edproctoring_snap_severity($snap, $violations) {
    if (!$snap->is_violation) {
        return null;
    }
    // Direct link wins — every violation stores the snap it triggered.
    foreach ($violations as $v) {
        if ((int)$v->snap_id === (int)$snap->id) {
            return $v->severity;
        }
    }
    // Unlinked violation snaps can only come from critical evidence bursts,
    // so never proximity-match them against warning/info violations.
    return 'critical';
}

if ($snaps) {
    $gallery = '';
    foreach ($snaps as $snap) {
        $url = image_store::get_snapshot_url($snap->pathnamehash);
        if (!$url) {
            continue;
        }
        $severity = quizaccess_edproctoring_snap_severity($snap, $violations ?: []);
        if ($severity === 'critical') {
            $borderstyle = 'border:3px solid #a51c30;';   // Dark red.
            $flag = '🔴 ';
        } else if ($severity === 'warning') {
            $borderstyle = 'border:3px solid #b35900;';   // Dark orange.
            $flag = '🟠 ';
        } else if ($severity !== null) {
            $borderstyle = 'border:3px solid #6c757d;';   // Info — grey.
            $flag = 'ℹ ';
        } else {
            $borderstyle = 'border:1px solid #dee2e6;';
            $flag = '';
        }
        $label = $flag . $snap->capture_type . ' +' . $snap->elapsed_seconds . 's';
        $gallery .= html_writer::div(
            html_writer::img($url->out(), $label, ['class' => 'img-thumbnail', 'style' => 'width:140px']) .
            html_writer::div($label, 'small text-center'),
            'd-inline-block m-1 p-1 rounded',
            ['style' => $borderstyle]
        );
    }
    echo html_writer::div($gallery, 'd-flex flex-wrap');
} else {
    echo $OUTPUT->notification('No snapshots stored for this session.', 'info');
}

echo $OUTPUT->footer();
