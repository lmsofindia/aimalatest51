<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Faculty dashboard main entry page.
 *
 * - Managers/admins (viewall) with no teacher selected see the "All Faculty" overview.
 * - Selecting a teacher (or a normal teacher's own visit) shows that dashboard.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$teacherid = optional_param('teacherid', 0, PARAM_INT);
$context   = \context_system::instance();
$viewall   = \local_edzfaculty\helper\access::is_admin_viewer();

$PAGE->set_context($context);
$PAGE->set_pagelayout('mydashboard');
$PAGE->add_body_class('local-edzfaculty');
$output = $PAGE->get_renderer('local_edzfaculty');

if ($viewall && !$teacherid) {
    // ----- Admin / manager "All Faculty" overview -----
    $PAGE->set_url(new moodle_url('/local/edzfaculty/index.php'));
    $PAGE->set_title(get_string('overviewtitle', 'local_edzfaculty'));
    $PAGE->set_heading(get_string('overviewtitle', 'local_edzfaculty'));

    $model = (new \local_edzfaculty\helper\overview())->get_overview();

    echo $output->header();
    echo $output->render(new \local_edzfaculty\output\renderable\overview($model));
    echo $output->footer();
    return;
}

// ----- Single teacher dashboard -----
if (!$teacherid) {
    $teacherid = $USER->id;
}
if ($teacherid != $USER->id) {
    // Cross-teacher access is governed by the overviewaccess setting, not the
    // raw viewall capability (faculty may hold Manager site-wide).
    if (!$viewall) {
        throw new \required_capability_exception($context, 'local/edzfaculty:viewall', 'nopermissions', '');
    }
} else if (!\local_edzfaculty\helper\access::is_teacher() && !$viewall) {
    \local_edzfaculty\helper\access::require_teacher();
}

$params = ($teacherid != $USER->id) ? ['teacherid' => $teacherid] : [];
$PAGE->set_url(new moodle_url('/local/edzfaculty/index.php', $params));
$PAGE->set_title(get_string('dashboardtitle', 'local_edzfaculty'));
$PAGE->set_heading(get_string('dashboardtitle', 'local_edzfaculty'));

$helper     = new \local_edzfaculty\helper\dashboard_helper($teacherid);
$model      = $helper->get_dashboard();
$renderable = new \local_edzfaculty\output\renderable\dashboard($model);

echo $output->header();

if ($viewall) {
    echo \html_writer::link(
        new moodle_url('/local/edzfaculty/index.php'),
        '← ' . get_string('backtoall', 'local_edzfaculty'),
        ['class' => 'edzf-backlink']
    );
}

if (empty($model['courses'])) {
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
