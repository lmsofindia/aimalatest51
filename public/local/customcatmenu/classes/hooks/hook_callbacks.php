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
 * @package    local_customcatmenu
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */

namespace local_customcatmenu\hooks;

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
        global $PAGE,$OUTPUT,$CFG;

        // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
        // return; // stop here, no settings added
        // }

        // Check if the explore menu is enabled.
        if (!get_config('local_customcatmenu', 'enable')) {
            return;
        }
        $categories = core_course_category::get_all();
        $structuredcats = [];

        foreach ($categories as $category) {
        if (!$category->visible) continue;

        $categoryurl = (new moodle_url('/course/index.php', ['categoryid' => $category->id]))->out(false);

        if ($category->parent == 0) {
            $structuredcats[$category->id] = [
                'id' => $category->id,
                'name' => $category->name,
                'url' => $categoryurl,
                'subcats' => []
            ];
        } else {
            if (isset($structuredcats[$category->parent])) {
                $structuredcats[$category->parent]['subcats'][] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'url' => $categoryurl
                ];
            }
        }
    }
        $templatecontext = [
            'categories' => array_values($structuredcats),
            'exploreheading' => get_string('explorecategories', 'local_customcatmenu'),
        ];

        $explorehtml = $OUTPUT->render_from_template('local_customcatmenu/explore_menu', $templatecontext);
        $PAGE->requires->js_init_code("window.exploreMenuHTML = " . json_encode($explorehtml) . ";");
        $PAGE->requires->js_call_amd('local_customcatmenu/customcatmenu', 'init');

            $hook->get_primaryview()->add(
                'Explore',
                new moodle_url('#'), 
                navigation_node::TYPE_CUSTOM,
                2,
                'exploremenu',
                new pix_icon('i/navigationitem', '')
            );
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
        $PAGE->requires->css('/local/customcatmenu/styles.css');
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