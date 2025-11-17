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
  * Hook callback class for local_panelmenu plugin.
  *
  * @package    local_panelmenu
  * @category   hook
  * @author     Rashid
  * @copyright  2025 Rashid
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

namespace local_panelmenu\hooks;
use core\hook\navigation\primary_extend;
use pix_icon;
use navigation_node;
use core\hook\output\before_standard_head_html_generation;
use core\hook\output\before_footer_html_generation;
use core\hook\output\before_http_headers;
use core\hook\output\after_http_headers;
use moodle_url;
use core_course_category;


class hook_callbacks {

    /**
     * Runs before HTTP headers are sent.
     *
     * @param after_http_headers $hook Hook object.
     */
    public static function after_http_headers(after_http_headers $hook): void {
        global $PAGE, $SCRIPT,$CFG;
        
        //echo $OUTPUT->render_from_template('local_panelmenu/custom_panel', $context);
         $PAGE->requires->js_call_amd('local_panelmenu/paneltoggle', 'init');

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            // Do nothing during installation or upgrade.
            return;
        }
        require_once($CFG->dirroot . '/local/panelmenu/lib.php');
        $html =  \render_custom_panel_menu();
        $hook->add_html($html);
    }


    /**
     * Callback allowing to add to <head> of the page
     *
     * @param \core\hook\output\before_http_headers $hook
     */
     public static function before_http_headers(before_http_headers $hook): void {
       // $PAGE->requires->css('/local/panelmenu/styles.css');
        global $PAGE, $SCRIPT,$CFG;
        $PAGE->requires->css(new moodle_url('/local/panelmenu/styles.css'));
        
    }

}
