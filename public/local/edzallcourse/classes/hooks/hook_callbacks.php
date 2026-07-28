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
 * Hook callbacks for local_edzallcourse.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzallcourse\hooks;

use core\hook\output\before_http_headers;

/**
 * Callbacks reacting to core hooks.
 */
class hook_callbacks {

    /**
     * Redirect the core course index to the EDZ Catalogue when enabled.
     *
     * @param before_http_headers $hook
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $CFG, $SCRIPT;

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            return;
        }

        if (!get_config('local_edzallcourse', 'enable')) {
            return;
        }
        if (!get_config('local_edzallcourse', 'takeovercourseindex')) {
            return;
        }

        if (($SCRIPT ?? '') !== '/course/index.php') {
            return;
        }

        $params = [];
        $categoryid = optional_param('categoryid', 0, PARAM_INT);
        if ($categoryid > 0) {
            $params['category'] = $categoryid;
        }

        redirect(new \moodle_url('/local/edzallcourse/index.php', $params));
    }
}
