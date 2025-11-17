<?php
require_once(__DIR__ . '/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/edztrainingcalendar:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edztrainingcalendar/list.php'));
$PAGE->set_title(get_string('listtitle', 'local_edztrainingcalendar'));
$PAGE->set_heading(get_string('listtitle', 'local_edztrainingcalendar'));

$filteremail = optional_param('email', '', PARAM_EMAIL);
$from = optional_param('from', 0, PARAM_INT);
$to = optional_param('to', 0, PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);

global $DB;
if ($deleteid) {
    $DB->delete_records('local_edztrainingcalendar_journey', ['id' => $deleteid]);
    redirect(new moodle_url('/local/edztrainingcalendar/list.php'));
}

// Build where clause
$where = '1=1';
$params = [];
if ($filteremail) {
    $where .= ' AND LOWER(useremail) = :email';
    $params['email'] = strtolower($filteremail);
}
if ($from) {
    $where .= ' AND uploadeddate >= :from';
    $params['from'] = $from;
}
if ($to) {
    $where .= ' AND uploadeddate <= :to';
    $params['to'] = $to;
}

$records = $DB->get_records_select('local_edztrainingcalendar_journey', $where, $params, 'uploadeddate DESC', '*', 0, 500);

echo $OUTPUT->header();
echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/edztrainingcalendar/list.php')]);
echo html_writer::tag('label', 'Email:') . html_writer::empty_tag('input', ['type' => 'email', 'name' => 'email', 'value' => s($filteremail)]);
echo html_writer::tag('label', 'From (ts):') . html_writer::empty_tag('input', ['type' => 'number', 'name' => 'from', 'value' => intval($from)]);
echo html_writer::tag('label', 'To (ts):') . html_writer::empty_tag('input', ['type' => 'number', 'name' => 'to', 'value' => intval($to)]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Filter']);
echo html_writer::end_tag('form');

echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', 'ID');
echo html_writer::tag('th', 'Email');
echo html_writer::tag('th', 'Course');
echo html_writer::tag('th', 'Start date');
echo html_writer::tag('th', 'Uploaded');
echo html_writer::tag('th', 'Processed');
echo html_writer::tag('th', 'Actions');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');
foreach ($records as $r) {
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $r->id);
    echo html_writer::tag('td', s($r->useremail));
    echo html_writer::tag('td', s($r->courseshortname));
    echo html_writer::tag('td', s($r->coursestartdate));
    echo html_writer::tag('td', userdate($r->uploadeddate));
    echo html_writer::tag('td', $r->processed_flag ? 'Yes' : 'No');
    $deleteurl = new moodle_url('/local/edztrainingcalendar/list.php', ['delete' => $r->id]);
    echo html_writer::tag('td', html_writer::link($deleteurl, get_string('delete', 'local_edztrainingcalendar')));
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
