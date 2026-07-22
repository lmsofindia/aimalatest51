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

namespace local_trackmytime;

/**
 * Public reporting API for local_trackmytime.
 *
 * This is a SUPPORTED, versioned contract: other plugins (e.g. local_reportpanel)
 * consume time-on-task data through these static methods instead of reading the
 * {trackmytime} table directly. Keep signatures backward-compatible; add methods
 * rather than changing existing ones.
 *
 * All methods degrade safely to empty/zero results when the {trackmytime} table is
 * not present, so callers can guard once with class_exists() and never worry about
 * install order.
 *
 * Time semantics: trackmytime records module-view time only (its heartbeat fires on
 * mod-*-view pages). It does NOT record logins or non-module page visits — callers
 * that need those must use core (user.lastaccess / logstore), not this API.
 *
 * @package   local_trackmytime
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reporting {

    /** @var int API contract version. Bump when the method surface changes. */
    const APIVERSION = 1;

    /**
     * Is the tracking table available? All public methods no-op safely if not.
     *
     * @return bool
     */
    public static function is_available(): bool {
        global $DB;
        static $exists = null;
        if ($exists === null) {
            $exists = $DB->get_manager()->table_exists('trackmytime');
        }
        return $exists;
    }

    /**
     * Normalise an optional [from, to] window into a SQL fragment + params on the
     * timestart column. A null bound means "unbounded" on that side.
     *
     * @param int|null $from inclusive lower bound (unix ts)
     * @param int|null $to exclusive upper bound (unix ts)
     * @return array [string $wheresql, array $params]  ($wheresql already prefixed with ' AND ' or '')
     */
    protected static function window(?int $from, ?int $to): array {
        $sql = '';
        $params = [];
        if ($from !== null) {
            $sql .= ' AND timestart >= ?';
            $params[] = $from;
        }
        if ($to !== null) {
            $sql .= ' AND timestart < ?';
            $params[] = $to;
        }
        return [$sql, $params];
    }

    /**
     * Total seconds a user spent in a course (optionally within a window).
     *
     * @param int $userid
     * @param int $courseid
     * @param int|null $from
     * @param int|null $to
     * @return int seconds
     */
    public static function course_time(int $userid, int $courseid, ?int $from = null, ?int $to = null): int {
        global $DB;
        if (!self::is_available()) {
            return 0;
        }
        [$w, $p] = self::window($from, $to);
        return (int)$DB->get_field_sql(
            "SELECT COALESCE(SUM(timespent), 0) FROM {trackmytime}
              WHERE userid = ? AND courseid = ?" . $w,
            array_merge([$userid, $courseid], $p)
        );
    }

    /**
     * Total seconds spent by many users across a course, keyed by userid.
     * Users with no tracked time are omitted (caller can default to 0).
     *
     * @param int $courseid
     * @param int|null $from
     * @param int|null $to
     * @return array [userid => seconds]
     */
    public static function course_time_by_user(int $courseid, ?int $from = null, ?int $to = null): array {
        global $DB;
        if (!self::is_available()) {
            return [];
        }
        [$w, $p] = self::window($from, $to);
        $rows = $DB->get_records_sql(
            "SELECT userid, COALESCE(SUM(timespent), 0) AS secs
               FROM {trackmytime}
              WHERE courseid = ?" . $w . "
           GROUP BY userid",
            array_merge([$courseid], $p)
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->userid] = (int)$r->secs;
        }
        return $out;
    }

    /**
     * Seconds spent on a single course module, keyed by userid.
     *
     * @param int $cmid
     * @param int[] $userids optional filter; empty = all users
     * @param int|null $from
     * @param int|null $to
     * @return array [userid => seconds]
     */
    public static function module_time(int $cmid, array $userids = [], ?int $from = null, ?int $to = null): array {
        global $DB;
        if (!self::is_available()) {
            return [];
        }
        [$w, $p] = self::window($from, $to);
        $inuser = '';
        if (!empty($userids)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_map('intval', $userids));
            $inuser = ' AND userid ' . $insql;
            $p = array_merge($p, $inparams);
        }
        $rows = $DB->get_records_sql(
            "SELECT userid, COALESCE(SUM(timespent), 0) AS secs
               FROM {trackmytime}
              WHERE cmid = ?" . $w . $inuser . "
           GROUP BY userid",
            array_merge([$cmid], $p)
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->userid] = (int)$r->secs;
        }
        return $out;
    }

    /**
     * Total seconds per course module across a set of cmids (all users), keyed by cmid.
     * Bulk companion to {@see module_time()} for per-activity reporting.
     *
     * @param int[] $cmids
     * @param int|null $from
     * @param int|null $to
     * @return array [cmid => seconds]
     */
    public static function module_time_totals(array $cmids, ?int $from = null, ?int $to = null): array {
        global $DB;
        if (!self::is_available() || empty($cmids)) {
            return [];
        }
        [$w, $p] = self::window($from, $to);
        [$insql, $inparams] = $DB->get_in_or_equal(array_map('intval', $cmids));
        $rows = $DB->get_records_sql(
            "SELECT cmid, COALESCE(SUM(timespent), 0) AS secs
               FROM {trackmytime}
              WHERE cmid " . $insql . $w . "
           GROUP BY cmid",
            array_merge($inparams, $p)
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->cmid] = (int)$r->secs;
        }
        return $out;
    }

    /**
     * Daily time-on-task series across a window, bucketed in PHP by calendar day so
     * the result honours the server timezone and stays DB-portable.
     *
     * @param array $filters {from:int, to:int, courseid?:int, userids?:int[]}
     * @return array ['YYYY-MM-DD' => seconds] ordered ascending, gap-filled with 0
     */
    public static function time_series(array $filters): array {
        global $DB;
        $from = isset($filters['from']) ? (int)$filters['from'] : (time() - 30 * DAYSECS);
        $to = isset($filters['to']) ? (int)$filters['to'] : (time() + 1);
        if (!self::is_available() || $to <= $from) {
            return [];
        }

        $where = 'timestart >= ? AND timestart < ?';
        $params = [$from, $to];
        if (!empty($filters['courseid'])) {
            $where .= ' AND courseid = ?';
            $params[] = (int)$filters['courseid'];
        }
        if (!empty($filters['userids'])) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_map('intval', $filters['userids']));
            $where .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        // Pre-seed every day in range with 0 so the line has no gaps.
        $series = [];
        for ($d = usergetmidnight($from); $d < $to; $d += DAYSECS) {
            $series[date('Y-m-d', $d)] = 0;
        }

        $rs = $DB->get_recordset_sql(
            "SELECT id, timestart, timespent FROM {trackmytime} WHERE {$where}",
            $params
        );
        foreach ($rs as $r) {
            $key = date('Y-m-d', (int)$r->timestart);
            if (!isset($series[$key])) {
                $series[$key] = 0;
            }
            $series[$key] += (int)$r->timespent;
        }
        $rs->close();
        ksort($series);
        return $series;
    }

    /**
     * Userids with at least one tracked session in the last N days.
     *
     * @param int $sincedays
     * @return int[] userids
     */
    public static function active_users(int $sincedays = 7): array {
        global $DB;
        if (!self::is_available()) {
            return [];
        }
        $since = time() - max(1, $sincedays) * DAYSECS;
        $rows = $DB->get_fieldset_sql(
            "SELECT DISTINCT userid FROM {trackmytime} WHERE timestart >= ?",
            [$since]
        );
        return array_map('intval', $rows);
    }

    /**
     * Count of distinct active users in the last N days.
     *
     * @param int $sincedays
     * @return int
     */
    public static function active_user_count(int $sincedays = 7): int {
        return count(self::active_users($sincedays));
    }

    /**
     * Has this user any tracked session in the last N days?
     *
     * @param int $userid
     * @param int $days
     * @return bool
     */
    public static function is_active(int $userid, int $days = 7): bool {
        global $DB;
        if (!self::is_available()) {
            return false;
        }
        $since = time() - max(1, $days) * DAYSECS;
        return $DB->record_exists_select('trackmytime',
            'userid = ? AND timestart >= ?', [$userid, $since]);
    }

    /**
     * Weekly usage heatmap: seconds bucketed by day-of-week (0=Sun..6=Sat) and hour
     * (0..23) across a window. Bucketed in PHP for portability + timezone fidelity.
     *
     * @param int|null $from
     * @param int|null $to
     * @return array $grid[$dow][$hour] = seconds  (fully populated 7 x 24)
     */
    public static function usage_heatmap(?int $from = null, ?int $to = null): array {
        global $DB;
        $grid = [];
        for ($d = 0; $d < 7; $d++) {
            $grid[$d] = array_fill(0, 24, 0);
        }
        if (!self::is_available()) {
            return $grid;
        }
        [$w, $p] = self::window($from, $to);
        $rs = $DB->get_recordset_sql(
            "SELECT id, timestart, timespent FROM {trackmytime} WHERE 1=1" . $w,
            $p
        );
        foreach ($rs as $r) {
            $dow = (int)date('w', (int)$r->timestart);   // 0 (Sun) .. 6 (Sat).
            $hour = (int)date('G', (int)$r->timestart);  // 0 .. 23.
            $grid[$dow][$hour] += (int)$r->timespent;
        }
        $rs->close();
        return $grid;
    }

    /**
     * Courses ranked by total tracked time within a window.
     *
     * @param int|null $from
     * @param int|null $to
     * @param int $limit
     * @return array list of objects {courseid:int, seconds:int} desc by seconds
     */
    public static function course_rankings(?int $from = null, ?int $to = null, int $limit = 10): array {
        global $DB;
        if (!self::is_available()) {
            return [];
        }
        [$w, $p] = self::window($from, $to);
        $rows = $DB->get_records_sql(
            "SELECT courseid, COALESCE(SUM(timespent), 0) AS secs
               FROM {trackmytime}
              WHERE courseid > 1" . $w . "
           GROUP BY courseid
           ORDER BY secs DESC",
            $p, 0, max(1, $limit)
        );
        $out = [];
        foreach ($rows as $r) {
            $out[] = (object)['courseid' => (int)$r->courseid, 'seconds' => (int)$r->secs];
        }
        return $out;
    }

    /**
     * Total tracked seconds across the site within a window (optionally one course).
     *
     * @param int|null $from
     * @param int|null $to
     * @param int|null $courseid
     * @return int seconds
     */
    public static function total_time(?int $from = null, ?int $to = null, ?int $courseid = null): int {
        global $DB;
        if (!self::is_available()) {
            return 0;
        }
        [$w, $p] = self::window($from, $to);
        $coursesql = '';
        if ($courseid !== null) {
            $coursesql = ' AND courseid = ?';
            $p[] = $courseid;
        }
        return (int)$DB->get_field_sql(
            "SELECT COALESCE(SUM(timespent), 0) FROM {trackmytime} WHERE 1=1" . $w . $coursesql,
            $p
        );
    }

    /**
     * Format seconds as a compact human string (e.g. "0m", "45s", "2h 5m").
     * Provided so consumers render time consistently with the trackmytime UI.
     *
     * @param int $secs
     * @return string
     */
    public static function format_seconds(int $secs): string {
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
}
