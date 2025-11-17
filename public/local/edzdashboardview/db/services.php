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
 * External functions and service declaration for EDZ Dashboard View
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    local_edzdashboardview
 * @category   webservice
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$functions = [

    // ✅ User tab webservice
    'local_edzdashboardview_get_user_dashboard_data' => [
        'classname'   => 'local_edzdashboardview_external',
        'methodname'  => 'get_user_dashboard_data',
        'classpath'   => 'local/edzdashboardview/externallib.php',
        'description' => 'Return Active Users & Enrolments data for user tab',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> ''
    ],
    
    // ✅ Site tab webservice
    'local_edzdashboardview_get_site_dashboard_data' => [
        'classname'   => 'local_edzdashboardview_external',
        'methodname'  => 'get_site_dashboard_data',
        'classpath'   => 'local/edzdashboardview/externallib.php',
        'description' => 'Return Site-wide stats for site tab',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> ''
    ],
    // ✅ Course tab webservice
    'local_edzdashboardview_get_course_dashboard_data' => [
        'classname'   => 'local_edzdashboardview_external',
        'methodname'  => 'get_course_dashboard_data',
        'classpath'   => 'local/edzdashboardview/externallib.php',
        'description' => 'Return Course-wide stats for course tab',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> ''
    ],
     // ✅ Reward tab webservice
    'local_edzdashboardview_get_reward_dashboard_data' => [
        'classname'   => 'local_edzdashboardview_external',
        'methodname'  => 'get_reward_dashboard_data',
        'classpath'   => 'local/edzdashboardview/externallib.php',
        'description' => 'Return Reward-wide stats for reward tab',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> ''
    ],
];

$services = [
    'EDZ Dashboard Services' => [
        'functions' => [
            'local_edzdashboardview_get_user_dashboard_data',
            'local_edzdashboardview_get_site_dashboard_data',
            'local_edzdashboardview_get_course_dashboard_data',
            'local_edzdashboardview_get_reward_dashboard_data'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'edz_dashboard'
    ]
];
