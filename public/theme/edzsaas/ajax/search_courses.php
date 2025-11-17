<?php
// File: theme/edzsaas/ajax/search_courses.php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/theme/edzsaas/lib/frontpage_settings.php');
// require_login();

$PAGE->set_context(context_system::instance());
header('Content-Type: application/json');

global $DB;


$q = optional_param('q', '', PARAM_TEXT);


$response = [
    'keywords' => [],
    'courses' => []
];

if (!empty($q)) {
    $response['keywords'][] = $q;

    $like = $DB->sql_like('fullname', ':search', false, false);
    $sql = "SELECT id, category, fullname, shortname, summary, visible, format
            FROM {course}
            WHERE visible = 1 AND category != 0 AND $like ORDER BY fullname ASC
            LIMIT 4";
    $params = ['search' => '%' . $q . '%'];

    $records = $DB->get_records_sql($sql, $params);

    foreach ($records as $record) {
        $imageurl = theme_edzsaas_get_course_image_search($record); 
        $response['courses'][] = [
            'fullname' => format_string($record->fullname),
            'url' => (new moodle_url('/course/view.php', ['id' => $record->id]))->out(false),
            'image' => $imageurl ?: '',
        ];
    }
}
echo json_encode($response);
exit;
