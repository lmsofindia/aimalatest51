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
 * Catalogue query engine for local_edzallcourse.
 *
 * Wraps Moodle core category APIs for the tree/counts and a single, scoped,
 * paginated SQL for the course listing (needed for popularity sort + scoped
 * search). All course cards are built only for the visible page.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzallcourse\helper;

use core_course_category;
use context_system;

/**
 * Category tree + course listing helpers.
 */
class catalogue {

    /** @var array Per-request memo of descendant id lists keyed by category id. */
    private static $descendantcache = [];

    /**
     * Configured scope: 'recursive' (default) or 'direct'.
     *
     * @return string
     */
    public static function scope(): string {
        return get_config('local_edzallcourse', 'scope') === 'direct' ? 'direct' : 'recursive';
    }

    /**
     * Configured page size.
     *
     * @return int
     */
    public static function perpage(): int {
        $n = (int)get_config('local_edzallcourse', 'perpage');
        return $n > 0 ? $n : 12;
    }

    /**
     * Configured default sort key.
     *
     * @return string
     */
    public static function default_sort(): string {
        $s = get_config('local_edzallcourse', 'defaultsort');
        return in_array($s, ['popular', 'new', 'az', 'start'], true) ? $s : 'popular';
    }

    /**
     * Card field toggles from config.
     *
     * @return array
     */
    public static function card_opts(): array {
        return [
            'showteacher'   => (bool)get_config('local_edzallcourse', 'showteacher'),
            'showstartdate' => (bool)get_config('local_edzallcourse', 'showstartdate'),
            'showsummary'   => (bool)get_config('local_edzallcourse', 'showsummary'),
        ];
    }

    /**
     * Fetch a category (visible to the current user) or null.
     *
     * @param int $id
     * @return core_course_category|null
     */
    public static function get_category(int $id): ?core_course_category {
        if ($id <= 0) {
            return null;
        }
        $cat = core_course_category::get($id, IGNORE_MISSING, false);
        return $cat ?: null;
    }

    /**
     * Resolve the category to show: the requested one if valid, else the first
     * root category, else 0.
     *
     * @param int $requested
     * @return int
     */
    public static function effective_category_id(int $requested): int {
        if ($requested > 0 && self::get_category($requested)) {
            return $requested;
        }
        foreach (core_course_category::top()->get_children() as $c) {
            return (int)$c->id;
        }
        return 0;
    }

    /**
     * Root categories (top level) as chip/rail data.
     *
     * @return array list of ['id','name','count','haschildren']
     */
    public static function roots(): array {
        $out = [];
        foreach (core_course_category::top()->get_children() as $cat) {
            $out[] = [
                'id'          => (int)$cat->id,
                'name'        => $cat->get_formatted_name(),
                'count'       => self::count_for((int)$cat->id),
                'haschildren' => $cat->has_children(),
            ];
        }
        return $out;
    }

    /**
     * Direct children of a category as chip data.
     *
     * @param int $id
     * @return array list of ['id','name','count','haschildren']
     */
    public static function children(int $id): array {
        $cat = self::get_category($id);
        if (!$cat) {
            return [];
        }
        $out = [];
        foreach ($cat->get_children() as $child) {
            $out[] = [
                'id'          => (int)$child->id,
                'name'        => $child->get_formatted_name(),
                'count'       => self::count_for((int)$child->id),
                'haschildren' => $child->has_children(),
            ];
        }
        return $out;
    }

    /**
     * Ancestor path (root → node), each ['id','name']. Empty if not found.
     *
     * @param int $id
     * @return array
     */
    public static function path(int $id): array {
        $cat = self::get_category($id);
        if (!$cat) {
            return [];
        }
        $out = [];
        foreach ($cat->get_parents() as $pid) {
            $p = self::get_category((int)$pid);
            if ($p) {
                $out[] = ['id' => (int)$p->id, 'name' => $p->get_formatted_name()];
            }
        }
        $out[] = ['id' => (int)$cat->id, 'name' => $cat->get_formatted_name()];
        return $out;
    }

    /**
     * All descendant category ids including self (visible to user), memoised.
     *
     * @param core_course_category $cat
     * @return int[]
     */
    public static function descendant_ids(core_course_category $cat): array {
        $key = (int)$cat->id;
        if (isset(self::$descendantcache[$key])) {
            return self::$descendantcache[$key];
        }
        $ids = [$key];
        foreach ($cat->get_children() as $child) {
            $ids = array_merge($ids, self::descendant_ids($child));
        }
        self::$descendantcache[$key] = $ids;
        return $ids;
    }

    /**
     * Course count for a category under the active scope (MUC cached).
     *
     * @param int $id
     * @return int
     */
    public static function count_for(int $id): int {
        $scope = self::scope();
        $cache = \cache::make('local_edzallcourse', 'treecounts');
        $ckey  = $scope . '_' . $id;
        $val = $cache->get($ckey);
        if ($val !== false) {
            return (int)$val;
        }
        $cat = self::get_category($id);
        $count = $cat ? (int)$cat->get_courses_count(['recursive' => ($scope === 'recursive')]) : 0;
        $cache->set($ckey, $count);
        return $count;
    }

    /**
     * Normalise a sort key to a safe ORDER BY clause.
     *
     * @param string $sort
     * @return string
     */
    private static function order_clause(string $sort): string {
        switch ($sort) {
            case 'new':
                return 'c.timecreated DESC, c.id DESC';
            case 'az':
                return 'c.fullname ASC';
            case 'start':
                return 'c.startdate ASC, c.fullname ASC';
            case 'popular':
            default:
                return 'enrolledcount DESC, c.fullname ASC';
        }
    }

    /**
     * A paginated, searched, sorted page of courses for a category node.
     *
     * Scoped to the node (direct) or its whole subtree (recursive). Uses one
     * SQL with limit/offset; cards are built only for the returned page.
     *
     * @param int $categoryid
     * @param string $q search text (already trimmed)
     * @param string $sort one of popular|new|az|start
     * @param int $page 1-based
     * @param int|null $perpage
     * @return array ['records'=>stdClass[], 'total','page','perpage','totalpages','from','to','path']
     */
    public static function courses_page(int $categoryid, string $q, string $sort, int $page, ?int $perpage = null): array {
        global $DB;

        $perpage = $perpage && $perpage > 0 ? $perpage : self::perpage();
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perpage;
        $sort    = in_array($sort, ['popular', 'new', 'az', 'start'], true) ? $sort : self::default_sort();

        $empty = [
            'records' => [], 'total' => 0, 'page' => 1, 'perpage' => $perpage,
            'totalpages' => 0, 'from' => 0, 'to' => 0, 'path' => [],
        ];

        $cat = self::get_category($categoryid);
        if (!$cat) {
            return $empty;
        }

        $ids = self::scope() === 'recursive' ? self::descendant_ids($cat) : [(int)$cat->id];
        list($insql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'cat');

        $where = ["c.category $insql", 'c.id <> :siteid'];
        $params['siteid'] = SITEID;

        if (!has_capability('moodle/course:viewhiddencourses', context_system::instance())) {
            $where[] = 'c.visible = 1';
        }

        if ($q !== '') {
            $like = '(' . $DB->sql_like('c.fullname', ':q1', false) . ' OR '
                        . $DB->sql_like('c.shortname', ':q2', false) . ' OR '
                        . $DB->sql_like('c.summary', ':q3', false) . ')';
            $where[] = $like;
            $escaped = '%' . $DB->sql_like_escape($q) . '%';
            $params['q1'] = $escaped;
            $params['q2'] = $escaped;
            $params['q3'] = $escaped;
        }

        $wsql = implode(' AND ', $where);

        $total = (int)$DB->count_records_sql("SELECT COUNT(DISTINCT c.id) FROM {course} c WHERE $wsql", $params);
        if ($total === 0) {
            $empty['path'] = self::path($categoryid);
            return array_merge($empty, ['page' => 1]);
        }

        // Clamp the requested page into range BEFORE querying, so a stale/high
        // page (deep link or shrunken result set) returns the last page, not empty.
        $totalpages = (int)ceil($total / $perpage);
        if ($page > $totalpages) {
            $page = $totalpages;
        }
        $offset = ($page - 1) * $perpage;

        $order = self::order_clause($sort);

        // Correlated subquery for enrolment count avoids GROUP BY on TEXT columns (portable).
        $sql = "SELECT c.id, c.category, c.fullname, c.shortname, c.summary, c.summaryformat,
                       c.visible, c.timecreated, c.startdate, c.enddate,
                       (SELECT COUNT(DISTINCT ue.id)
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                         WHERE e.courseid = c.id) AS enrolledcount
                  FROM {course} c
                 WHERE $wsql
              ORDER BY $order";

        $records = $DB->get_records_sql($sql, $params, $offset, $perpage);

        return [
            'records'    => $records,
            'total'      => $total,
            'page'       => $page,
            'perpage'    => $perpage,
            'totalpages' => $totalpages,
            'from'       => $offset + 1,
            'to'         => min($offset + $perpage, $total),
            'path'       => self::path($categoryid),
        ];
    }
}
