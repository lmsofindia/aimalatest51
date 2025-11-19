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

/**.
 * Hook callbacks
 * @package    local_edzmyenrollcourse
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */

namespace local_edzmyenrollcourse\hooks;

use core\hook\navigation\primary_extend;
use pix_icon;
use navigation_node;
use core\hook\output\before_http_headers;
use core\hook\output\before_standard_head_html_generation;
use moodle_url;
use core_course_category;

class hook_callbacks {

    /**
     * Callback allowing to add primary node
     *
     * @param \core\hook\navigation\primary_extend $hook
     */


public static function primary_navigation_extend(primary_extend $hook): void {
    global $PAGE, $OUTPUT, $CFG, $USER;

    if (!get_config('local_edzmyenrollcourse', 'enable')) {
        return;
    }

    require_once($CFG->dirroot.'/course/lib.php');

    // Get only courses where user is ENROLLED
    $enrolledCourses = enrol_get_users_courses(
        $USER->id,
        true,
        'id, fullname, category, visible'
    );

    $coursesForTemplate = [];

    foreach ($enrolledCourses as $course) {
        if (!$course->visible) continue;

        // Build category path
        $fullPath = self::get_category_full_path($course->category);

        $coursesForTemplate[] = [
            'name' => $course->fullname,
            'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'category_path' => $fullPath
        ];
    }

    $templatecontext = [
        'courses' => $coursesForTemplate,
        'hascourses' => !empty($coursesForTemplate),
        
    ];

    $allencouhtml = $OUTPUT->render_from_template('local_edzmyenrollcourse/index', $templatecontext);
    $PAGE->requires->js_init_code("window.allEnCourseMenuHTML = " . json_encode($allencouhtml) . ";");
    $PAGE->requires->js_call_amd('local_edzmyenrollcourse/allencourse', 'init');

    $hook->get_primaryview()->add(
        get_string('my_enroll_course', 'local_edzmyenrollcourse'),
        new moodle_url('#'),
        navigation_node::TYPE_CUSTOM,
        2,
        'edzallencou',
        new pix_icon('i/navigationitem', '')
    );
}

/**
 * Full category path like "Cat A / Cat B / Cat C"
 */
private static function get_category_full_path($categoryid) {
    $path = [];
    $category = core_course_category::get($categoryid);

    while ($category) {
        $path[] = $category->name;
        if ($category->parent == 0) break;
        $category = core_course_category::get($category->parent);
    }

    return implode(' / ', array_reverse($path));
}



    /**
     * Callback allowing to before_http_headers
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $CFG;
        if (during_initial_install() || isset($CFG->upgraderunning)) {
            // Do nothing during installation or upgrade.
            return;
        }
    }

    /**
     * Callback allowing to add to <head> of the page
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */

     public static function before_standard_head_html_generation(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $PAGE;
        $PAGE->requires->css('/local/edzmyenrollcourse/styles.css');
    }

    /**
     * Callback allowing to add contetnt inside the region-main, in the very end
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
   public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
    global $CFG;

    if (during_initial_install() || isset($CFG->upgraderunning)) {
        return;
    }

    }

}