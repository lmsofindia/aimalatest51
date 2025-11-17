<?php
require_once(__DIR__ . '/../../config.php');
require_login();

use local_edztrainingcalendar\form\report_filter_form;
use local_edztrainingcalendar\output\report_page;

$context = context_system::instance();
require_capability('local/edztrainingcalendar:report', $context);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edztrainingcalendar/report.php'));
$PAGE->set_title('Training Calendar Report');
$PAGE->set_heading('Training Calendar Report');

// Filter form handling (same as before)
$form = new report_filter_form();

$filters = [
    'startdate' => 0,
    'enddate' => 0,
    'courseshortname' => '',
    'search' => '',
];
if ($data = $form->get_data()) {
    $filters['startdate'] = $data->startdate ?: 0;
    $filters['enddate'] = $data->enddate ?: 0;
    $filters['courseshortname'] = $data->courseshortname ?: '';
    $filters['search'] = trim($data->search);
}

$conditions = [];
$params = [];
if ($filters['startdate']) {
    $conditions[] = 'timestamp_coursestartdate >= :start';
    $params['start'] = $filters['startdate'];
}
if ($filters['enddate']) {
    $conditions[] = 'timestamp_coursestartdate <= :end';
    $params['end'] = $filters['enddate'];
}
if (!empty($filters['courseshortname'])) {
    $conditions[] = 'courseshortname = :cs';
    $params['cs'] = $filters['courseshortname'];
}
if (!empty($filters['search'])) {
    $conditions[] = '(useremail LIKE :search OR courseshortname LIKE :search)';
    $params['search'] = '%' . $filters['search'] . '%';
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "SELECT j.*, c.id AS courseid, c.fullname AS coursefullname, u.firstname, u.lastname
        FROM {local_edztrainingcalendar_journey} j
        LEFT JOIN {user} u ON u.id = j.userid
        LEFT JOIN {course} c ON c.shortname = j.courseshortname
        $where
        ORDER BY j.timestamp_coursestartdate DESC";
$records = $DB->get_records_sql($sql, $params);

// Summaries for pie chart
$stats = ['allocated' => 0, 'inprogress' => 0, 'completed' => 0];
foreach ($records as $r) {
    $stats['allocated']++;
    $completion = $DB->get_field('course_completions', 'timecompleted', ['userid' => $r->userid, 'course' => $r->courseid]);
    if ($completion) {
        $stats['completed']++;
        $r->completionstatus = 'Completed';
        $r->actualcompletiondate = userdate($completion);
    } else {
        // if start date < today => In Progress
        $r->completionstatus = (time() > $r->timestamp_coursestartdate) ? 'In Progress' : 'Not Started';
        if ($r->completionstatus === 'In Progress') {
            $stats['inprogress']++;
        }
        $r->actualcompletiondate = '-';
    }
}

// ✅ For debugging — output the final numbers in browser console
$PAGE->requires->js_init_code("
    console.log('EDZ Chart PHP Stats →', " . json_encode($stats) . ");
");

// Pass data to JS for chart
$PAGE->requires->js_call_amd('local_edztrainingcalendar/report_chart', 'init', [$stats]);
$PAGE->requires->js_init_code("
    document.getElementById('selectall').addEventListener('change', e => {
        document.querySelectorAll('input[name=\"selected[]\"]').forEach(cb => cb.checked = e.target.checked);
    });
");


// Prepare renderable
$renderable = new report_page($records, $stats, $filters);

if (optional_param('download', 0, PARAM_INT)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="training_report.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Course', 'Student', 'Email', 'Allocated', 'Start', 'Expected', 'Actual', 'Status', 'Grade']);

    foreach ($records as $r) {
        $grade = $DB->get_field('grade_grades', 'finalgrade', [
            'userid' => $r->userid,
            'itemid' => $DB->get_field('grade_items', 'id', ['courseid' => $r->courseid, 'itemtype' => 'course'])
        ]);
        fputcsv($out, [
            $r->coursefullname,
            fullname($r),
            $r->useremail,
            userdate($r->uploadeddate),
            $r->coursestartdate,
            $r->expectedcmpldate,
            $r->actualcompletiondate,
            $r->completionstatus,
            $grade ?? '-'
        ]);
    }
    fclose($out);
    exit;
}
// Render
echo $OUTPUT->header();
// Breadcrumb - above form
$PAGE->navbar->add(get_string('pluginname', 'local_edztrainingcalendar'));
$PAGE->navbar->add('Training Calendar Report');
$form->display();
echo $OUTPUT->render_from_template('local_edztrainingcalendar/report', $renderable->export_for_template($OUTPUT));
$PAGE->requires->js_call_amd('local_edztrainingcalendar/report_dashboard', 'init', [$stats]);
echo $OUTPUT->footer();
