<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

// Get type parameter (default empty string).
$type = optional_param('type', '', PARAM_ALPHA);

// Page setup.
$context = context_system::instance();
$PAGE->set_url(new moodle_url('/local/edzcategoryview/index.php', ['type' => $type]));
$PAGE->set_context($context);

echo $OUTPUT->header();

$template = '';
$data = [];

    // Get selected department category from settings.
    $departmentcategoryid = get_config('local_edzcategoryview', 'departmentcategoryid');


    // Topic settings.
    $enabletopic = get_config('local_edzcategoryview', 'enabletopic');
    $topictitle = get_config('local_edzcategoryview', 'topictitle') ?: 'Browse by Topic';
    $topicsubtitle = get_config('local_edzcategoryview', 'topicsubtitle') ?: 'Select a topic to explore resources.';
    $topicbgcolor      = get_config('local_edzcategoryview', 'topicbgcolor');
    $topictitlecolor      = get_config('local_edzcategoryview', 'topictitlecolor');

    // Department settings.
    $enabledepart = get_config('local_edzcategoryview', 'enabledepartment');
    $departtitle = get_config('local_edzcategoryview', 'departmenttitle') ?: 'Browse by Department';
    $departsubtitle = get_config('local_edzcategoryview', 'departmentsubtitle') ?: 'Select a department to explore resources.';
    $departmentbgcolor = get_config('local_edzcategoryview', 'departmentbgcolor');
    $departmenttitlecolor = get_config('local_edzcategoryview', 'departmenttitlecolor');



if ($type === 'topic' && $enabletopic) {
    $template = 'local_edzcategoryview/topic';

    // $data['heading'] = get_string('browsebytopic', 'local_edzcategoryview');
    // $data['subheading'] = get_string('topic_subheading', 'local_edzcategoryview');


    $data['heading'] = $topictitle;
    $data['subheading'] = $topicsubtitle;
     $data['bgcolor'] = $topicbgcolor;
     $data['titlecolor'] = $topictitlecolor;
    $data['breadcrumb'] = [
        ['name' => get_string('home', 'local_edzcategoryview'), 'url' => new moodle_url('/')],
    ];
    $data['categories'] = local_edzcategoryview_get_category_tree(0, $departmentcategoryid);

} elseif ($type === 'depart' && $enabledepart) {
    $template = 'local_edzcategoryview/depart';

    $data['heading'] = $departtitle;
    $data['subheading'] = $departsubtitle;
     $data['bgcolor'] = $departmentbgcolor;
     $data['titlecolor'] = $departmenttitlecolor;
    $data['breadcrumb'] = [
        ['name' => get_string('home', 'local_edzcategoryview'), 'url' => new moodle_url('/')],
    ];

    $data['departments'] = [];
    if ($departmentcategoryid) {
        $data['departments'] = local_edzcategoryview_get_category_tree($departmentcategoryid);
    }
} else {
    echo html_writer::tag('p', get_string('invalidtype', 'local_edzcategoryview'));
}


if ($template) {
    echo $OUTPUT->render_from_template($template, $data);
}

echo $OUTPUT->footer();
