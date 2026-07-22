<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Grading queue — assignment submissions awaiting grade, and quiz manual-grade gaps.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading {

    /**
     * Assignments across the given courses that have submissions awaiting grading.
     *
     * @param int[] $courseids
     * @return \stdClass[] rows: assignid, name, course, duedate, pending, oldest
     */
    public static function assign_queue(array $courseids): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT a.id AS assignid, a.name, a.course, a.duedate,
                       COUNT(DISTINCT s.userid) AS pending,
                       MIN(s.timemodified) AS oldest
                  FROM {assign} a
                  JOIN {assign_submission} s
                    ON s.assignment = a.id AND s.latest = 1 AND s.status = 'submitted'
             LEFT JOIN {assign_grades} g
                    ON g.assignment = a.id AND g.userid = s.userid
                 WHERE a.course $insql
                   AND (g.id IS NULL OR g.grade IS NULL OR g.grade < 0 OR g.timemodified < s.timemodified)
              GROUP BY a.id, a.name, a.course, a.duedate
                HAVING COUNT(DISTINCT s.userid) > 0
              ORDER BY oldest ASC";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Approximate count of quiz attempts awaiting manual grading, per course.
     * (Phase 1 approximation — finished attempts with no total grade yet.)
     *
     * @param int[] $courseids
     * @return array [courseid => count]
     */
    public static function quiz_pending_by_course(array $courseids): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT q.course AS courseid, COUNT(qa.id) AS cnt
                  FROM {quiz_attempts} qa
                  JOIN {quiz} q ON q.id = qa.quiz
                 WHERE q.course $insql
                   AND qa.state = 'finished'
                   AND qa.sumgrades IS NULL
              GROUP BY q.course";
        $out = [];
        foreach ($DB->get_records_sql($sql, $params) as $r) {
            $out[(int)$r->courseid] = (int)$r->cnt;
        }
        return $out;
    }

    /**
     * Total pending grading count across courses (assignment submissions).
     *
     * @param \stdClass[] $queue result of assign_queue()
     * @return int
     */
    public static function total_pending(array $queue): int {
        $n = 0;
        foreach ($queue as $row) {
            $n += (int)$row->pending;
        }
        return $n;
    }

    /**
     * Pending assignment grading count per course.
     *
     * @param \stdClass[] $queue
     * @return array [courseid => count]
     */
    public static function pending_by_course(array $queue): array {
        $out = [];
        foreach ($queue as $row) {
            $cid = (int)$row->course;
            $out[$cid] = ($out[$cid] ?? 0) + (int)$row->pending;
        }
        return $out;
    }
}
