<?php

/**
 * Course AI Manager dashboard — lists all supported CMs with status.
 * Teachers access this via the course navigation "AI Manager" link.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$courseid = required_param('courseid', PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/edzaiaxisfront:managecourse', $context);

if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
    throw new moodle_exception('plugindisabled', 'local_edzaiaxisfront');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edzaiaxisfront/pages/dashboard.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('dashboard_title', 'local_edzaiaxisfront'));
$PAGE->set_heading($course->fullname . ': ' . get_string('ai_manager_nav', 'local_edzaiaxisfront'));
$PAGE->set_pagelayout('incourse');

// Load AMD dashboard module
$PAGE->requires->js_call_amd(
    'local_edzaiaxisfront/dashboard',
    'init',
    [[
        'courseid' => $courseid,
        'wwwroot'  => (new moodle_url('/'))->out(false),
        'sesskey'  => sesskey(),
        'wizardurl' => (new moodle_url('/local/edzaiaxisfront/pages/wizard.php', ['courseid' => $courseid]))->out(false),
    ]]
);

// Build template context
$templatectx = [
    'courseid'    => $courseid,
    'coursename'  => $course->fullname,
    'wizard_url'  => (new moodle_url('/local/edzaiaxisfront/pages/wizard.php', ['courseid' => $courseid]))->out(false),
    'sesskey'     => sesskey(),
    'strings'     => [
        'status_not_ingested' => get_string('status_not_ingested', 'local_edzaiaxisfront'),
        'status_processing'   => get_string('status_processing',   'local_edzaiaxisfront'),
        'status_ready'        => get_string('status_ready',        'local_edzaiaxisfront'),
        'status_failed'       => get_string('status_failed',       'local_edzaiaxisfront'),
    ],
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_edzaiaxisfront/dashboard', $templatectx);
echo $OUTPUT->footer();
