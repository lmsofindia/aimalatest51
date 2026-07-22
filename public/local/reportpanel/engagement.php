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
 * Site-wide time & engagement report (Domain A) — full-mode only.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_reportpanel\local\daterange;
use local_reportpanel\report\engagement_report;

require_login(null, false);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reportpanel/engagement.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('card_engagement', 'local_reportpanel'));
$PAGE->set_heading(get_string('card_engagement', 'local_reportpanel'));

// This report is all-user site analytics: full mode (viewall) only.
require_capability('local/reportpanel:view', $context);
if (!has_capability('local/reportpanel:viewall', $context)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('engagement_fullmodeonly', 'local_reportpanel'), 'info');
    $perfurl = new moodle_url('/local/trackmytime/pages/performance.php');
    echo html_writer::div(
        html_writer::link($perfurl, get_string('engagement_gotomine', 'local_reportpanel'),
            ['class' => 'btn btn-primary']),
        'mt-2'
    );
    echo $OUTPUT->footer();
    exit;
}

// Filters.
$rangekey = optional_param('range', daterange::DEFAULT_KEY, PARAM_ALPHANUMEXT);
$fromstr = optional_param('from', '', PARAM_RAW_TRIMMED);
$tostr = optional_param('to', '', PARAM_RAW_TRIMMED);
$download = optional_param('download', '', PARAM_ALPHA);

$customfrom = ($fromstr !== '') ? (int)strtotime($fromstr . ' 00:00') : 0;
$customto = ($tostr !== '') ? (int)strtotime($tostr . ' 00:00') : 0;
$window = daterange::resolve($rangekey, $customfrom, $customto);

$activedays = (int)get_config('local_reportpanel', 'activedays');
if ($activedays <= 0) {
    $activedays = 7;
}

$report = new engagement_report();
$data = $report->get_data([
    'from' => $window->from,
    'to' => $window->to,
    'activedays' => $activedays,
]);

// CSV export of the top-courses-by-time table.
if ($download === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');
    $csv = new csv_export_writer();
    $csv->set_filename('engagement-courses-' . date('Ymd'));
    $csv->add_data([
        get_string('col_course', 'local_reportpanel'),
        get_string('col_timeontask', 'local_reportpanel'),
        get_string('col_seconds', 'local_reportpanel'),
    ]);
    foreach ($data['courses']['rows'] as $row) {
        $csv->add_data([$row['course'], $row['time'], $row['seconds']]);
    }
    $csv->download_file();
    exit;
}

// Render the trend line server-side (Chart.js via core/chartjs). Top courses render
// as CSS meter bars in the template.
$charts = $report->get_charts($data);
$trendhtml = !empty($charts['trend']) ? $OUTPUT->render_chart($charts['trend'], false) : '';

// KPI cards (total-time carries a period-over-period delta).
$kpicards = [
    array_merge(
        ['label' => get_string('kpi_totaltime', 'local_reportpanel'), 'value' => $data['kpis']['totaltime'], 'icon' => 'fa-clock'],
        $data['timedelta']
    ),
    ['label' => get_string('kpi_activeusers', 'local_reportpanel', $activedays), 'value' => $data['kpis']['activecount'], 'icon' => 'fa-user-check'],
    ['label' => get_string('kpi_inactiveusers', 'local_reportpanel'), 'value' => $data['kpis']['inactive'], 'icon' => 'fa-user-clock'],
    ['label' => get_string('kpi_avgactive', 'local_reportpanel'), 'value' => $data['kpis']['avgactive'], 'icon' => 'fa-gauge'],
];

$csvurl = new moodle_url('/local/reportpanel/engagement.php', [
    'range' => $window->key, 'from' => $fromstr, 'to' => $tostr, 'download' => 'csv',
]);

$templatecontext = [
    'available'      => $data['available'],
    'unavailablemsg' => get_string('engagement_nodata', 'local_reportpanel'),
    'actionurl'      => (new moodle_url('/local/reportpanel/engagement.php'))->out(false),
    'rangemenu'      => daterange::menu($window->key),
    'iscustom'       => ($window->key === 'custom'),
    'customfrom'     => $fromstr,
    'customto'       => $tostr,
    'rangelabel'     => $window->label,
    'asof'           => get_string('asof', 'local_reportpanel', userdate(time())),
    'kpicards'       => $kpicards,
    'trendchart'     => $trendhtml,
    'hastrend'       => ($trendhtml !== ''),
    'hascourse'      => $data['courses']['hasrows'],
    'courserows'     => $data['courses']['rows'],
    'heatmap'        => $data['heatmap'],
    'csvurl'         => $csvurl->out(false),
    'backurl'        => (new moodle_url('/local/reportpanel/index.php'))->out(false),
];

$PAGE->requires->js_call_amd('local_reportpanel/filters', 'init');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reportpanel/engagement', $templatecontext);
echo $OUTPUT->footer();
