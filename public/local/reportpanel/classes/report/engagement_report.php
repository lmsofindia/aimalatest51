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

use local_reportpanel\helper\timetracking;
use local_reportpanel\helper\charts;
use local_reportpanel\helper\format;

/**
 * Site-wide time & engagement report (Domain A). Sources all time-on-task data from
 * local_trackmytime via {@see timetracking}. Full-mode only (all-user analytics).
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class engagement_report extends report_base {

    public function get_key(): string {
        return 'engagement';
    }

    public function get_title(): string {
        return get_string('card_engagement', 'local_reportpanel');
    }

    public function get_columns(): array {
        return [
            'course' => get_string('col_course', 'local_reportpanel'),
            'time'   => get_string('col_timeontask', 'local_reportpanel'),
        ];
    }

    /**
     * Build the engagement payload.
     *
     * @param array $filters {from:int, to:int, activedays:int}
     * @return array
     */
    public function get_data(array $filters): array {
        global $DB;

        $from = (int)($filters['from'] ?? (time() - 30 * DAYSECS));
        $to = (int)($filters['to'] ?? (time() + 1));
        $activedays = (int)($filters['activedays'] ?? 7);

        $available = timetracking::available();

        // KPIs.
        $totalsecs = timetracking::total_time($from, $to);
        $activecount = timetracking::active_count($activedays);
        $totalusers = self::total_learner_count();
        $inactive = max(0, $totalusers - $activecount);
        $avgactive = $activecount > 0 ? intdiv($totalsecs, $activecount) : 0;

        // Total-time delta vs the same-length previous window.
        $span = max(1, $to - $from);
        $prevsecs = timetracking::total_time($from - $span, $from);
        $timedelta = format::delta($totalsecs, $prevsecs);

        // Trend (daily) — convert seconds to minutes for a readable axis.
        $series = timetracking::time_series(['from' => $from, 'to' => $to]);
        $trendlabels = [];
        $trendvalues = [];
        foreach ($series as $ymd => $secs) {
            $ts = strtotime($ymd . ' 00:00');
            $trendlabels[] = userdate($ts, get_string('strftimedateshort', 'langconfig'));
            $trendvalues[] = (int)round($secs / 60);
        }

        // Top courses by time (rendered as CSS meter bars).
        $ranked = timetracking::course_rankings($from, $to, 8);
        $courserows = [];
        if (!empty($ranked)) {
            $ids = array_map(fn($r) => $r->courseid, $ranked);
            [$insql, $inparams] = $DB->get_in_or_equal($ids);
            $names = $DB->get_records_select_menu('course', "id {$insql}", $inparams, '', 'id, fullname');
            $maxsecs = 0;
            foreach ($ranked as $r) {
                $maxsecs = max($maxsecs, (int)$r->seconds);
            }
            foreach ($ranked as $r) {
                $name = isset($names[$r->courseid])
                    ? format_string($names[$r->courseid])
                    : get_string('deletedcourse', 'local_reportpanel');
                $courserows[] = [
                    'course' => $name,
                    'time'   => timetracking::format_seconds($r->seconds),
                    'seconds' => $r->seconds,
                    'barpct' => $maxsecs > 0 ? (int)round($r->seconds / $maxsecs * 100) : 0,
                ];
            }
        }

        // Usage heatmap (7 x 24 seconds) → display buckets.
        $grid = timetracking::usage_heatmap($from, $to);
        $heatmap = self::shape_heatmap($grid);

        return [
            'available'   => $available,
            'range'       => ['from' => $from, 'to' => $to],
            'activedays'  => $activedays,
            'kpis' => [
                'totaltime'   => timetracking::format_seconds($totalsecs),
                'activecount' => format::number($activecount),
                'inactive'    => format::number($inactive),
                'avgactive'   => timetracking::format_seconds($avgactive),
            ],
            'timedelta' => $timedelta,
            'trend' => ['labels' => $trendlabels, 'values' => $trendvalues],
            'courses' => [
                'rows'   => $courserows,
                'hasrows' => !empty($courserows),
            ],
            'heatmap' => $heatmap,
        ];
    }

    public function get_charts(array $data): array {
        // Only the trend is a Chart.js line; top-courses render as CSS meter bars.
        return [
            'trend' => charts::time_line(
                $data['trend']['labels'],
                $data['trend']['values'],
                get_string('chart_minutes', 'local_reportpanel')
            ),
        ];
    }

    /**
     * Count of "learners" for the inactive baseline: real, non-deleted, confirmed,
     * unsuspended accounts (excludes guest + the primary admin id 2 and site id 1).
     *
     * @return int
     */
    protected static function total_learner_count(): int {
        global $DB;
        return (int)$DB->count_records_select('user',
            'deleted = 0 AND suspended = 0 AND confirmed = 1 AND id > 2');
    }

    /**
     * Turn a 7 x 24 seconds grid into a template-friendly structure with intensity
     * levels 0–4 (relative to the busiest cell) and per-cell tooltips.
     *
     * @param array $grid $grid[$dow][$hour] = seconds
     * @return array {days: [{name, cells:[{level,tip}]}], hourlabels:[], hasdata:bool}
     */
    protected static function shape_heatmap(array $grid): array {
        $max = 0;
        foreach ($grid as $hours) {
            foreach ($hours as $secs) {
                $max = max($max, (int)$secs);
            }
        }
        // date('w'): 0=Sun..6=Sat. Present Mon-first for a work-week feel.
        $order = [1, 2, 3, 4, 5, 6, 0];
        $daynames = [
            0 => userdate(strtotime('Sunday'), '%a'),
            1 => userdate(strtotime('Monday'), '%a'),
            2 => userdate(strtotime('Tuesday'), '%a'),
            3 => userdate(strtotime('Wednesday'), '%a'),
            4 => userdate(strtotime('Thursday'), '%a'),
            5 => userdate(strtotime('Friday'), '%a'),
            6 => userdate(strtotime('Saturday'), '%a'),
        ];

        $days = [];
        foreach ($order as $dow) {
            $cells = [];
            for ($h = 0; $h < 24; $h++) {
                $secs = (int)($grid[$dow][$h] ?? 0);
                $level = 0;
                if ($max > 0 && $secs > 0) {
                    $ratio = $secs / $max;
                    $level = $ratio >= 0.75 ? 4 : ($ratio >= 0.5 ? 3 : ($ratio >= 0.25 ? 2 : 1));
                }
                $cells[] = [
                    'level' => $level,
                    'tip'   => $daynames[$dow] . ' ' . sprintf('%02d:00', $h) . ' — ' .
                               timetracking::format_seconds($secs),
                ];
            }
            $days[] = ['name' => $daynames[$dow], 'cells' => $cells];
        }

        $hourlabels = [];
        for ($h = 0; $h < 24; $h += 3) {
            $hourlabels[] = sprintf('%02d', $h);
        }

        return ['days' => $days, 'hourlabels' => $hourlabels, 'hasdata' => ($max > 0)];
    }
}
