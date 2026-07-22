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

namespace local_reportpanel\report;

use local_reportpanel\helper\analytics;
use local_reportpanel\helper\timetracking;
use local_reportpanel\helper\charts;
use local_reportpanel\helper\format;

/**
 * Site-wide overview dashboard (Domain B) — the plugin's own replacement for the
 * external "Site reports" link. Composes the shared analytics + trackmytime data into
 * period-aware KPIs, trend lines and health tables. Full-mode only.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview_report extends report_base {

    public function get_key(): string {
        return 'overview';
    }

    public function get_title(): string {
        return get_string('card_overview', 'local_reportpanel');
    }

    public function get_columns(): array {
        return [
            'label' => get_string('col_metric', 'local_reportpanel'),
            'value' => get_string('col_value', 'local_reportpanel'),
        ];
    }

    /**
     * Build the overview payload.
     *
     * @param array $filters {from:int, to:int, activedays:int}
     * @return array
     */
    public function get_data(array $filters): array {
        $from = (int)($filters['from'] ?? (time() - 30 * DAYSECS));
        $to = (int)($filters['to'] ?? (time() + 1));
        $activedays = (int)($filters['activedays'] ?? 7);

        // KPIs (raw values; formatting happens in the page).
        $timesecs = timetracking::total_time($from, $to);
        $kpis = [
            'totalusers'    => analytics::total_users(),
            'activeusers'   => timetracking::active_count($activedays),
            'newregs'       => analytics::new_registrations($from, $to),
            'enrolments'    => analytics::total_enrolments(),
            'coursecomp'    => analytics::course_completions($from, $to),
            'activitycomp'  => analytics::activity_completions($from, $to),
            'certs'         => analytics::certificates_issued($from, $to),
            'timeontasksecs' => $timesecs,
            'timeontask'    => timetracking::format_seconds($timesecs),
        ];

        // Period-over-period deltas for the range-based metrics (same-length prior window).
        $span = max(1, $to - $from);
        $pfrom = $from - $span;
        $pto = $from;
        $deltas = [
            'newregs'      => format::delta($kpis['newregs'], analytics::new_registrations($pfrom, $pto)),
            'coursecomp'   => format::delta($kpis['coursecomp'], analytics::course_completions($pfrom, $pto)),
            'activitycomp' => format::delta($kpis['activitycomp'], analytics::activity_completions($pfrom, $pto)),
            'certs'        => format::delta($kpis['certs'], analytics::certificates_issued($pfrom, $pto)),
            'timeontask'   => format::delta($timesecs, timetracking::total_time($pfrom, $pto)),
        ];

        // Trends (daily) over the window.
        $regseries = analytics::day_count_series('user', 'timecreated', $from, $to,
            ' AND deleted = 0 AND id > 2');
        $compseries = analytics::day_count_series('course_completions', 'timecompleted', $from, $to,
            ' AND timecompleted IS NOT NULL');
        $timeseries = timetracking::time_series(['from' => $from, 'to' => $to]);

        return [
            'range'      => ['from' => $from, 'to' => $to],
            'activedays' => $activedays,
            'timeavailable' => timetracking::available(),
            'kpis'       => $kpis,
            'deltas'     => $deltas,
            'trends'     => [
                'registrations' => self::labelled($regseries, false),
                'completions'   => self::labelled($compseries, false),
                'time'          => self::labelled($timeseries, true),
            ],
            'registration' => analytics::registration_breakdown(),
            'health'       => analytics::enrolment_health($activedays),
            'topcourses'   => analytics::top_courses_by_completion($from, $to, 0, 5),
        ];
    }

    /**
     * Charts: three trend lines.
     *
     * @param array $data
     * @return array
     */
    public function get_charts(array $data): array {
        return [
            'registrations' => charts::time_line(
                $data['trends']['registrations']['labels'],
                $data['trends']['registrations']['values'],
                get_string('ov_newregs', 'local_reportpanel')),
            'completions' => charts::time_line(
                $data['trends']['completions']['labels'],
                $data['trends']['completions']['values'],
                get_string('ov_completions', 'local_reportpanel'),
                charts::COLOR_GREEN),
            'time' => charts::time_line(
                $data['trends']['time']['labels'],
                $data['trends']['time']['values'],
                get_string('chart_minutes', 'local_reportpanel'),
                charts::COLOR_PURPLE),
        ];
    }

    /**
     * Convert a ['YYYY-MM-DD' => value] series into {labels, values} with short date
     * labels. When $istime, values are seconds → converted to minutes for the axis.
     *
     * @param array $series
     * @param bool $istime
     * @return array {labels, values}
     */
    protected static function labelled(array $series, bool $istime): array {
        $labels = [];
        $values = [];
        foreach ($series as $ymd => $val) {
            $ts = strtotime($ymd . ' 00:00');
            $labels[] = userdate($ts, get_string('strftimedateshort', 'langconfig'));
            $values[] = $istime ? (int)round($val / 60) : (int)$val;
        }
        return ['labels' => $labels, 'values' => $values];
    }
}
