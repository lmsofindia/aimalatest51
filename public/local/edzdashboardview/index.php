<?php
require('../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edzdashboardview/index.php'));
$PAGE->set_pagelayout('report'); // You can use 'standard', 'report', etc.
$PAGE->set_title(get_string('pluginname', 'local_edzdashboardview'));
$PAGE->set_heading(get_string('pluginname', 'local_edzdashboardview'));

// Get the renderer for your plugin.
$renderer = $PAGE->get_renderer('local_edzdashboardview');

// Print page.
echo $OUTPUT->header();
echo $renderer->render_dashboard();
echo $OUTPUT->footer();
