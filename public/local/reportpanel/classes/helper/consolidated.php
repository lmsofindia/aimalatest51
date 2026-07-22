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
 * Builds a single user's consolidated learning report: one row per enrolled course
 * with completion %, completion date, final grade, certificate date and earned
 * course badges. All reads are live (no cache) and scoped to one user.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consolidated {

    /**
     * Build the report for a user.
     *
     * @param int $userid
     * @return array {user: stdClass|null, rows: array, hasrows: bool}
     */
    public static function build(int $userid): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $user = \core_user::get_user($userid);
        if (!$user || $user->deleted) {
            return ['user' => null, 'rows' => [], 'hasrows' => false];
        }

        // cacherev is required by get_fast_modinfo() inside core_completion\progress.
        $courses = enrol_get_users_courses($userid, true,
            'id, fullname, shortname, enablecompletion, visible, cacherev');
        $hascustomcert = catalogue::mod_installed('customcert');

        // Course-level final grades for this user, keyed by course id. Matches the
        // raw grade_items/grade_grades pattern used elsewhere in the codebase
        // (local_edzteams, local_edzperformance) rather than grade_get_course_grade().
        $grademap = self::course_grades($userid);

        $dateformat = get_string('strftimedate', 'langconfig');

        // Chart accumulators: completion-status split + grade % per course.
        $statuscount = ['completed' => 0, 'inprogress' => 0, 'notstarted' => 0];
        $gradelabels = [];
        $gradevalues = [];

        $rows = [];
        foreach ($courses as $course) {
            if ((int)$course->id === SITEID) {
                continue;
            }

            // Completion percentage (only when completion is enabled site- and course-wide).
            $pct = null;
            $cinfo = new \completion_info($course);
            if ($cinfo->is_enabled()) {
                $pct = \core_completion\progress::get_course_progress_percentage($course, $userid);
            }
            $completionpct = ($pct === null) ? '—' : round($pct) . '%';

            // Completion date.
            $completiondate = '—';
            $timecompleted = self::course_completion_time($userid, (int)$course->id);
            if ($timecompleted) {
                $completiondate = userdate($timecompleted, $dateformat);
            }

            // Final course grade.
            $gradelabel = '—';
            $gradepct = null;
            if (isset($grademap[(int)$course->id])) {
                $g = $grademap[(int)$course->id];
                $max = (float)$g->grademax;
                if ($max > 0) {
                    $gradepct = (int)round((float)$g->finalgrade / $max * 100);
                    $gradelabel = $gradepct . '% (' . format_float((float)$g->finalgrade, 2) .
                        ' / ' . format_float($max, 2) . ')';
                } else {
                    $gradelabel = format_float((float)$g->finalgrade, 2);
                }
            }

            // Certificate date (latest issued for any customcert in this course).
            $certdate = '—';
            if ($hascustomcert) {
                $ts = self::latest_cert_time($userid, (int)$course->id);
                if ($ts) {
                    $certdate = userdate($ts, $dateformat);
                }
            }

            // Course badges earned.
            $badges = self::course_badges($userid, (int)$course->id);

            // Completion-status split for the doughnut.
            if ($timecompleted || ($pct !== null && round($pct) >= 100)) {
                $statuscount['completed']++;
            } else if (($pct !== null && $pct > 0) || $gradepct !== null) {
                $statuscount['inprogress']++;
            } else {
                $statuscount['notstarted']++;
            }

            // Grade % per course for the bar chart (only graded courses).
            if ($gradepct !== null) {
                $gradelabels[] = format_string($course->shortname);
                $gradevalues[] = $gradepct;
            }

            $cid = (int)$course->id;
            $rows[] = [
                'coursename' => format_string($course->fullname),
                'courseurl' => (new \moodle_url('/course/view.php', ['id' => $cid]))->out(false),
                'completionpct' => $completionpct,
                'hascompletionpct' => ($pct !== null),
                'completionurl' => (new \moodle_url('/report/completion/index.php',
                    ['course' => $cid]))->out(false),
                'completiondate' => $completiondate,
                'grade' => $gradelabel,
                'hasgrade' => ($gradepct !== null),
                'gradeurl' => (new \moodle_url('/grade/report/user/index.php',
                    ['id' => $cid, 'userid' => $userid]))->out(false),
                'certdate' => $certdate,
                'badges' => $badges !== '' ? $badges : '—',
            ];
        }

        \core_collator::asort_array_of_arrays_by_key($rows, 'coursename');

        return [
            'user' => $user,
            'rows' => array_values($rows),
            'hasrows' => !empty($rows),
            'summary' => [
                'statuscount' => $statuscount,
                'gradelabels' => $gradelabels,
                'gradevalues' => $gradevalues,
            ],
        ];
    }

    /**
     * Course-level final grades for a user, keyed by course id. Reads the raw
     * gradebook (itemtype = 'course'), matching the codebase convention.
     *
     * @param int $userid
     * @return array courseid => {finalgrade, grademax}
     */
    protected static function course_grades(int $userid): array {
        global $DB;
        $sql = "SELECT gi.courseid, gg.finalgrade, gi.grademax
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = :userid
                 WHERE gi.itemtype = 'course' AND gi.hidden = 0
                       AND gg.finalgrade IS NOT NULL";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Course completion timestamp for a user, or 0.
     *
     * @param int $userid
     * @param int $courseid
     * @return int
     */
    protected static function course_completion_time(int $userid, int $courseid): int {
        global $DB;
        $ts = $DB->get_field('course_completions', 'timecompleted',
            ['userid' => $userid, 'course' => $courseid]);
        return $ts ? (int)$ts : 0;
    }

    /**
     * Latest customcert issue timestamp for a user in a course, or 0.
     *
     * @param int $userid
     * @param int $courseid
     * @return int
     */
    protected static function latest_cert_time(int $userid, int $courseid): int {
        global $DB;
        $sql = "SELECT MAX(ci.timecreated)
                  FROM {customcert_issues} ci
                  JOIN {customcert} cc ON cc.id = ci.customcertid
                 WHERE ci.userid = :userid AND cc.course = :courseid";
        $ts = $DB->get_field_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
        return $ts ? (int)$ts : 0;
    }

    /**
     * Comma-separated names of course badges the user has earned.
     *
     * @param int $userid
     * @param int $courseid
     * @return string
     */
    protected static function course_badges(int $userid, int $courseid): string {
        global $DB;
        $sql = "SELECT b.id, b.name
                  FROM {badge} b
                  JOIN {badge_issued} bi ON bi.badgeid = b.id AND bi.userid = :userid
                 WHERE b.courseid = :courseid
              ORDER BY b.name ASC";
        $records = $DB->get_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
        $names = [];
        foreach ($records as $b) {
            $names[] = format_string($b->name);
        }
        return implode(', ', $names);
    }
}
