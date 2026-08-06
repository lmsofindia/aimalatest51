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

$functions = [

    'local_edzallcourse_get_view' => [
        'classname'     => 'local_edzallcourse\external\get_view',
        'methodname'    => 'execute',
        'description'   => 'Rebuild and render the catalogue drilldown, grid and pager for a category selection.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
        'capabilities'  => 'local/edzallcourse:view',
    ],

    'local_edzallcourse_search_suggest' => [
        'classname'     => 'local_edzallcourse\external\search_suggest',
        'methodname'    => 'execute',
        'description'   => 'Lightweight typeahead: return matching courses and categories for a search term.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
        'capabilities'  => 'local/edzallcourse:view',
    ],
];
