<?php
// Ed Proctoring — teacher report: all proctored attempts for a quiz.

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

use quizaccess_edproctoring\helper\trust_score;

$cmid     = required_param('cmid', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$cm      = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$quiz    = $DB->get_record('quiz',   ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cmid);
require_capability('quizaccess/edproctoring:viewreport', $context);

$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/edproctoring/report/index.php', ['cmid' => $cmid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('reporttitle', 'quizaccess_edproctoring', $quiz->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($quiz->name, new moodle_url('/mod/quiz/view.php', ['id' => $cmid]));
$PAGE->navbar->add(get_string('viewreport', 'quizaccess_edproctoring'));

$sessions = $DB->get_records_sql("
    SELECT s.*, u.firstname, u.lastname, u.email, qa.attempt AS attemptnumber, qa.sumgrades
      FROM {quizaccess_edproctoring_session} s
      JOIN {user} u ON u.id = s.userid
 LEFT JOIN {quiz_attempts} qa ON qa.id = s.attemptid
     WHERE s.quizid = :quizid
     ORDER BY s.timecreated DESC
", ['quizid' => $quiz->id]);

// ---------------------------------------------------------------------------
// CSV export — must run before any HTML output.
// ---------------------------------------------------------------------------
if ($download === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');

    $export = new csv_export_writer();
    $export->set_filename(clean_filename('proctoring_' . $quiz->name . '_' . date('Ymd')));
    $export->add_data([
        'Student', 'Email', 'Attempt #', 'Quiz score', 'Started', 'Completed',
        'Status', 'Trust score', 'Critical violations', 'Warning violations',
        'Total violations', 'Snapshots', 'Consent given',
    ]);

    foreach ($sessions as $s) {
        $grade = ($s->sumgrades !== null)
            ? format_float(quiz_rescale_grade($s->sumgrades, $quiz, false), 2) . ' / ' . format_float($quiz->grade, 2)
            : '';
        $export->add_data([
            fullname($s),
            $s->email,
            $s->attemptnumber ?? '',
            $grade,
            $s->started_at ? userdate($s->started_at, '%Y-%m-%d %H:%M') : '',
            $s->ended_at ? userdate($s->ended_at, '%Y-%m-%d %H:%M') : '',
            $s->status,
            $s->trust_score !== null ? format_float((float)$s->trust_score, 1) : '',
            $s->critical_violations,
            $s->warning_violations,
            $s->total_violations,
            $s->total_snapshots,
            $s->consent_given ? 'yes' : 'no',
        ]);
    }
    $export->download_file();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reporttitle', 'quizaccess_edproctoring', $quiz->name));

// Live monitor entry point for this quiz.
$liveurl = new moodle_url('/mod/quiz/accessrule/edproctoring/report/live.php', ['cmid' => $cmid]);
echo html_writer::div(
    html_writer::link($liveurl, get_string('openlivemonitor', 'quizaccess_edproctoring'),
        ['class' => 'btn btn-primary']),
    'mb-3'
);

if (empty($sessions)) {
    echo $OUTPUT->notification(get_string('noresults', 'quizaccess_edproctoring'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('student',     'quizaccess_edproctoring'),
    get_string('starttime',   'quizaccess_edproctoring'),
    get_string('status',      'quizaccess_edproctoring'),
    get_string('trustscore',  'quizaccess_edproctoring'),
    get_string('violations',  'quizaccess_edproctoring'),
    '',
];
$table->attributes['class'] = 'generaltable table table-hover';

foreach ($sessions as $s) {
    $name    = fullname($s);
    $started = userdate($s->started_at);
    $status  = ucfirst($s->status);

    if ($s->trust_score !== null) {
        $band      = trust_score::get_band((float)$s->trust_score);
        $bandclass = trust_score::get_band_class($band);
        $scorehtml = html_writer::span(
            number_format((float)$s->trust_score, 1) . '%',
            $bandclass . ' fw-bold'
        );
    } else {
        $scorehtml = html_writer::span('—', 'text-muted');
    }

    $viols = html_writer::span($s->critical_violations, 'text-danger fw-bold') . ' crit / '
           . html_writer::span($s->warning_violations,  'text-warning fw-bold') . ' warn';

    $detailurl = new moodle_url('/mod/quiz/accessrule/edproctoring/report/attempt_detail.php',
        ['sessionid' => $s->id]);
    $link = html_writer::link($detailurl, get_string('viewdetail', 'quizaccess_edproctoring'),
        ['class' => 'btn btn-sm btn-outline-secondary']);

    $table->data[] = [$name, $started, $status, $scorehtml, $viols, $link];
}

echo html_writer::table($table);

// Export CSV link.
$csvurl = new moodle_url('/mod/quiz/accessrule/edproctoring/report/index.php',
    ['cmid' => $cmid, 'download' => 'csv']);
echo html_writer::div(
    html_writer::link($csvurl, '⬇ ' . get_string('exportcsv', 'quizaccess_edproctoring'),
        ['class' => 'btn btn-sm btn-secondary mt-3']),
    'mt-2'
);

echo $OUTPUT->footer();
