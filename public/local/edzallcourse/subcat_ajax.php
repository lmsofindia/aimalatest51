<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Subcat ajax for local_edzallcourse plugin.
 *
 * @package    local_edzallcourse
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */



define('AJAX_SCRIPT', true);

require('../../config.php');
require_once($CFG->libdir . '/externallib.php');

require_sesskey();
require_capability('local/edzallcourse:view', context_system::instance());

global $PAGE, $OUTPUT;
$PAGE->set_context(context_system::instance());




// === Params ===
$categoryid = optional_param('subcategoryid', 0, PARAM_RAW);

$courses = \local_edzallcourse\local\service::get_courses_by_category($categoryid);

// Get category details
$categorydescription = '';
$categorynamee = '';

$category = \core_course_category::get($categoryid, IGNORE_MISSING);

if ($category && $category->parent != 0) { 
    $context = context_coursecat::instance($categoryid);
    $categorydescription = format_text(
        file_rewrite_pluginfile_urls(
            $category->description,
            'pluginfile.php',
            $context->id,
            'coursecat',
            'description',
            ''
        ),
        $category->descriptionformat,
        [
            'context' => $context,
            'noclean' => true,
            'trusttext' => true
        ]
    );
    $categorynamee = $category->get_formatted_name();
}


$hasmore = count($courses) > $limit;


$courses = array_slice($courses, 0, $limit);


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
        $course['teachers'] = get_string('notyetassigned', 'local_edzallcourse');
    }
    //$course['teachers'] = !empty($teachernames) ? implode(', ', $teachernames) : get_string('notyetassigned', 'local_edzallcourse');
    if (!empty($filters['compliance']) && in_array(1, $filters['compliance'])) {
        $course['badge'] = 'COMPLIANCE';
    }
}
unset($course);
// print_r($courses);die();

$template = $OUTPUT->render_from_template('local_edzallcourse/courselist', [
    'courses' => $courses,
     'noresults' => empty($courses),
]);

echo json_encode([
    'html' => $template,
    'hasmore' => $hasmore,
    'noresults' => empty($courses),
    'description' => $categorydescription,
    'categoryname' => $categorynamee
]);

die;

