<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\task;

use local_edzfaculty\helper\atrisk;
use local_edzfaculty\helper\courses;
use local_edzfaculty\helper\zoom_attendance;

defined('MOODLE_INTERNAL') || die();

/**
 * Rebuilds the per-student and per-course analytics caches.
 * Runs under CLI/cron — no $USER/$PAGE/$OUTPUT (Vidya lesson #9).
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_cache extends \core\task\scheduled_task {

    /**
     * @return string
     */
    public function get_name(): string {
        return get_string('task_refresh_cache', 'local_edzfaculty');
    }

    /**
     * Optionally limit the rebuild to a set of courses (used by the manual AJAX trigger).
     *
     * @var int[]|null
     */
    protected $onlycourses = null;

    /**
     * @param int[] $courseids
     */
    public function set_courses(array $courseids): void {
        $this->onlycourses = $courseids;
    }

    /**
     * Execute the rebuild.
     */
    public function execute(): void {
        global $DB;

        $days      = (int)(get_config('local_edzfaculty', 'engagementdays') ?: 30);
        $now       = time();
        $winstart  = $now - ($days * DAYSECS);
        $prevstart = $now - (2 * $days * DAYSECS);
        $cfg       = atrisk::config();
        $hasattend = $DB->get_manager()->table_exists('attendance_log');
        $source    = get_config('local_edzfaculty', 'attendancesource') ?: 'auto';

        if ($this->onlycourses !== null) {
            $courseids = $this->onlycourses;
        } else {
            $courseids = $DB->get_fieldset_select('course', 'id', 'id <> ?', [SITEID]);
        }

        mtrace('local_edzfaculty: rebuilding cache for ' . count($courseids) . ' course(s).');

        foreach ($courseids as $courseid) {
            $students = courses::student_ids($courseid);
            if (!$students) {
                continue;
            }

            // Precompute Zoom attendance for the course once (if used).
            $zoommap = [];
            if (($source === 'zoom' || $source === 'auto') && zoom_attendance::available()) {
                $zoommap = zoom_attendance::pct_by_course($courseid);
            }

            $sumscore = 0;
            $scorecount = 0;
            $activecount = 0;
            $atriskcount = 0;

            foreach ($students as $userid) {
                $m = $this->student_metrics($courseid, $userid, $winstart, $hasattend, $source, $zoommap);
                [$score, $level, $reasons] = atrisk::score($m, $cfg);

                if ($m->avgscore !== null) {
                    $sumscore += $m->avgscore;
                    $scorecount++;
                }
                if ($m->lastaccess >= $winstart) {
                    $activecount++;
                }
                if ($level !== 'ok') {
                    $atriskcount++;
                }

                $this->upsert_cache($courseid, $userid, $m, $score, $level, $reasons, $now);
            }

            $studenttotal = count($students);
            $rollup = (object)[
                'courseid'             => $courseid,
                'activestudentspct'    => $studenttotal ? round(($activecount / $studenttotal) * 100, 2) : 0,
                'contentviews'         => $this->course_views($courseid, $students, $winstart, $now),
                'assessmentstaken'     => $this->course_assessments($courseid, $students, $winstart, $now),
                'avgscore'             => $scorecount ? round($sumscore / $scorecount, 2) : 0,
                'activestudentsprev'   => $this->active_prev($courseid, $students, $prevstart, $winstart),
                'contentviewsprev'     => $this->course_views($courseid, $students, $prevstart, $winstart),
                'assessmentstakenprev' => $this->course_assessments($courseid, $students, $prevstart, $winstart),
                'avgscoreprev'         => $scorecount ? round($sumscore / $scorecount, 2) : 0,
                'atriskcount'          => $atriskcount,
                'contentdeliveredpct'  => courses::content_delivered_pct($courseid),
                'timemodified'         => $now,
            ];
            $this->upsert_coursecache($rollup);
            mtrace("  course $courseid: $studenttotal students, $atriskcount at-risk.");
        }

        mtrace('local_edzfaculty: cache rebuild complete.');
    }

    /**
     * Compute one student's metrics in a course.
     */
    protected function student_metrics(int $courseid, int $userid, int $winstart, bool $hasattend,
            string $source = 'auto', array $zoommap = []): \stdClass {
        global $DB;

        // Last access.
        $lastaccess = (int)$DB->get_field('user_lastaccess', 'timeaccess',
            ['courseid' => $courseid, 'userid' => $userid]) ?: 0;

        // Average score (mod grade items only).
        $avgsql = "SELECT AVG(CASE WHEN gi.grademax > 0 THEN (gg.finalgrade / gi.grademax) * 100 END) AS avgscore
                     FROM {grade_grades} gg
                     JOIN {grade_items} gi ON gi.id = gg.itemid
                    WHERE gi.courseid = :courseid AND gi.itemtype = 'mod'
                      AND gg.userid = :userid AND gg.finalgrade IS NOT NULL";
        $avg = $DB->get_field_sql($avgsql, ['courseid' => $courseid, 'userid' => $userid]);
        $avgscore = ($avg !== null && $avg !== false) ? round((float)$avg, 2) : null;

        // Score trend: avg of last 3 graded items minus the previous 3.
        $scoretrend = $this->score_trend($courseid, $userid);

        // Missed deadlines: assignments past due with no submission.
        $missed = $this->missed_deadlines($courseid, $userid);

        // Forum posts in window.
        $forumposts = (int)$DB->get_field_sql(
            "SELECT COUNT(fp.id)
               FROM {forum_posts} fp
               JOIN {forum_discussions} fd ON fd.id = fp.discussion
              WHERE fd.course = :courseid AND fp.userid = :userid AND fp.created >= :winstart",
            ['courseid' => $courseid, 'userid' => $userid, 'winstart' => $winstart]);

        // Content views in window (logstore — task context only).
        $views = $this->user_views($courseid, $userid, $winstart, time());

        // Attendance — source-driven (mod_attendance, Zoom participant reports, or off).
        $attendance = null;
        if ($source !== 'off') {
            if (($source === 'attendance' || $source === 'auto') && $hasattend) {
                $attendance = $this->attendance_pct($courseid, $userid);
            }
            if ($attendance === null && ($source === 'zoom' || $source === 'auto')
                    && array_key_exists($userid, $zoommap)) {
                $attendance = $zoommap[$userid];
            }
        }

        return (object)[
            'attendancepct'   => $attendance,
            'avgscore'        => $avgscore,
            'scoretrend'      => $scoretrend,
            'lastaccess'      => $lastaccess,
            'misseddeadlines' => $missed,
            'forumposts'      => $forumposts,
            'contentviews'    => $views,
        ];
    }

    /**
     * Score trend = avg(last 3 graded) - avg(previous 3 graded), as a percentage delta.
     */
    protected function score_trend(int $courseid, int $userid): ?float {
        global $DB;
        $sql = "SELECT gg.id, (gg.finalgrade / gi.grademax) * 100 AS pct
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                 WHERE gi.courseid = :courseid AND gi.itemtype = 'mod'
                   AND gg.userid = :userid AND gg.finalgrade IS NOT NULL AND gi.grademax > 0
              ORDER BY gg.timemodified DESC";
        $rows = array_values($DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid], 0, 6));
        if (count($rows) < 4) {
            return null;
        }
        $recent = array_slice($rows, 0, 3);
        $older  = array_slice($rows, 3, 3);
        $avg = fn($a) => array_sum(array_map(fn($r) => $r->pct, $a)) / max(1, count($a));
        return round($avg($recent) - $avg($older), 2);
    }

    /**
     * Assignments in a course past due with no submission by the student.
     */
    protected function missed_deadlines(int $courseid, int $userid): int {
        global $DB;
        $sql = "SELECT COUNT(a.id)
                  FROM {assign} a
             LEFT JOIN {assign_submission} s
                    ON s.assignment = a.id AND s.userid = :userid AND s.latest = 1 AND s.status = 'submitted'
                 WHERE a.course = :courseid AND a.duedate > 0 AND a.duedate < :now
                   AND s.id IS NULL";
        return (int)$DB->get_field_sql($sql,
            ['courseid' => $courseid, 'userid' => $userid, 'now' => time()]);
    }

    /**
     * Attendance percentage from mod_attendance (if present).
     */
    protected function attendance_pct(int $courseid, int $userid): ?float {
        global $DB;
        $sql = "SELECT AVG(CASE WHEN st.acronym IN ('P','L') THEN 100 ELSE 0 END) AS pct
                  FROM {attendance_log} al
                  JOIN {attendance_sessions} ses ON ses.id = al.sessionid
                  JOIN {attendance} att ON att.id = ses.attendanceid
                  JOIN {attendance_statuses} st ON st.id = al.statusid
                 WHERE att.course = :courseid AND al.studentid = :userid";
        $pct = $DB->get_field_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
        return ($pct !== null && $pct !== false) ? round((float)$pct, 2) : null;
    }

    /**
     * Log 'viewed' events for one user in a course within a window.
     */
    protected function user_views(int $courseid, int $userid, int $from, int $to): int {
        global $DB;
        if (!$DB->get_manager()->table_exists('logstore_standard_log')) {
            return 0;
        }
        return (int)$DB->get_field_sql(
            "SELECT COUNT(id) FROM {logstore_standard_log}
              WHERE courseid = :courseid AND userid = :userid
                AND crud = 'r' AND timecreated >= :from AND timecreated < :to",
            ['courseid' => $courseid, 'userid' => $userid, 'from' => $from, 'to' => $to]);
    }

    /**
     * Total course views for a set of students within a window.
     */
    protected function course_views(int $courseid, array $students, int $from, int $to): int {
        global $DB;
        if (!$students || !$DB->get_manager()->table_exists('logstore_standard_log')) {
            return 0;
        }
        [$insql, $params] = $DB->get_in_or_equal($students, SQL_PARAMS_NAMED);
        $params += ['courseid' => $courseid, 'from' => $from, 'to' => $to];
        return (int)$DB->get_field_sql(
            "SELECT COUNT(id) FROM {logstore_standard_log}
              WHERE courseid = :courseid AND crud = 'r'
                AND timecreated >= :from AND timecreated < :to AND userid $insql",
            $params);
    }

    /**
     * Assessment attempts (assign submissions + quiz attempts) in a window.
     */
    protected function course_assessments(int $courseid, array $students, int $from, int $to): int {
        global $DB;
        if (!$students) {
            return 0;
        }
        [$insql, $params] = $DB->get_in_or_equal($students, SQL_PARAMS_NAMED);
        $params += ['courseid' => $courseid, 'from' => $from, 'to' => $to];
        $assign = (int)$DB->get_field_sql(
            "SELECT COUNT(s.id)
               FROM {assign_submission} s JOIN {assign} a ON a.id = s.assignment
              WHERE a.course = :courseid AND s.status = 'submitted'
                AND s.timemodified >= :from AND s.timemodified < :to AND s.userid $insql", $params);
        [$insql2, $params2] = $DB->get_in_or_equal($students, SQL_PARAMS_NAMED);
        $params2 += ['courseid' => $courseid, 'from' => $from, 'to' => $to];
        $quiz = (int)$DB->get_field_sql(
            "SELECT COUNT(qa.id)
               FROM {quiz_attempts} qa JOIN {quiz} q ON q.id = qa.quiz
              WHERE q.course = :courseid AND qa.state = 'finished'
                AND qa.timefinish >= :from AND qa.timefinish < :to AND qa.userid $insql2", $params2);
        return $assign + $quiz;
    }

    /**
     * Previous-period active-student percentage.
     */
    protected function active_prev(int $courseid, array $students, int $from, int $to): float {
        global $DB;
        if (!$students) {
            return 0;
        }
        [$insql, $params] = $DB->get_in_or_equal($students, SQL_PARAMS_NAMED);
        $params += ['courseid' => $courseid, 'from' => $from, 'to' => $to];
        $active = (int)$DB->get_field_sql(
            "SELECT COUNT(DISTINCT userid) FROM {logstore_standard_log}
              WHERE courseid = :courseid AND timecreated >= :from AND timecreated < :to AND userid $insql",
            $params);
        return count($students) ? round(($active / count($students)) * 100, 2) : 0;
    }

    /**
     * Insert or update a per-student cache row.
     */
    protected function upsert_cache(int $courseid, int $userid, \stdClass $m, float $score, string $level, array $reasons, int $now): void {
        global $DB;
        $record = (object)[
            'courseid'        => $courseid,
            'userid'          => $userid,
            'attendancepct'   => $m->attendancepct,
            'avgscore'        => $m->avgscore,
            'scoretrend'      => $m->scoretrend,
            'lastaccess'      => $m->lastaccess,
            'misseddeadlines' => $m->misseddeadlines,
            'forumposts'      => $m->forumposts,
            'contentviews'    => $m->contentviews,
            'riskscore'       => $score,
            'risklevel'       => $level,
            'riskreasons'     => json_encode($reasons),
            'timemodified'    => $now,
        ];
        $existing = $DB->get_record('local_edzfaculty_cache',
            ['courseid' => $courseid, 'userid' => $userid], 'id');
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_edzfaculty_cache', $record);
        } else {
            $DB->insert_record('local_edzfaculty_cache', $record);
        }
    }

    /**
     * Insert or update a per-course rollup row.
     */
    protected function upsert_coursecache(\stdClass $rollup): void {
        global $DB;
        $existing = $DB->get_record('local_edzfaculty_coursecache',
            ['courseid' => $rollup->courseid], 'id');
        if ($existing) {
            $rollup->id = $existing->id;
            $DB->update_record('local_edzfaculty_coursecache', $rollup);
        } else {
            $DB->insert_record('local_edzfaculty_coursecache', $rollup);
        }
    }
}
