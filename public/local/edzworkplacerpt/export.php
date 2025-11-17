<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/edzworkplacerpt/classes/helper.php');

use local_edzworkplacerpt\helper;

$context = context_system::instance();
require_login();
//require_capability('local/edzworkplacerpt:viewreport', $context);

$startdate = optional_param('startdate', 0, PARAM_INT);
$enddate = optional_param('enddate', 0, PARAM_INT);
$level = optional_param('level', 1, PARAM_INT);
$category = optional_param('category', 0, PARAM_INT);
$course = optional_param('course', 0, PARAM_INT);
$department = optional_param('department', '', PARAM_TEXT);
$showonly = optional_param('showonly', 'all', PARAM_TEXT);

if ($level === -1) {
    $level = 0;
}

$filters = [];
if ($startdate) {
    $filters['startdate'] = $startdate;
}
if ($enddate) {
    $filters['enddate'] = $enddate;
}
if ($category) {
    $filters['categoryid'] = $category;
}
if ($course) {
    $filters['courseid'] = $course;
}
if ($department) {
    $filters['department'] = $department;
}
if ($showonly) {
    $filters['showonly'] = $showonly;
}

$subordinates = helper::get_subordinates($USER->id, $level);
$report = helper::fetch_report_rows($subordinates, $filters, 0, 0); // limit=0 => all

$filename = 'edzworkplace_report_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$out = fopen('php://output', 'w');
// header row
fputcsv($out, ['Name', 'Email', 'Department', 'Course', 'Enrolment Date', 'Completion Date', 'Badges', 'Has Certificate']);

foreach ($report['rows'] as $r) {
    $enrol = $r['enrolmenttime'] ? userdate($r['enrolmenttime'], '%d-%b-%y') : '';
    $comp = $r['completiontime'] ? userdate($r['completiontime'], '%d-%b-%y') : '';
    fputcsv($out, [
        $r['name'],
        $r['email'],
        $r['department'],
        $r['coursename'] ?? '',
        $enrol,
        $comp,
        $r['badgecount'],
        $r['hascertificate'] ? 'Yes' : 'No'
    ]);
}
fclose($out);
exit;
