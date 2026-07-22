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
 * Quiz reports — per-quiz stats (full mode) or own attempts (self mode).
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_reportpanel\helper\access;
use local_reportpanel\helper\catalogue;
use local_reportpanel\helper\quizreport;

require_login(null, false);
$context = context_system::instance();
require_capability('local/reportpanel:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reportpanel/quiz.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('quiz_title', 'local_reportpanel'));
$PAGE->set_heading(get_string('quiz_title', 'local_reportpanel'));

$backurl = (new moodle_url('/local/reportpanel/index.php'))->out(false);
$full = access::is_full_mode();

if ($full) {
    $categoryid = optional_param('categoryid', 0, PARAM_INT);
    $courseid = optional_param('courseid', 0, PARAM_INT);

    // Category options.
    $catoptions = [];
    foreach (catalogue::categories() as $id => $name) {
        $catoptions[] = ['id' => $id, 'name' => $name, 'selected' => ($id == $categoryid)];
    }

    // Course options for the selected category (server-side, so reloads/deeplinks work).
    $courseoptions = [];
    if ($categoryid) {
        foreach (catalogue::courses_in_category($categoryid) as $c) {
            $courseoptions[] = [
                'id' => $c['id'],
                'name' => $c['fullname'],
                'selected' => ($c['id'] == $courseid),
            ];
        }
    }

    $rows = [];
    $course = null;
    if ($courseid) {
        $course = catalogue::course($courseid);
        if ($course) {
            $rows = quizreport::course_quiz_stats($courseid);
        }
    }

    $templatecontext = [
        'backurl' => $backurl,
        'intro' => get_string('quiz_intro_full', 'local_reportpanel'),
        'actionurl' => (new moodle_url('/local/reportpanel/quiz.php'))->out(false),
        'categories' => $catoptions,
        'courses' => $courseoptions,
        'hascourses' => !empty($courseoptions),
        'categoryid' => $categoryid,
        'courseid' => $courseid,
        'hascourse' => (bool)$course,
        'coursename' => $course ? format_string($course->fullname) : '',
        'rows' => $rows,
        'hasrows' => !empty($rows),
    ];

    // Auto-submit the GET form when category/course changes (no custom build file
    // needed; degrades to the "Open" button when JS is unavailable).
    $PAGE->requires->js_amd_inline("
require([], function() {
    var cat = document.getElementById('reportpanel-category');
    var course = document.getElementById('reportpanel-course');
    if (cat) {
        cat.addEventListener('change', function() {
            if (course) { course.value = '0'; }
            cat.form.submit();
        });
    }
    if (course) {
        course.addEventListener('change', function() { course.form.submit(); });
    }
});
");

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_reportpanel/quiz_full', $templatecontext);
    echo $OUTPUT->footer();
} else {
    $rows = quizreport::own_attempts((int)$USER->id);
    $templatecontext = [
        'backurl' => $backurl,
        'intro' => get_string('quiz_intro_self', 'local_reportpanel'),
        'rows' => $rows,
        'hasrows' => !empty($rows),
    ];
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_reportpanel/quiz_self', $templatecontext);
    echo $OUTPUT->footer();
}
