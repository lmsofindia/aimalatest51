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
 * Redirect the bare /course/ index landing to the course catalogue.
 *
 * @package    theme_edzcorp
 * @copyright  2026 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_edzcorp\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * after_config hook: send /course/ (and /course/index.php with no params) to
 * the local_coursecatalogue front page.
 */
class course_index_redirect
{

    /**
     * @param \core\hook\after_config $hook
     * @return void
     */
    public static function redirect(\core\hook\after_config $hook): void
    {
        global $CFG;

        // Skip non-interactive / bootstrap contexts.
        if (during_initial_install()) {
            return;
        }
        if ((defined('CLI_SCRIPT') && CLI_SCRIPT)
            || (defined('AJAX_SCRIPT') && AJAX_SCRIPT)
            || (defined('WS_SERVER') && WS_SERVER)
        ) {
            return;
        }
        if (!empty($CFG->upgraderunning)) {
            return;
        }

        // Only the bare course-index landing: /course/ or /course/index.php with
        // NO query string. Category browsing (?categoryid=) and management pages
        // are deliberately left alone.
        $requesturi = $_SERVER['REQUEST_URI'] ?? '';
        $path  = (string) parse_url($requesturi, PHP_URL_PATH);
        $query = (string) parse_url($requesturi, PHP_URL_QUERY);

        if ($query !== '') {
            return;
        }
        if (!preg_match('#/course/(index\.php)?$#', $path)) {
            return;
        }

        // Only redirect when the catalogue plugin is actually installed.
        if (\core_component::get_plugin_directory('local', 'coursecatalogue') === null) {
            return;
        }

        redirect(new \moodle_url('/local/edzallcourse/')); // redirect to edzallcourse 
    }
}
