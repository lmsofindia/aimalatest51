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

/**
 * Shared aggregate queries for the ranking (Domain D) and site-overview (Domain B)
 * reports. All reads are live joins against core tables; no cache in v1 (volumes are
 * bounded by GROUP BY + LIMIT, or by counts). Every method is static and side-effect
 * free so both reports can compose them freely.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analytics {

    /**
     * Optional " AND c.category = ?" fragment for a course alias.
     *
     * @param int $categoryid 0 = no filter
     * @param string $alias course table alias
     * @return array [string $sql, array $params]
     */
    protected static function category_clause(int $categoryid, string $alias = 'c'): array {
        if ($categoryid > 0) {
            return [" AND {$alias}.category = ?", [$categoryid]];
        }
        return ['', []];
    }

    /**
     * Top courses by number of (actively) enrolled distinct users.
     *
     * @param int $categoryid 0 = all
     * @param int $limit
     * @return array list of {id, name, count}
     */
    public static function top_courses_by_enrolment(int $categoryid, int $limit): array {
        global $DB;
        [$catsql, $catparams] = self::category_clause($categoryid);
        $sql = "SELECT c.id, c.fullname, COUNT(DISTINCT ue.userid) AS cnt
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE c.id <> ?" . $catsql . "
              GROUP BY c.id, c.fullname
              ORDER BY cnt DESC, c.fullname ASC";
        $params = array_merge([SITEID], $catparams);
        return self::rows_from_courses($DB->get_records_sql($sql, $params, 0, $limit));
    }

    /**
     * Top courses by course-completions within a window.
     *
     * @param int $from
     * @param int $to
     * @param int $categoryid
     * @param int $limit
     * @return array list of {id, name, count}
     */
    public static function top_courses_by_completion(int $from, int $to, int $categoryid, int $limit): array {
        global $DB;
        [$catsql, $catparams] = self::category_clause($categoryid);
        $sql = "SELECT c.id, c.fullname, COUNT(cc.id) AS cnt
                  FROM {course} c
                  JOIN {course_completions} cc ON cc.course = c.id
                 WHERE cc.timecompleted IS NOT NULL
                   AND cc.timecompleted >= ? AND cc.timecompleted < ?" . $catsql . "
              GROUP BY c.id, c.fullname
              ORDER BY cnt DESC, c.fullname ASC";
        $params = array_merge([$from, $to], $catparams);
        return self::rows_from_courses($DB->get_records_sql($sql, $params, 0, $limit));
    }

    /**
     * Most-active learners by course-completions within a window.
     *
     * @param int $from
     * @param int $to
     * @param int $limit
     * @return array list of {id, name, count}
     */
    public static function top_learners_by_completion(int $from, int $to, int $limit): array {
        global $DB;
        $sql = "SELECT cc.userid, COUNT(cc.id) AS cnt
                  FROM {course_completions} cc
                  JOIN {user} u ON u.id = cc.userid
                 WHERE cc.timecompleted IS NOT NULL
                   AND cc.timecompleted >= ? AND cc.timecompleted < ?
                   AND u.deleted = 0
              GROUP BY cc.userid
              ORDER BY cnt DESC";
        $records = $DB->get_records_sql($sql, [$from, $to], 0, $limit);
        if (empty($records)) {
            return [];
        }
        $ids = array_map(fn($r) => (int)$r->userid, $records);
        $names = self::resolve_user_names($ids);
        $rows = [];
        foreach ($records as $r) {
            $rows[] = (object)[
                'id' => (int)$r->userid,
                'name' => $names[(int)$r->userid] ?? get_string('deleteduser', 'local_reportpanel'),
                'count' => (int)$r->cnt,
            ];
        }
        return $rows;
    }

    /**
     * Resolve course names (+ category) for a set of ids.
     *
     * @param int[] $ids
     * @return array [id => {name, category}]
     */
    public static function course_meta(array $ids): array {
        global $DB;
        if (empty($ids)) {
            return [];
        }
        $records = $DB->get_records_list('course', 'id', $ids, '', 'id, fullname, category');
        $out = [];
        foreach ($records as $r) {
            $out[(int)$r->id] = (object)['name' => format_string($r->fullname), 'category' => (int)$r->category];
        }
        return $out;
    }

    /**
     * Resolve display names for a set of user ids.
     *
     * @param int[] $ids
     * @return array [id => fullname]
     */
    public static function resolve_user_names(array $ids): array {
        global $DB;
        if (empty($ids)) {
            return [];
        }
        $records = $DB->get_records_list('user', 'id', $ids, '', '*');
        $out = [];
        foreach ($records as $u) {
            $out[(int)$u->id] = fullname($u);
        }
        return $out;
    }

    // ── Site-overview (Domain B) counts ──────────────────────────────────────

    /**
     * Count of real, active accounts (non-deleted, unsuspended, confirmed, id > 2).
     *
     * @return int
     */
    public static function total_users(): int {
        global $DB;
        return (int)$DB->count_records_select('user',
            'deleted = 0 AND suspended = 0 AND confirmed = 1 AND id > 2');
    }

    /**
     * New user registrations created within a window (real accounts only).
     *
     * @param int $from
     * @param int $to
     * @return int
     */
    public static function new_registrations(int $from, int $to): int {
        global $DB;
        return (int)$DB->count_records_select('user',
            'deleted = 0 AND id > 2 AND timecreated >= ? AND timecreated < ?', [$from, $to]);
    }

    /**
     * Registration status breakdown.
     *
     * @return array {confirmed, unconfirmed, suspended, deleted}
     */
    public static function registration_breakdown(): array {
        global $DB;
        return [
            'confirmed'   => (int)$DB->count_records_select('user',
                'deleted = 0 AND suspended = 0 AND confirmed = 1 AND id > 2'),
            'unconfirmed' => (int)$DB->count_records_select('user',
                'deleted = 0 AND confirmed = 0 AND id > 2'),
            'suspended'   => (int)$DB->count_records_select('user',
                'deleted = 0 AND suspended = 1 AND id > 2'),
            'deleted'     => (int)$DB->count_records_select('user', 'deleted = 1'),
        ];
    }

    /**
     * Enrolment-health counts.
     *
     * @param int $inactivedays "not accessed" threshold
     * @return array {nocourses, multicourse, notaccessed}
     */
    public static function enrolment_health(int $inactivedays): array {
        global $DB;

        $nocourses = (int)$DB->get_field_sql(
            "SELECT COUNT(u.id) FROM {user} u
              WHERE u.deleted = 0 AND u.id > 2
                AND NOT EXISTS (SELECT 1 FROM {user_enrolments} ue WHERE ue.userid = u.id)");

        $multicourse = (int)$DB->get_field_sql(
            "SELECT COUNT(*) FROM (
                SELECT ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid <> ?
              GROUP BY ue.userid
                HAVING COUNT(DISTINCT e.courseid) > 1
             ) t", [SITEID]);

        $cutoff = time() - max(1, $inactivedays) * DAYSECS;
        $notaccessed = (int)$DB->count_records_select('user',
            'deleted = 0 AND id > 2 AND lastaccess > 0 AND lastaccess < ?', [$cutoff]);

        return ['nocourses' => $nocourses, 'multicourse' => $multicourse, 'notaccessed' => $notaccessed];
    }

    /**
     * Total distinct user↔course active enrolments (excludes the site course).
     *
     * @return int
     */
    public static function total_enrolments(): int {
        global $DB;
        return (int)$DB->get_field_sql(
            "SELECT COUNT(*) FROM (
                SELECT DISTINCT ue.userid, e.courseid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid <> ?
             ) t", [SITEID]);
    }

    /**
     * Course completions within a window.
     *
     * @param int $from
     * @param int $to
     * @return int
     */
    public static function course_completions(int $from, int $to): int {
        global $DB;
        return (int)$DB->count_records_select('course_completions',
            'timecompleted IS NOT NULL AND timecompleted >= ? AND timecompleted < ?', [$from, $to]);
    }

    /**
     * Activity (module) completions within a window.
     *
     * @param int $from
     * @param int $to
     * @return int
     */
    public static function activity_completions(int $from, int $to): int {
        global $DB;
        return (int)$DB->count_records_select('course_modules_completion',
            'completionstate > 0 AND timemodified >= ? AND timemodified < ?', [$from, $to]);
    }

    /**
     * Certificates issued within a window (mod_customcert). 0 if not installed.
     *
     * @param int $from
     * @param int $to
     * @return int
     */
    public static function certificates_issued(int $from, int $to): int {
        global $DB;
        if (!$DB->get_manager()->table_exists('customcert_issues')) {
            return 0;
        }
        return (int)$DB->count_records_select('customcert_issues',
            'timecreated >= ? AND timecreated < ?', [$from, $to]);
    }

    /**
     * Daily count series from a table's timestamp column, bucketed in PHP so the
     * result is DB-portable and timezone-consistent, gap-filled with zeros.
     *
     * @param string $table table name (no braces)
     * @param string $tscol timestamp column
     * @param int $from
     * @param int $to
     * @param string $extrawhere additional SQL (already using ? params), prefixed with ' AND '
     * @param array $extraparams
     * @return array ['YYYY-MM-DD' => count]
     */
    public static function day_count_series(string $table, string $tscol, int $from, int $to,
            string $extrawhere = '', array $extraparams = []): array {
        global $DB;
        $series = [];
        for ($d = usergetmidnight($from); $d < $to; $d += DAYSECS) {
            $series[date('Y-m-d', $d)] = 0;
        }
        if ($to <= $from) {
            return $series;
        }
        $rs = $DB->get_recordset_sql(
            "SELECT id, {$tscol} AS ts FROM {" . $table . "}
              WHERE {$tscol} >= ? AND {$tscol} < ?" . $extrawhere,
            array_merge([$from, $to], $extraparams)
        );
        foreach ($rs as $r) {
            $key = date('Y-m-d', (int)$r->ts);
            if (!isset($series[$key])) {
                $series[$key] = 0;
            }
            $series[$key]++;
        }
        $rs->close();
        ksort($series);
        return $series;
    }

    /**
     * Shape course get_records rows into {id, name, count}.
     *
     * @param array $records rows with id, fullname, cnt
     * @return array
     */
    protected static function rows_from_courses(array $records): array {
        $rows = [];
        foreach ($records as $r) {
            $rows[] = (object)[
                'id' => (int)$r->id,
                'name' => format_string($r->fullname),
                'count' => (int)$r->cnt,
            ];
        }
        return $rows;
    }
}
