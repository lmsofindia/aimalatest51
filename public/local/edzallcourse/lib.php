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
 * Library functions for local_edzallcourse.
 *
 * Intentionally minimal: only the navigation hook lives here. All business
 * logic is in namespaced helper classes under classes/helper/.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a "Course Catalogue" link to the flat navigation.
 *
 * @param global_navigation $nav
 */
function local_edzallcourse_extend_navigation(global_navigation $nav): void {
    if (!get_config('local_edzallcourse', 'enable')) {
        return;
    }
    if (!has_capability('local/edzallcourse:view', context_system::instance())) {
        return;
    }

    $node = $nav->add(
        get_string('viewpage', 'local_edzallcourse'),
        new moodle_url('/local/edzallcourse/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_edzallcourse',
        new pix_icon('i/course', '')
    );
    $node->showinflatnavigation = true;
}
