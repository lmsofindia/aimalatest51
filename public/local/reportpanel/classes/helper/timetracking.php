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

namespace local_reportpanel\helper;

use local_trackmytime\reporting;

/**
 * Single seam between the Reports Hub and local_trackmytime's time-on-task data.
 *
 * Everything routes through the SUPPORTED \local_trackmytime\reporting API — never a
 * direct read of another plugin's table. If trackmytime is absent (or its table not
 * yet created), {@see available()} returns false and every method degrades to an
 * empty/zero result so the Hub still renders with a graceful "time data unavailable"
 * state.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timetracking {

    /**
     * Is trackmytime's reporting API installed and its table present?
     *
     * @return bool
     */
    public static function available(): bool {
        return class_exists('\\local_trackmytime\\reporting') && reporting::is_available();
    }

    /**
     * Total tracked seconds across the site in a window.
     *
     * @param int|null $from
     * @param int|null $to
     * @return int
     */
    public static function total_time(?int $from, ?int $to): int {
        return self::available() ? reporting::total_time($from, $to) : 0;
    }

    /**
     * Daily time-on-task series ['YYYY-MM-DD' => seconds].
     *
     * @param array $filters {from,to,courseid?,userids?}
     * @return array
     */
    public static function time_series(array $filters): array {
        return self::available() ? reporting::time_series($filters) : [];
    }

    /**
     * Userids active (any tracked session) within the last N days.
     *
     * @param int $days
     * @return int[]
     */
    public static function active_users(int $days): array {
        return self::available() ? reporting::active_users($days) : [];
    }

    /**
     * Count of distinct active users in the last N days.
     *
     * @param int $days
     * @return int
     */
    public static function active_count(int $days): int {
        return self::available() ? reporting::active_user_count($days) : 0;
    }

    /**
     * 7 x 24 weekly usage grid of seconds.
     *
     * @param int|null $from
     * @param int|null $to
     * @return array
     */
    public static function usage_heatmap(?int $from, ?int $to): array {
        return self::available() ? reporting::usage_heatmap($from, $to) : self::empty_heatmap();
    }

    /**
     * Courses ranked by tracked time in a window.
     *
     * @param int|null $from
     * @param int|null $to
     * @param int $limit
     * @return array list of {courseid, seconds}
     */
    public static function course_rankings(?int $from, ?int $to, int $limit): array {
        return self::available() ? reporting::course_rankings($from, $to, $limit) : [];
    }

    /**
     * Total seconds per course module across cmids, keyed by cmid.
     *
     * @param int[] $cmids
     * @param int|null $from
     * @param int|null $to
     * @return array [cmid => seconds]
     */
    public static function module_time_totals(array $cmids, ?int $from = null, ?int $to = null): array {
        return self::available() ? reporting::module_time_totals($cmids, $from, $to) : [];
    }

    /**
     * Format seconds consistently with the trackmytime UI.
     *
     * @param int $secs
     * @return string
     */
    public static function format_seconds(int $secs): string {
        if (class_exists('\\local_trackmytime\\reporting')) {
            return reporting::format_seconds($secs);
        }
        // Local fallback identical in shape to the API formatter.
        if ($secs <= 0) {
            return '0m';
        }
        if ($secs < 60) {
            return $secs . 's';
        }
        $h = intdiv($secs, 3600);
        $m = intdiv($secs % 3600, 60);
        return $h > 0 ? ($m > 0 ? "{$h}h {$m}m" : "{$h}h") : "{$m}m";
    }

    /**
     * Empty 7 x 24 heatmap grid.
     *
     * @return array
     */
    protected static function empty_heatmap(): array {
        $grid = [];
        for ($d = 0; $d < 7; $d++) {
            $grid[$d] = array_fill(0, 24, 0);
        }
        return $grid;
    }
}
