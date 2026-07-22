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
 * Ranking / "top N" reports (Domain D). Four leaderboards over a date range and an
 * optional category: courses by enrolment, courses by completions, courses by time on
 * task, and the most-active learners. Full-mode only.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rankings_report extends report_base {

    public function get_key(): string {
        return 'rankings';
    }

    public function get_title(): string {
        return get_string('card_rankings', 'local_reportpanel');
    }

    public function get_columns(): array {
        return [
            'label' => get_string('col_name', 'local_reportpanel'),
            'value' => get_string('col_value', 'local_reportpanel'),
        ];
    }

    /**
     * Build all four ranking blocks.
     *
     * @param array $filters {from:int, to:int, limit:int, categoryid:int}
     * @return array
     */
    public function get_data(array $filters): array {
        $from = (int)($filters['from'] ?? (time() - 30 * DAYSECS));
        $to = (int)($filters['to'] ?? (time() + 1));
        $limit = (int)($filters['limit'] ?? 10);
        $categoryid = (int)($filters['categoryid'] ?? 0);

        // 1) Courses by enrolment (all-time snapshot).
        $enrol = analytics::top_courses_by_enrolment($categoryid, $limit);

        // 2) Courses by completions (in range).
        $completion = analytics::top_courses_by_completion($from, $to, $categoryid, $limit);

        // 3) Courses by time on task (in range), category-filtered post-hoc.
        $time = $this->courses_by_time($from, $to, $categoryid, $limit);

        // 4) Most-active learners by completions (in range).
        $learners = analytics::top_learners_by_completion($from, $to, $limit);

        return [
            'blocks' => [
                'enrol' => self::block(
                    get_string('rank_enrol_title', 'local_reportpanel'),
                    $enrol, false, get_string('col_enrolments', 'local_reportpanel'), 'course'),
                'completion' => self::block(
                    get_string('rank_completion_title', 'local_reportpanel'),
                    $completion, false, get_string('col_completions', 'local_reportpanel'), 'course'),
                'time' => self::block(
                    get_string('rank_time_title', 'local_reportpanel'),
                    $time, true, get_string('col_timeontask', 'local_reportpanel'), 'course'),
                'learners' => self::block(
                    get_string('rank_learners_title', 'local_reportpanel'),
                    $learners, false, get_string('col_completions', 'local_reportpanel'), 'user'),
            ],
        ];
    }

    /**
     * Charts: one horizontal bar per block (value on the x-axis).
     *
     * @param array $data
     * @return array
     */
    // Ranking blocks render as CSS meter bars (see the template), not Chart.js — this
    // keeps a 1-row block visually identical to a 50-row one. No chart objects needed.
    public function get_charts(array $data): array {
        return [];
    }

    /**
     * Courses ranked by time on task, then filtered to a category if requested.
     * Because the trackmytime API ranks globally, we over-fetch and slice.
     *
     * @param int $from
     * @param int $to
     * @param int $categoryid
     * @param int $limit
     * @return array list of {id, name, count} where count = seconds
     */
    protected function courses_by_time(int $from, int $to, int $categoryid, int $limit): array {
        $fetch = $categoryid > 0 ? max($limit * 5, 50) : $limit;
        $ranked = timetracking::course_rankings($from, $to, $fetch);
        if (empty($ranked)) {
            return [];
        }
        $ids = array_map(fn($r) => $r->courseid, $ranked);
        $meta = analytics::course_meta($ids);
        $rows = [];
        foreach ($ranked as $r) {
            if (!isset($meta[$r->courseid])) {
                continue; // Deleted course.
            }
            if ($categoryid > 0 && $meta[$r->courseid]->category !== $categoryid) {
                continue;
            }
            $rows[] = (object)[
                'id' => (int)$r->courseid,
                'name' => $meta[$r->courseid]->name,
                'count' => (int)$r->seconds,
            ];
            if (count($rows) >= $limit) {
                break;
            }
        }
        return $rows;
    }

    /**
     * Shape one ranking block for the template + charts.
     *
     * @param string $title
     * @param array $items list of {id, name, count}
     * @param bool $istime whether count is seconds (format as duration)
     * @param string $unit column/axis label
     * @param string $type 'course' or 'user' — drives the drill-down link
     * @return array
     */
    protected static function block(string $title, array $items, bool $istime, string $unit,
            string $type): array {
        $counts = array_map(fn($it) => (int)$it->count, $items);
        $max = !empty($counts) ? max($counts) : 0;
        $rows = [];
        $rank = 1;
        foreach ($items as $it) {
            $count = (int)$it->count;
            $rows[] = [
                'rank' => $rank++,
                'label' => $it->name,
                'value' => $istime ? timetracking::format_seconds($count) : format::number($count),
                'barpct' => $max > 0 ? (int)round($count / $max * 100) : 0,
                'url' => self::drill_url($type, (int)$it->id),
            ];
        }
        return [
            'title' => $title,
            'colunit' => $unit,
            'rows' => $rows,
            'hasrows' => !empty($rows),
        ];
    }

    /**
     * Drill-down URL for a ranked row.
     *
     * @param string $type 'course' or 'user'
     * @param int $id
     * @return string
     */
    protected static function drill_url(string $type, int $id): string {
        if ($id <= 0) {
            return '';
        }
        if ($type === 'user') {
            return (new \moodle_url('/local/reportpanel/consolidated.php', ['userid' => $id]))->out(false);
        }
        return (new \moodle_url('/local/reportpanel/courseconsolidated.php', ['courseid' => $id]))->out(false);
    }
}
