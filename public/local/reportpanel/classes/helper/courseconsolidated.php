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
 * Builds a consolidated COURSE report: one row per enrolled user with completion %,
 * activity-completion count (X / Y), final grade, completion date and status. Also
 * returns summary buckets used to draw the completion-status doughnut and the grade
 * distribution bar chart. Reads are live (no cache) and scoped to a single course;
 * heavy per-user work is done with bulk queries rather than per-user calls.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courseconsolidated {

    /**
     * Build the course report.
     *
     * @param int $courseid
     * @return array {course: stdClass|null, rows: array, hasrows: bool, summary: array}
     */
    public static function build(int $courseid): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course || (int)$course->id === SITEID) {
            return ['course' => null, 'rows' => [], 'hasrows' => false, 'summary' => self::empty_summary()];
        }

        $coursecontext = \context_course::instance($course->id);

        // Enrolled users (active enrolments only). 'u.*' guarantees fullname() has
        // every name field it needs regardless of site fullnamedisplay settings.
        $users = get_enrolled_users($coursecontext, '', 0, 'u.*',
            'u.lastname ASC, u.firstname ASC', 0, 0, true);

        if (empty($users)) {
            return ['course' => $course, 'rows' => [], 'hasrows' => false, 'summary' => self::empty_summary()];
        }

        // Total completion-tracked activities in the course (denominator for X / Y).
        $completion = new \completion_info($course);
        $completionenabled = $completion->is_enabled();
        $totalactivities = 0;
        if ($completionenabled) {
            $totalactivities = count($completion->get_activities());
        }

        // Bulk: completed-activity count per user.
        $completedmap = self::completed_activity_counts((int)$course->id);
        // Bulk: course completion timestamps per user.
        $completionmap = self::completion_times((int)$course->id);
        // Bulk: final course grades per user.
        $grademap = self::course_grades((int)$course->id);

        $dateformat = get_string('strftimedate', 'langconfig');

        // Summary counters for the charts.
        $statuscount = ['completed' => 0, 'inprogress' => 0, 'notstarted' => 0];
        $gradebuckets = self::empty_grade_buckets();
        $gradesum = 0.0;
        $gradecount = 0;

        $rows = [];
        foreach ($users as $u) {
            $uid = (int)$u->id;

            // Activity completion X / Y and derived percentage.
            $done = isset($completedmap[$uid]) ? (int)$completedmap[$uid] : 0;
            if ($completionenabled && $totalactivities > 0) {
                $done = min($done, $totalactivities);
                $pct = (int)round($done / $totalactivities * 100);
                $activitylabel = $done . ' / ' . $totalactivities;
                $completionpct = $pct . '%';
            } else {
                $pct = null;
                $activitylabel = '—';
                $completionpct = '—';
            }

            // Completion date.
            $completiondate = '—';
            $timecompleted = isset($completionmap[$uid]) ? (int)$completionmap[$uid] : 0;
            if ($timecompleted) {
                $completiondate = userdate($timecompleted, $dateformat);
            }

            // Final course grade (percentage of grademax).
            $gradelabel = '—';
            $gradepct = null;
            if (isset($grademap[$uid]) && $grademap[$uid]->finalgrade !== null) {
                $g = $grademap[$uid];
                $max = (float)$g->grademax;
                if ($max > 0) {
                    $gradepct = round((float)$g->finalgrade / $max * 100);
                    $gradelabel = $gradepct . '% (' . format_float((float)$g->finalgrade, 2) .
                        ' / ' . format_float($max, 2) . ')';
                } else {
                    $gradelabel = format_float((float)$g->finalgrade, 2);
                }
            }

            // Status classification for the doughnut.
            if ($timecompleted || ($pct !== null && $pct >= 100)) {
                $status = 'completed';
                $statusbadge = 'success';
            } else if ($done > 0 || $gradepct !== null || ($pct !== null && $pct > 0)) {
                $status = 'inprogress';
                $statusbadge = 'warning';
            } else {
                $status = 'notstarted';
                $statusbadge = 'secondary';
            }
            $statuscount[$status]++;

            // Grade distribution bucketing (only users who have a grade).
            if ($gradepct !== null) {
                self::bucket_grade($gradebuckets, (float)$gradepct);
                $gradesum += (float)$gradepct;
                $gradecount++;
            }

            $cid = (int)$course->id;
            $rows[] = [
                'fullname' => fullname($u),
                'nameurl' => (new \moodle_url('/user/view.php',
                    ['id' => $uid, 'course' => $cid]))->out(false),
                'email' => $u->email,
                'completionpct' => $completionpct,
                'activities' => $activitylabel,
                'hasactivities' => ($activitylabel !== '—'),
                'activitiesurl' => (new \moodle_url('/report/completion/index.php',
                    ['course' => $cid]))->out(false),
                'grade' => $gradelabel,
                'hasgrade' => ($gradepct !== null),
                'gradeurl' => (new \moodle_url('/grade/report/user/index.php',
                    ['id' => $cid, 'userid' => $uid]))->out(false),
                'completiondate' => $completiondate,
                'status' => get_string('status_' . $status, 'local_reportpanel'),
                'statusbadge' => $statusbadge,
                'statuskey' => $status,
            ];
        }

        $totalusers = count($rows);
        $avggrade = $gradecount > 0 ? round($gradesum / $gradecount, 1) : null;
        $completionrate = $totalusers > 0
            ? round($statuscount['completed'] / $totalusers * 100, 1)
            : 0;

        $summary = [
            'totalusers' => $totalusers,
            'completed' => $statuscount['completed'],
            'inprogress' => $statuscount['inprogress'],
            'notstarted' => $statuscount['notstarted'],
            'completionrate' => $completionrate,
            'avggrade' => $avggrade,
            'avggradelabel' => $avggrade === null ? '—' : $avggrade . '%',
            'statuscount' => $statuscount,
            'gradebuckets' => $gradebuckets,
            'totalactivities' => $totalactivities,
        ];

        return [
            'course' => $course,
            'rows' => $rows,
            'hasrows' => !empty($rows),
            'summary' => $summary,
        ];
    }

    /**
     * Empty summary structure (no enrolled users / invalid course).
     *
     * @return array
     */
    protected static function empty_summary(): array {
        return [
            'totalusers' => 0,
            'completed' => 0,
            'inprogress' => 0,
            'notstarted' => 0,
            'completionrate' => 0,
            'avggrade' => null,
            'avggradelabel' => '—',
            'statuscount' => ['completed' => 0, 'inprogress' => 0, 'notstarted' => 0],
            'gradebuckets' => self::empty_grade_buckets(),
            'totalactivities' => 0,
        ];
    }

    /**
     * Ordered, empty grade-distribution buckets.
     *
     * @return array label => count
     */
    protected static function empty_grade_buckets(): array {
        return [
            '0–39' => 0,
            '40–59' => 0,
            '60–74' => 0,
            '75–89' => 0,
            '90–100' => 0,
        ];
    }

    /**
     * Increment the appropriate grade bucket for a 0–100 percentage.
     *
     * @param array $buckets by reference
     * @param float $pct
     * @return void
     */
    protected static function bucket_grade(array &$buckets, float $pct): void {
        if ($pct < 40) {
            $buckets['0–39']++;
        } else if ($pct < 60) {
            $buckets['40–59']++;
        } else if ($pct < 75) {
            $buckets['60–74']++;
        } else if ($pct < 90) {
            $buckets['75–89']++;
        } else {
            $buckets['90–100']++;
        }
    }

    /**
     * Completed-activity count per user for a course (completionstate > 0).
     *
     * @param int $courseid
     * @return array userid => count
     */
    protected static function completed_activity_counts(int $courseid): array {
        global $DB;
        $sql = "SELECT cmc.userid, COUNT(cmc.id) AS completed
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                 WHERE cm.course = :courseid
                   AND cm.completion > 0
                   AND cm.deletioninprogress = 0
                   AND cmc.completionstate > 0
              GROUP BY cmc.userid";
        $out = [];
        foreach ($DB->get_records_sql($sql, ['courseid' => $courseid]) as $rec) {
            $out[(int)$rec->userid] = (int)$rec->completed;
        }
        return $out;
    }

    /**
     * Course-completion timestamps per user, keyed by userid (only where completed).
     *
     * @param int $courseid
     * @return array userid => timecompleted
     */
    protected static function completion_times(int $courseid): array {
        global $DB;
        $sql = "SELECT userid, timecompleted
                  FROM {course_completions}
                 WHERE course = :courseid AND timecompleted IS NOT NULL";
        $out = [];
        foreach ($DB->get_records_sql($sql, ['courseid' => $courseid]) as $rec) {
            $out[(int)$rec->userid] = (int)$rec->timecompleted;
        }
        return $out;
    }

    /**
     * Final course grades per user, keyed by userid. Reads the raw gradebook
     * (itemtype = 'course'), matching the codebase convention.
     *
     * @param int $courseid
     * @return array userid => {finalgrade, grademax}
     */
    protected static function course_grades(int $courseid): array {
        global $DB;
        $sql = "SELECT gg.userid, gg.finalgrade, gi.grademax
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype = 'course'";
        $out = [];
        foreach ($DB->get_records_sql($sql, ['courseid' => $courseid]) as $rec) {
            $out[(int)$rec->userid] = $rec;
        }
        return $out;
    }
}
