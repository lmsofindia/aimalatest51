<?php
define('AJAX_SCRIPT', true);

require('../../config.php');
require_once($CFG->libdir . '/externallib.php');

require_sesskey();
require_capability('local/coursefilter:view', context_system::instance());

global $PAGE, $OUTPUT;
$PAGE->set_context(context_system::instance());

// === Params ===
$search  = optional_param('q', '', PARAM_RAW_TRIMMED);
$filtersjson = optional_param('filters', '{}', PARAM_RAW);
$filters = json_decode($filtersjson, true) ?? [];
$sort = required_param('sort', PARAM_ALPHA);
$offset  = optional_param('offset', 0, PARAM_INT);
$limit   = 4;
$courses = \local_coursefilter\local\service::search_courses($search, $filters, $limit + 1, $offset,$sort);
$hasmore = count($courses) > $limit;

// Only keep the first $limit
$courses = array_slice($courses, 0, $limit);

// === Enrich courses with extra data ===
foreach ($courses as &$course) {
    $courseobj = get_course($course['id']);

    // --- Course image ---
    $course['courseimage'] = \core_course\external\course_summary_exporter::get_course_image($courseobj);

    // --- Category ---
    $category = core_course_category::get($courseobj->category, IGNORE_MISSING);
    $course['categoryname'] = $category ? $category->get_formatted_name() : '';

    // --- Start date ---
    $course['startdate'] = $courseobj->startdate ? userdate($courseobj->startdate, get_string('strftimedate', 'langconfig')) : '';

    // --- Teachers ---
    $context = context_course::instance($courseobj->id);
    $teachers = get_role_users(3, $context); // roleid 3 = editingteacher (default)
    $teachernames = [];
    foreach ($teachers as $t) {
        $teachernames[] = fullname($t);
    }
    if (!empty($teachernames)) {
    $firstteacher = reset($teachernames); // first teacher only
    $total = count($teachernames);

    if ($total > 1) {
        $course['teachers'] = $firstteacher . ' +' . ($total - 1);
    } else {
        $course['teachers'] = $firstteacher;
        }
    } else {
        $course['teachers'] = get_string('notyetassigned', 'local_coursefilter');
    }
    //$course['teachers'] = !empty($teachernames) ? implode(', ', $teachernames) : get_string('notyetassigned', 'local_coursefilter');
    if (!empty($filters['compliance']) && in_array(1, $filters['compliance'])) {
        $course['badge'] = 'COMPLIANCE';
    }
}
unset($course);

// === Render items only (not full page) ===
$template = $OUTPUT->render_from_template('local_coursefilter/coursegrid_items', [
    'courses' => $courses,
]);

echo json_encode([
    'html' => $template,
    'hasmore' => $hasmore
]);

die;
