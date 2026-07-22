<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Faculty dashboard main entry page.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$teacherid = optional_param('teacherid', 0, PARAM_INT);

$context = context_system::instance();

// A manager may view another teacher's dashboard; a teacher views their own.
if ($teacherid && $teacherid != $USER->id) {
    require_capability('local/edzfaculty:viewall', $context);
} else {
    require_capability('local/edzfaculty:view', $context);
    $teacherid = $USER->id;
}

$params = ($teacherid != $USER->id) ? ['teacherid' => $teacherid] : [];
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edzfaculty/index.php', $params));
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_title(get_string('dashboardtitle', 'local_edzfaculty'));
$PAGE->set_heading(get_string('dashboardtitle', 'local_edzfaculty'));
$PAGE->add_body_class('local-edzfaculty');

$helper = new \local_edzfaculty\helper\dashboard_helper($teacherid);
$model  = $helper->get_dashboard();

$output     = $PAGE->get_renderer('local_edzfaculty');
$renderable = new \local_edzfaculty\output\renderable\dashboard($model);

echo $output->header();

if (empty($model['courses'])) {
    // Not teaching anything — show a friendly notice rather than an empty dashboard.
    echo $output->notification(get_string('noaccess', 'local_edzfaculty'), 'info');
} else {
    if ($teacherid != $USER->id) {
        echo $output->notification(
            get_string('viewingas', 'local_edzfaculty', fullname(\core_user::get_user($teacherid))),
            'info'
        );
    }
    echo $output->render($renderable);
}

echo $output->footer();
