<?php
/**
 * Lists all edztrackvideo instances in a course.
 *
 * @package   mod_edztrackvideo
 */

require_once('../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);

require_course_login($course);
$context = context_course::instance($course->id);

$PAGE->set_url('/mod/edztrackvideo/index.php', ['id' => $courseid]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_edztrackvideo'));

$instances = get_all_instances_in_course('edztrackvideo', $course);
if (empty($instances)) {
    notice(get_string('noinstances', 'mod_edztrackvideo'), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('sourcetype', 'mod_edztrackvideo')];
foreach ($instances as $instance) {
    $url = new moodle_url('/mod/edztrackvideo/view.php', ['id' => $instance->coursemodule]);
    $link = html_writer::link($url, format_string($instance->name), $instance->visible ? [] : ['class' => 'dimmed']);
    $table->data[] = [$link, get_string('source_' . $instance->sourcetype, 'mod_edztrackvideo')];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
