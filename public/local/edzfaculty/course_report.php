<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Detailed per-course report page.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$id = required_param('id', PARAM_INT);
$course = get_course($id);

$context = \context_course::instance($id);
$syscontext = \context_system::instance();

// Access: a teacher of this course, or an admin viewer.
if (!has_capability('mod/assign:grade', $context)
        && !has_capability('moodle/grade:edit', $context)
        && !\local_edzfaculty\helper\access::is_admin_viewer()) {
    throw new \required_capability_exception($context, 'local/edzfaculty:view', 'nopermissions', '');
}

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/edzfaculty/course_report.php', ['id' => $id]));
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_title(get_string('coursereporttitle', 'local_edzfaculty'));
$PAGE->set_heading(get_string('coursereporttitle', 'local_edzfaculty'));
$PAGE->add_body_class('local-edzfaculty');

$model = (new \local_edzfaculty\helper\course_report($id))->get_report();

$output = $PAGE->get_renderer('local_edzfaculty');
echo $output->header();
echo $output->render(new \local_edzfaculty\output\renderable\course_report($model));
echo $output->footer();
