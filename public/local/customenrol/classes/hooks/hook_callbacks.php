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
 * @package    local_customenrol
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */

namespace local_customenrol\hooks;

use core\hook\navigation\primary_extend;
use pix_icon;
use navigation_node;
use core\hook\output\before_http_headers;
use core\hook\output\before_standard_head_html_generation;
use moodle_url;
use core_course_category;
use core\output\html;
use context_course;

class hook_callbacks
{

    /**
     * Callback allowing to add primary node
     *
     * @param \core\hook\navigation\primary_extend $hook
     */


    /**
     * Callback allowing to before_http_headers
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void
    {
        global $CFG;
        if (during_initial_install() || isset($CFG->upgraderunning)) {
            // Do nothing during installation or upgrade.
            return;
        }

        global $SCRIPT, $USER, $CFG;

        $enabled = (int)get_config('local_customenrol', 'enabled');
        if (!$enabled) {
            return; // Respect admin toggle.
        }

        if ($SCRIPT !== '/enrol/index.php') {
            return; // Only intercept enrol landing.
        }

        require_once($CFG->libdir . '/enrollib.php');

        $courseid = optional_param('id', 0, PARAM_INT);
        if (!$courseid) {
            return; // Nothing to do.
        }

        // Only redirect if user is NOT enrolled.
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return;
        }

        if (is_enrolled($context, $USER, '', true)) {
            return; // Already enrolled -> keep core flow.
        }

        // Build target URL.
        $url = new moodle_url('/local/customenrol/index.php', ['id' => $courseid]);
        redirect($url);
    }
}
