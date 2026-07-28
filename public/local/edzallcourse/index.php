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
 * EDZ Catalogue main page.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_edzallcourse\helper\catalogue;
use local_edzallcourse\output\renderable\catalogue_page;

$categoryid = optional_param('category', 0, PARAM_INT);
$q          = optional_param('q', '', PARAM_TEXT);
$sort       = optional_param('sort', '', PARAM_ALPHA);
$page       = optional_param('page', 1, PARAM_INT);

$context = context_system::instance();
require_capability('local/edzallcourse:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edzallcourse/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('viewpage', 'local_edzallcourse'));
$PAGE->set_heading(get_string('viewpage', 'local_edzallcourse'));
$PAGE->navbar->ignore_active();
$PAGE->set_secondary_navigation(false);
$PAGE->add_body_class('local-edzallcourse-page');

echo $OUTPUT->header();

if (!get_config('local_edzallcourse', 'enable')) {
    echo $OUTPUT->notification(get_string('disabledpage', 'local_edzallcourse'), 'info');
    echo $OUTPUT->footer();
    die;
}

$selected = catalogue::effective_category_id($categoryid);

$PAGE->requires->js_call_amd('local_edzallcourse/catalogue', 'init', [[
    'categoryid' => $selected,
    'q'          => $q,
    'sort'       => $sort !== '' ? $sort : catalogue::default_sort(),
    'page'       => max(1, $page),
]]);

$renderable = new catalogue_page($selected, $q, $sort, $page);
$renderer   = $PAGE->get_renderer('local_edzallcourse');
echo $renderer->render_catalogue_page($renderable);

echo $OUTPUT->footer();
