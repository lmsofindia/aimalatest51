<?php
require('../../config.php');
global $CFG, $PAGE, $OUTPUT;

require_capability('local/coursefilter:view', \context_system::instance());

$enabled = get_config('local_coursefilter', 'enable');
if (!$enabled) {
    print_error('disabled', 'admin');
}

$PAGE->set_url(new moodle_url('/local/coursefilter/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('viewpage', 'local_coursefilter'));
$PAGE->set_heading(get_string('viewpage', 'local_coursefilter'));

$renderer = $PAGE->get_renderer('local_coursefilter');
$service  = new \local_coursefilter\local\service();

// === Filters and search ===
$fieldsmeta = \local_coursefilter\local\service::enabled_fields();
$options    = \local_coursefilter\local\service::options_for_enabled_fields();
// echo "<pre>";
// print_r($fieldsmeta); die();
$q = optional_param('q', '', PARAM_TEXT);

// --- Load more pagination ---
$limit = 4; // initial number of courses
$filtersjson = optional_param('filters', '{}', PARAM_RAW);
$filters = json_decode($filtersjson, true) ?? [];
$categoryid = optional_param('categoryid', 0, PARAM_INT);
if ($categoryid > 0) {
    $filters['course_category'] = [$categoryid];
}

$courses = \local_coursefilter\local\service::search_courses($q, $filters, $limit + 1, 0);
$hasmore = count($courses) > $limit;

// Only keep the first $limit
$courses = array_slice($courses, 0, $limit);

// === Enrich each course with extra data ===
foreach ($courses as &$course) {
    $courseobj = get_course($course['id']); // load full course record

    // --- Course image (clean helper) ---
    $course['courseimage'] = \core_course\external\course_summary_exporter::get_course_image($courseobj);

    // --- Category ---
    $category = core_course_category::get($courseobj->category, IGNORE_MISSING);
    $course['categoryname'] = $category ? $category->get_formatted_name() : '';

    // --- Start date ---
    $course['startdate'] = $courseobj->startdate ? userdate($courseobj->startdate, get_string('strftimedate', 'langconfig')) : '';

    // --- Teacher(s) ---
    $context = context_course::instance($courseobj->id);
    $teachers = get_role_users(3, $context); // roleid 3 = editingteacher (default)
    $teachernames = [];
    foreach ($teachers as $t) {
       $teachernames[] = fullname($t);
    }
    // for counter
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
}
unset($course);

// === Data for template ===
$data = [
    'searchplaceholder' => get_string('searchplaceholder', 'local_coursefilter'),
    'filtersenabled' => !empty($fieldsmeta),
    'filters' => [],
    'courses' => $courses,
    'noresults' => empty($courses),
    'hasmore' => $hasmore,
];

// foreach ($fieldsmeta as $shortname => $meta) {
//     $data['filters'][] = [
//         'shortname' => $shortname,
//         'name' => $meta['label'],
//         'options' => $options[$shortname] ?? [],
//     ];
// }
foreach ($fieldsmeta as $shortname => $meta) {
    $opts = $options[$shortname] ?? [];

    // Mark the category filter option as checked
    if ($shortname === 'course_category' && $categoryid > 0) {
        foreach ($opts as &$opt) {
            if ((int)$opt['value'] === $categoryid) {
                $opt['checked'] = true;
            }
        }
        unset($opt); // break reference
    }

    $data['filters'][] = [
        'shortname' => $shortname,
        'name' => $meta['label'],
        'options' => $opts,
    ];
}

// === Render ===
echo $OUTPUT->header();
echo $renderer->render_index_page($data);
echo $OUTPUT->footer();
