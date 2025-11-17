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

namespace local_edzdashboardview\task;

/**
 * Class log_usage_task
 *
 * @package    local_edzdashboardview
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_usage\task;

class log_usage_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('log_usage_task', 'local_edzdashboardview');
    }

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/edzdashboardview/locallib.php');
        log_system_usage();
    }
}

