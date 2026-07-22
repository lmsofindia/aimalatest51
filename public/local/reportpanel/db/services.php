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
 * External function definitions for local_reportpanel.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_reportpanel_get_courses' => [
        'classname'    => 'local_reportpanel\external\get_courses',
        'description'  => 'List courses in a category for the report pickers.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/reportpanel:viewall',
    ],
    'local_reportpanel_search_users' => [
        'classname'    => 'local_reportpanel\external\search_users',
        'description'  => 'Search users by name/email for the consolidated report.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'local/reportpanel:viewall',
    ],
];
