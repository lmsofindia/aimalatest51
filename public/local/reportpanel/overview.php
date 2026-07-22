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
 * Site-wide overview dashboard (Domain B) — full-mode only. Replaces the external
 * "Site reports" card.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_reportpanel\local\daterange;
use local_reportpanel\report\overview_report;
use local_reportpanel\helper\format;

require_login(null, false);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reportpanel/overview.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('card_overview', 'local_reportpanel'));
$PAGE->set_heading(get_string('card_overview', 'local_reportpanel'));

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
$download = optional_param('download', '', PARAM_ALPHA);
$customfrom = ($fromstr !== '') ? (int)strtotime($fromstr . ' 00:00') : 0;
$customto = ($tostr !== '') ? (int)strtotime($tostr . ' 00:00') : 0;
$window = daterange::resolve($rangekey, $customfrom, $customto);

$activedays = (int)get_config('local_reportpanel', 'activedays');
if ($activedays <= 0) {
    $activedays = 7;
}

$report = new overview_report();
$data = $report->get_data([
    'from' => $window->from,
    'to' => $window->to,
    'activedays' => $activedays,
]);
$charts = $report->get_charts($data);

$renderchart = function ($chart) use ($OUTPUT) {
    return !empty($chart) ? $OUTPUT->render_chart($chart, false) : '';
};
$regchart = $renderchart($charts['registrations']);
$compchart = $renderchart($charts['completions']);
$timechart = $renderchart($charts['time']);

// KPI cards (formatted numbers; deltas + drill-down where meaningful).
$k = $data['kpis'];
$d = $data['deltas'];
$rangeparams = ['range' => $window->key, 'from' => $fromstr, 'to' => $tostr];
$engurl = (new moodle_url('/local/reportpanel/engagement.php', $rangeparams))->out(false);
$rankurl = (new moodle_url('/local/reportpanel/rankings.php', $rangeparams))->out(false);

$card = function (string $label, string $value, string $icon, ?array $delta = null, ?string $url = null): array {
    $c = ['label' => $label, 'value' => $value, 'icon' => $icon];
    if ($delta !== null) {
        $c = array_merge($c, $delta);
    }
    if ($url !== null) {
        $c['url'] = $url;
    }
    return $c;
};

$kpicards = [
    $card(get_string('ov_totalusers', 'local_reportpanel'), format::number($k['totalusers']), 'fa-users'),
    $card(get_string('ov_activeusers', 'local_reportpanel', $activedays), format::number($k['activeusers']), 'fa-user-check', null, $engurl),
    $card(get_string('ov_newregs', 'local_reportpanel'), format::number($k['newregs']), 'fa-user-plus', $d['newregs']),
    $card(get_string('ov_enrolments', 'local_reportpanel'), format::number($k['enrolments']), 'fa-graduation-cap'),
    $card(get_string('ov_coursecomp', 'local_reportpanel'), format::number($k['coursecomp']), 'fa-circle-check', $d['coursecomp'], $rankurl),
    $card(get_string('ov_activitycomp', 'local_reportpanel'), format::number($k['activitycomp']), 'fa-list-check', $d['activitycomp'], $rankurl),
    $card(get_string('ov_certs', 'local_reportpanel'), format::number($k['certs']), 'fa-certificate', $d['certs']),
    $card(get_string('ov_timeontask', 'local_reportpanel'), $k['timeontask'], 'fa-clock', $d['timeontask'], $engurl),
];

// Registration + health tables.
$reg = $data['registration'];
$regrows = [
    ['label' => get_string('ov_reg_confirmed', 'local_reportpanel'), 'value' => format::number($reg['confirmed'])],
    ['label' => get_string('ov_reg_unconfirmed', 'local_reportpanel'), 'value' => format::number($reg['unconfirmed'])],
    ['label' => get_string('ov_reg_suspended', 'local_reportpanel'), 'value' => format::number($reg['suspended'])],
    ['label' => get_string('ov_reg_deleted', 'local_reportpanel'), 'value' => format::number($reg['deleted'])],
];
$health = $data['health'];
$healthrows = [
    ['label' => get_string('ov_health_nocourses', 'local_reportpanel'), 'value' => format::number($health['nocourses'])],
    ['label' => get_string('ov_health_multicourse', 'local_reportpanel'), 'value' => format::number($health['multicourse'])],
    ['label' => get_string('ov_health_notaccessed', 'local_reportpanel', $activedays), 'value' => format::number($health['notaccessed'])],
];

// Top courses strip (with drill-down to the course consolidated report).
$topcourses = [];
foreach ($data['topcourses'] as $tc) {
    $topcourses[] = [
        'name' => $tc->name,
        'count' => format::number($tc->count),
        'url' => (new moodle_url('/local/reportpanel/courseconsolidated.php', ['courseid' => $tc->id]))->out(false),
    ];
}

if ($download === 'csv') {
    require_once($CFG->libdir . '/csvlib.class.php');
    $csv = new csv_export_writer();
    $csv->set_filename('overview-' . date('Ymd'));
    $csv->add_data([get_string('card_overview', 'local_reportpanel'), $window->label]);
    $csv->add_data([]);
    foreach ($kpicards as $c) {
        $csv->add_data([$c['label'], $c['value']]);
    }
    $csv->add_data([]);
    $csv->add_data([get_string('ov_registration_title', 'local_reportpanel')]);
    foreach ($regrows as $r) {
        $csv->add_data([$r['label'], $r['value']]);
    }
    $csv->add_data([]);
    $csv->add_data([get_string('ov_health_title', 'local_reportpanel')]);
    foreach ($healthrows as $r) {
        $csv->add_data([$r['label'], $r['value']]);
    }
    $csv->add_data([]);
    $csv->add_data([get_string('ov_topcourses_title', 'local_reportpanel')]);
    foreach ($topcourses as $tc) {
        $csv->add_data([$tc['name'], $tc['count']]);
    }
    $csv->download_file();
    exit;
}

$templatecontext = [
    'actionurl'   => (new moodle_url('/local/reportpanel/overview.php'))->out(false),
    'rangemenu'   => daterange::menu($window->key),
    'iscustom'    => ($window->key === 'custom'),
    'customfrom'  => $fromstr,
    'customto'    => $tostr,
    'rangelabel'  => $window->label,
    'kpicards'    => $kpicards,
    'regchart'    => $regchart,
    'hasregchart' => ($regchart !== ''),
    'compchart'   => $compchart,
    'hascompchart' => ($compchart !== ''),
    'timechart'   => $timechart,
    'hastimechart' => ($timechart !== ''),
    'timeavailable' => $data['timeavailable'],
    'regrows'     => $regrows,
    'healthrows'  => $healthrows,
    'topcourses'  => $topcourses,
    'hastopcourses' => !empty($topcourses),
    'rankingsurl' => (new moodle_url('/local/reportpanel/rankings.php'))->out(false),
    'asof'        => get_string('asof', 'local_reportpanel', userdate(time())),
    'csvurl'      => (new moodle_url('/local/reportpanel/overview.php', [
        'range' => $window->key, 'from' => $fromstr, 'to' => $tostr, 'download' => 'csv']))->out(false),
    'backurl'     => (new moodle_url('/local/reportpanel/index.php'))->out(false),
];

$PAGE->requires->js_call_amd('local_reportpanel/filters', 'init');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reportpanel/overview', $templatecontext);
echo $OUTPUT->footer();
