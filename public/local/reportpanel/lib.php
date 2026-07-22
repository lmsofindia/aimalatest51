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
 * Library callbacks for local_reportpanel.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a "Reports Hub" link to the flat navigation for any user who can view it.
 *
 * Boost's primary navigation does not render this node; surface it via a custom
 * menu item (Reports Hub|/local/reportpanel/index.php) or theme_edzcorp's sidebar.
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_reportpanel_extend_navigation(global_navigation $navigation) {
    if (!isloggedin() || isguestuser()) {
        return;
    }
    if (!has_capability('local/reportpanel:view', context_system::instance())) {
        return;
    }
    $node = $navigation->add(
        get_string('pluginname', 'local_reportpanel'),
        new moodle_url('/local/reportpanel/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'reportpanel',
        new pix_icon('i/report', '')
    );
    $node->showinflatnavigation = true;
}
