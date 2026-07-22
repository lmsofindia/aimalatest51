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
 * Ranking / "top N" reports (Domain D) — full-mode only.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_reportpanel\local\daterange;
use local_reportpanel\report\rankings_report;

require_login(null, false);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reportpanel/rankings.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('card_rankings', 'local_reportpanel'));
$PAGE->set_heading(get_string('card_rankings', 'local_reportpanel'));

require_capability('local/reportpanel:view', $context);
if (!has_capability('local/reportpanel:viewall', $context)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('managersonly', 'local_reportpanel'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Filters.
$rangekey = optional_param('range', daterange::DEFAULT_KEY, PARAM_ALPHANUMEXT);
$fromstr = optional_param('from', '', PARAM_RAW_TRIMMED);
$tostr = optional_param('to', '', PARAM_RAW_TRIMMED);
$top = optional_param('top', 10, PARAM_INT);
$categoryid = optional_param('category', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

if (!in_array($top, [10, 20, 50], true)) {
    $top = 10;
}
$customfrom = ($fromstr !== '') ? (int)strtotime($fromstr . ' 00:00') : 0;
$customto = ($tostr !== '') ? (int)strtotime($tostr . ' 00:00') : 0;
$window = daterange::resolve($rangekey, $customfrom, $customto);

$report = new rankings_report();
$data = $report->get_data([
    'from' => $window->from,
    'to' => $window->to,
    'limit' => $top,
    'categoryid' => $categoryid,
]);

if ($download === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');
    $csv = new csv_export_writer();
    $csv->set_filename('rankings-' . date('Ymd'));
    foreach ($data['blocks'] as $block) {
        $csv->add_data([$block['title']]);
        $csv->add_data(['#', get_string('col_name', 'local_reportpanel'), $block['colunit']]);
        foreach ($block['rows'] as $r) {
            $csv->add_data([$r['rank'], $r['label'], $r['value']]);
        }
        $csv->add_data([]);
    }
    $csv->download_file();
    exit;
}

// Ordered block list for the template (CSS meter bars, no Chart.js).
$barclasses = [
    'enrol' => 'rp-bars--blue',
    'completion' => 'rp-bars--green',
    'time' => 'rp-bars--purple',
    'learners' => 'rp-bars--teal',
];
$blocks = [];
foreach (['enrol', 'completion', 'time', 'learners'] as $key) {
    $block = $data['blocks'][$key];
    $blocks[] = [
        'title' => $block['title'],
        'colunit' => $block['colunit'],
        'rows' => $block['rows'],
        'hasrows' => $block['hasrows'],
        'barclass' => $barclasses[$key],
    ];
}

// Category menu.
$catmenu = [['value' => 0, 'label' => get_string('rank_allcategories', 'local_reportpanel'), 'selected' => ($categoryid === 0)]];
foreach (core_course_category::make_categories_list() as $cid => $cname) {
    $catmenu[] = ['value' => $cid, 'label' => $cname, 'selected' => ($categoryid === (int)$cid)];
}

// Top-N menu.
$topmenu = [];
foreach ([10, 20, 50] as $n) {
    $topmenu[] = ['value' => $n, 'label' => $n, 'selected' => ($top === $n)];
}

$templatecontext = [
    'actionurl'  => (new moodle_url('/local/reportpanel/rankings.php'))->out(false),
    'rangemenu'  => daterange::menu($window->key),
    'iscustom'   => ($window->key === 'custom'),
    'customfrom' => $fromstr,
    'customto'   => $tostr,
    'rangelabel' => $window->label,
    'catmenu'    => $catmenu,
    'topmenu'    => $topmenu,
    'blocks'     => $blocks,
    'asof'       => get_string('asof', 'local_reportpanel', userdate(time())),
    'csvurl'     => (new moodle_url('/local/reportpanel/rankings.php', [
        'range' => $window->key, 'from' => $fromstr, 'to' => $tostr,
        'category' => $categoryid, 'top' => $top, 'download' => 'csv']))->out(false),
    'backurl'    => (new moodle_url('/local/reportpanel/index.php'))->out(false),
];

$PAGE->requires->js_call_amd('local_reportpanel/filters', 'init');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reportpanel/rankings', $templatecontext);
echo $OUTPUT->footer();
