<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * List all EDZ Session activities in a course.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course id.
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_login($course);

$PAGE->set_url('/mod/edzsession/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_edzsession'));

$instances = get_all_instances_in_course('edzsession', $course);
if (empty($instances)) {
    notice(get_string('noinstances', 'mod_edzsession'), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('sessionname', 'mod_edzsession')];
foreach ($instances as $instance) {
    $link = html_writer::link(
        new moodle_url('/mod/edzsession/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name),
        $instance->visible ? [] : ['class' => 'dimmed']
    );
    $table->data[] = [$link, format_string($instance->name)];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
