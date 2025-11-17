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
 * @package    local_customfotmenu
 * @copyright  2025 customfotmenu  - https://edzlms.com/
 * @author    ThemesEDzsaas - Developer Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_customfotmenu\hooks;
use core\hook\navigation\primary_extend;
use pix_icon;
use navigation_node;
use core\hook\output\before_http_headers;
use core\hook\output\before_standard_head_html_generation;
use moodle_url;
use core_course_category;
use core_useragent;
class hook_callbacks {




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
        $PAGE->requires->css('/local/customfotmenu/styles.css');
    }

    /**
     * Callback allowing to add contetnt inside the region-main, in the very end
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
   public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
    global $CFG,$OUTPUT,$USER,$CFG;

    // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
    // return; // stop here, no settings added
    // }
    
    if (during_initial_install() || isset($CFG->upgraderunning)) {
        return;
    }
    $bgcolor   = get_config('local_customfotmenu', 'bgcolor');
    $textcolor = get_config('local_customfotmenu', 'textcolor');
 
    $loggedin = isloggedin() && !isguestuser();

    if($loggedin){
            $templatecontext = [
            'menu' => [
                [
                    'url' => $loggedin ? new moodle_url('/login/logout.php') : new moodle_url('/login/index.php'),
                    'icon' => $loggedin ? 'fa-sign-out' : 'fa-sign-in',
                    'text' => $loggedin ? get_string('logout', 'local_customfotmenu') : get_string('login', 'local_customfotmenu')
                ],
                [
                    'url' => new moodle_url('/course/index.php'),
                    'icon' => 'fa-book',
                    'text' => get_string('mycourses', 'local_customfotmenu')
                ],
                [
                    'url' => new moodle_url('/my/'),
                    'icon' => 'fa-home',
                    'text' => get_string('dashboard', 'local_customfotmenu')
                ],
                [
                    'url' => new moodle_url('/user/profile.php', ['id' => $USER->id]),
                    'icon' => 'fa-user',
                    'text' => get_string('profile', 'local_customfotmenu')
                ],
                [
                    'url' => new moodle_url('/grade/report/overview/index.php'),
                    'icon' => 'fa-graduation-cap',
                    'text' => get_string('grades', 'local_customfotmenu')
                ],
                
            ],
            'bgcolor'=> $bgcolor,
            'textcolor'=> $textcolor,
        ];
    }
    else{
            $templatecontext = [
            'menu' => [
                [
                    'url' => $loggedin ? new moodle_url('/login/logout.php') : new moodle_url('/login/index.php'),
                    'icon' => $loggedin ? 'fa-sign-out' : 'fa-sign-in',
                    'text' => $loggedin ? get_string('logout', 'local_customfotmenu') : get_string('login', 'local_customfotmenu')
                ]
                ],
            'bgcolor'=> $bgcolor,
            'textcolor'=> $textcolor,
        ];
    }
    // print_object($templatecontext);die();
    $html= $OUTPUT->render_from_template('local_customfotmenu/footerbar', $templatecontext);
    $hook->add_html($html);

   

    }

}