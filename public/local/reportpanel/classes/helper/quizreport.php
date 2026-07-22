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
 * Quiz reporting: per-quiz statistics for a course (full mode) and the current
 * user's own quiz attempts (self mode).
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizreport {

    /**
     * Per-quiz statistics for every quiz in a course.
     *
     * Grade aggregates come from {quiz_grades} (the final per-user grade, on the
     * quiz's own max-grade scale); participant counts from finished {quiz_attempts}.
     * Correlated subqueries keep the two from cross-multiplying.
     *
     * @param int $courseid
     * @return array row contexts for quiz_full.mustache
     */
    public static function course_quiz_stats(int $courseid): array {
        global $DB;

        $sql = "SELECT q.id, q.name, q.grade, cm.id AS cmid,
                       (SELECT COUNT(DISTINCT qa.userid)
                          FROM {quiz_attempts} qa
                         WHERE qa.quiz = q.id AND qa.state = 'finished') AS participants,
                       (SELECT MAX(qg.grade) FROM {quiz_grades} qg WHERE qg.quiz = q.id) AS maxgrade,
                       (SELECT MIN(qg.grade) FROM {quiz_grades} qg WHERE qg.quiz = q.id) AS mingrade,
                       (SELECT AVG(qg.grade) FROM {quiz_grades} qg WHERE qg.quiz = q.id) AS avggrade
                  FROM {quiz} q
                  JOIN {course_modules} cm ON cm.instance = q.id AND cm.course = q.course
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                 WHERE q.course = :courseid AND cm.deletioninprogress = 0
              ORDER BY q.name ASC";
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        $rows = [];
        foreach ($records as $r) {
            $max = (float)$r->grade;
            $reporturl = new \moodle_url('/mod/quiz/report.php',
                ['id' => (int)$r->cmid, 'mode' => 'overview']);
            $rows[] = [
                'name' => format_string($r->name),
                'url' => $reporturl->out(false),
                'participants' => (int)$r->participants,
                'highest' => self::grade_label($r->maxgrade, $max),
                'lowest' => self::grade_label($r->mingrade, $max),
                'average' => self::grade_label($r->avggrade, $max),
            ];
        }
        return $rows;
    }

    /**
     * The current user's own quiz attempts across enrolled courses.
     *
     * @param int $userid
     * @return array row contexts for quiz_self.mustache
     */
    public static function own_attempts(int $userid): array {
        global $DB;

        $sql = "SELECT q.id AS quizid, q.name, q.grade, c.id AS courseid, c.fullname AS coursename,
                       cm.id AS cmid,
                       COUNT(qa.id) AS attempts,
                       MAX(qa.timefinish) AS lastfinish,
                       MAX(qg.grade) AS mygrade
                  FROM {quiz_attempts} qa
                  JOIN {quiz} q ON q.id = qa.quiz
                  JOIN {course} c ON c.id = q.course
                  JOIN {course_modules} cm ON cm.instance = q.id AND cm.course = q.course
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
             LEFT JOIN {quiz_grades} qg ON qg.quiz = q.id AND qg.userid = qa.userid
                 WHERE qa.userid = :userid AND qa.state = 'finished'
                       AND cm.deletioninprogress = 0
              GROUP BY q.id, q.name, q.grade, c.id, c.fullname, cm.id
              ORDER BY lastfinish DESC";
        $records = $DB->get_records_sql($sql, ['userid' => $userid]);

        $dateformat = get_string('strftimedatetimeshort', 'langconfig');
        $rows = [];
        foreach ($records as $r) {
            $viewurl = new \moodle_url('/mod/quiz/view.php', ['id' => (int)$r->cmid]);
            $rows[] = [
                'name' => format_string($r->name),
                'url' => $viewurl->out(false),
                'coursename' => format_string($r->coursename),
                'mygrade' => self::grade_label($r->mygrade, (float)$r->grade),
                'attempts' => (int)$r->attempts,
                'lastattempt' => $r->lastfinish
                    ? userdate((int)$r->lastfinish, $dateformat) : '—',
            ];
        }
        return $rows;
    }

    /**
     * Format a grade value against a max as "82% (8.20 / 10.00)", or "—".
     *
     * @param float|string|null $grade
     * @param float $max
     * @return string
     */
    public static function grade_label($grade, float $max): string {
        if ($grade === null || $grade === '' || $max <= 0) {
            return '—';
        }
        $grade = (float)$grade;
        $pct = round($grade / $max * 100);
        return $pct . '% (' . format_float($grade, 2) . ' / ' . format_float($max, 2) . ')';
    }
}
