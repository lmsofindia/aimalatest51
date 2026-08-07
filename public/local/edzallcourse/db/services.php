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
 * External services for local_edzallcourse.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// NOTE: functions are named *_v2 deliberately. Moodle's upgrade does NOT update
// the stored `loginrequired` flag on an EXISTING external function, so the public
// (loginrequired=false) change never took effect on the original names. Fresh
// names force a clean re-registration that correctly picks up loginrequired=false.
$functions = [

    'local_edzallcourse_get_view_v2' => [
        'classname'     => 'local_edzallcourse\external\get_view',
        'methodname'    => 'execute',
        'description'   => 'Rebuild and render the catalogue drilldown, grid and pager for a category selection (public).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],

    'local_edzallcourse_search_suggest_v2' => [
        'classname'     => 'local_edzallcourse\external\search_suggest',
        'methodname'    => 'execute',
        'description'   => 'Lightweight typeahead: matching courses and categories for a search term (public).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],
];
