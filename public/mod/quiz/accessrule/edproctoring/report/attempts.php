<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Proctoring report: all attempts for a quiz.
 * Accessible by teachers and managers.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

use quizaccess_edproctoring\helper\trust_score as TrustScore;

$cmid = required_param('cmid', PARAM_INT);

$cm      = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$quiz    = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cmid);

require_login($course, false, $cm);
require_capability('quizaccess/edproctoring:viewreport', $context);

$PAGE->set_url(new moodle_url('/quizaccess/edproctoring/report/attempts.php', ['cmid' => $cmid]));
$PAGE->set_title(get_string('reporttitle', 'quizaccess_edproctoring', $quiz->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

// Handle CSV export.
$export = optional_param('export', '', PARAM_ALPHA);
if ($export === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="proctoring_report_' . $quiz->id . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student', 'Attempt', 'Start Time', 'Trust Score', 'Band',
        'Critical Violations', 'Warning Violations', 'Total Violations', 'Status']);

    $sessions = $DB->get_records('quizaccess_edproctoring_session',
        ['quizid' => $quiz->id], 'timecreated DESC');

    foreach ($sessions as $s) {
        $user = core_user::get_user($s->userid);
        fputcsv($out, [
            fullname($user),
            $s->attemptid,
            date('Y-m-d H:i:s', $s->started_at),
            number_format($s->trust_score ?? 0, 1),
            TrustScore::get_band($s->trust_score ?? 100),
            $s->critical_violations,
            $s->warning_violations,
            $s->total_violations,
            $s->status,
        ]);
    }
    fclose($out);
    exit;
}

echo $OUTPUT->header();

// Build template context.
$sessions     = $DB->get_records('quizaccess_edproctoring_session',
    ['quizid' => $quiz->id], 'timecreated DESC');

$templateData = [
    'quizname'   => $quiz->name,
    'cmid'       => $cmid,
    'hasresults' => !empty($sessions),
    'sessions'   => [],
];

foreach ($sessions as $s) {
    $user  = core_user::get_user($s->userid);
    $score = (float)($s->trust_score ?? 100);
    $band  = TrustScore::get_band($score);
    $detailurl = new moodle_url('/quizaccess/edproctoring/report/attempt_detail.php',
        ['sessionid' => $s->id]);

    $duration = $s->ended_at > 0
        ? format_time($s->ended_at - $s->started_at)
        : get_string('inprogress', 'quiz');

    $templateData['sessions'][] = [
        'id'                  => $s->id,
        'studentname'         => fullname($user),
        'studentpicture'      => $OUTPUT->user_picture($user, ['size' => 24]),
        'attemptno'           => $s->attemptid,
        'starttime'           => userdate($s->started_at),
        'duration'            => $duration,
        'trust_score'         => number_format($score, 1),
        'trust_band_class'    => TrustScore::get_band_class($band),
        'trust_label'         => get_string('band_' . $band, 'quizaccess_edproctoring'),
        'critical_violations' => $s->critical_violations,
        'warning_violations'  => $s->warning_violations,
        'total_violations'    => $s->total_violations,
        'status'              => $s->status,
        'statusbadge'         => $s->status === 'completed' ? 'success' : 'secondary',
        'highRisk'            => $band === TrustScore::BAND_HIGH_RISK,
        'review'              => $band === TrustScore::BAND_REVIEW,
        'detailurl'           => $detailurl->out(false),
    ];
}

echo $OUTPUT->render_from_template('quizaccess_edproctoring/report_attempts', $templateData);

echo $OUTPUT->footer();
