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
 * Callback implementations for local_edzdashboardview
 *
 * @package    local_edzdashboardview
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



function local_edzdashboardview_extend_navigation(global_navigation $nav) {
    if (has_capability('local/edzdashboardview:view', \context_system::instance())) {
        $node = $nav->add(
            get_string('pluginname', 'local_edzdashboardview'),
            new moodle_url('/local/edzdashboardview/index.php')
        );
    }
}
