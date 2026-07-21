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
 * Index page for local_edzallcourse
 *
 * @package   local_edzallcourse
**/
require('../../config.php');
global $CFG, $PAGE, $OUTPUT;

require_capability('local/edzallcourse:view', \context_system::instance());

$enabled = get_config('local_edzallcourse', 'enable');
if (!$enabled) {
    print_error('disabled', 'admin');
}
$PAGE->set_url(new moodle_url('/local/edzallcourse/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('viewpage', 'local_edzallcourse'));
// $PAGE->set_heading(get_string('viewpage', 'local_edzallcourse'));

$renderer = $PAGE->get_renderer('local_edzallcourse');
$service  = new \local_edzallcourse\local\service();

$fieldsmeta = \local_edzallcourse\local\service::enabled_fields();
$options    = \local_edzallcourse\local\service::options_for_enabled_fields();
$q = optional_param('q', '', PARAM_TEXT);


$limit = 10;
$filtersjson = optional_param('filters', '{}', PARAM_RAW);
$filters = json_decode($filtersjson, true) ?? [];
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$categoryids = []; 

$data = [
    'filtersenabled' => !empty($fieldsmeta),
    'filters' => [],
];


foreach ($fieldsmeta as $shortname => $meta) {
    $opts = $options[$shortname] ?? [];

    if ($shortname === 'course_category' && $categoryid > 0) {
        foreach ($opts as &$opt) {
            if ((int)$opt['value'] === $categoryid) {
                $opt['checked'] = true;
            }
        }
        unset($opt); 
    }

    $data['filters'][] = [
        'shortname' => $shortname,
        'name' => $meta['label'],
        'options' => $opts,
    ];
}
$PAGE->navbar->ignore_active();
$PAGE->set_secondary_navigation(false);
echo $OUTPUT->header();
echo $renderer->render_index_page($data);
echo $OUTPUT->footer();



