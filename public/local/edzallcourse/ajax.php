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
 * Main ajax for local_edzallcourse plugin.
 *
 * @package    local_edzallcourse
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */


define('AJAX_SCRIPT', true);

require('../../config.php');
require_once($CFG->libdir . '/externallib.php');

require_sesskey();
require_capability('local/edzallcourse:view', context_system::instance());

global $PAGE, $OUTPUT;
$PAGE->set_context(context_system::instance());

$filtersjson = optional_param('filters', '{}', PARAM_RAW);


$filters = json_decode($filtersjson, true) ?? [];


$parentcatid = $filters['course_category'] ?? 0;


$cattreePathsinfo = \local_edzallcourse\local\service::get_parent_category_tree_paths($parentcatid);


$templatehtml = $OUTPUT->render_from_template('local_edzallcourse/catfilterinfo', [
    'parent'        => $cattreePathsinfo['parent'] ?? null,
    'subcategories' => $cattreePathsinfo['paths'] ?? [],
]);


$response = [
    'template'   => $templatehtml,
    'hasmore'    => false,
    'noresults'  => empty($cattreePathsinfo['paths']),
    'catid'      => $parentcatid
];

echo json_encode($response);
die;
