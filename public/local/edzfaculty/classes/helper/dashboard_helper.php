<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Facade that assembles the full faculty dashboard model for the renderer.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_helper {

    /** @var int */
    protected $teacherid;

    /**
     * @param int $teacherid
     */
    public function __construct(int $teacherid) {
        $this->teacherid = $teacherid;
    }

    /**
     * Build the complete dashboard model.
     *
     * @return array
     */
    public function get_dashboard(): array {
        global $DB;

        $courses   = courses::teaching_courses($this->teacherid);
        $courseids = array_keys($courses);
        $labels    = terminology::labels();
        $showai    = (bool)(get_config('local_edzfaculty', 'showai') ?? 1);

        // --- Grading, discussions, timeline (cross-course) ---
        $queue           = grading::assign_queue($courseids);
        $pendingtotal    = grading::total_pending($queue);
        $pendingbycourse = grading::pending_by_course($queue);
        $quizbycourse    = grading::quiz_pending_by_course($courseids);

        $unanswered   = discussions::unanswered($courseids);
        $discbycourse = discussions::count_by_course($unanswered);

        $events       = timeline::upcoming($this->teacherid, $courseids, 14);
        $livebycourse = timeline::liveclass_count_by_course($events);

        // --- Per-course cards + focus/engagement maps ---
        $coursecards  = [];
        $focusmap     = [];   // courseid => focus stats + quick-action urls (for instant JS switching).
        $engagemap    = [];   // courseid => engagement metrics.
        $courseoptions = [];
        $chartlabels  = [];
        $chartvalues  = [];

        foreach ($courses as $c) {
            $cid  = (int)$c->id;
            $cc   = $DB->get_record('local_edzfaculty_coursecache', ['courseid' => $cid]);
            $atrisk = $DB->count_records_select('local_edzfaculty_cache',
                "courseid = :cid AND risklevel IN ('watch','critical')", ['cid' => $cid]);
            $studentcount = count(courses::student_ids($cid));
            $delivered = $cc ? (float)$cc->contentdeliveredpct : courses::content_delivered_pct($cid);
            $avg = $cc ? round((float)$cc->avgscore) : 0;

            $coursecards[] = [
                'id'        => $cid,
                'name'      => format_string($c->fullname),
                'shortname' => format_string($c->shortname),
                'students'  => $studentcount,
                'avgscore'  => $avg,
                'atrisk'    => (int)$atrisk,
                'delivered' => $delivered,
                'url'       => (new moodle_url('/local/edzfaculty/course_report.php', ['id' => $cid]))->out(false),
            ];

            $focusmap[$cid] = $this->focus_stats(
                $cid,
                $c,
                $livebycourse[$cid] ?? 0,
                $pendingbycourse[$cid] ?? 0,
                $discbycourse[$cid] ?? 0,
                $quizbycourse[$cid] ?? 0
            );

            $engagemap[$cid] = engagement::for_course($cid);

            $courseoptions[] = [
                'id'    => $cid,
                'label' => format_string($c->shortname) . ' · ' . format_string($c->fullname),
            ];

            $chartlabels[] = format_string($c->shortname);
            $chartvalues[] = $avg;
        }

        // Section performance horizontal bars (colour-coded).
        $sectionbars = [];
        foreach ($chartlabels as $i => $lab) {
            $pct = (int)$chartvalues[$i];
            $sectionbars[] = [
                'name' => $lab,
                'pct'  => $pct,
                'cls'  => $pct < 65 ? 'bad' : ($pct < 72 ? 'low' : ''),
            ];
        }

        // "All courses" focus + engagement aggregates.
        $focusall = [
            'live'   => array_sum($livebycourse),
            'assign' => $pendingtotal,
            'disc'   => array_sum($discbycourse),
            'quiz'   => array_sum($quizbycourse),
            'label'  => get_string('qa_all', 'local_edzfaculty'),
            'ctx'    => get_string('ctx_all', 'local_edzfaculty', count($courses)),
            'actions' => $this->quick_actions(0, null),
        ];
        $engageall = engagement::for_all($courseids);

        // --- KPIs ---
        $nextclass = null;
        foreach ($events as $e) {
            if ($e->type === 'liveclass') {
                $nextclass = $e;
                break;
            }
        }

        return [
            'labels'        => $labels,
            'showai'        => $showai,
            'teachername'   => fullname(\core_user::get_user($this->teacherid)),
            // Zone 0.
            'focusall'      => $focusall,
            'focusmap'      => $focusmap,
            'courseoptions' => $courseoptions,
            // Zone 1.
            'kpi'           => [
                'tograde'    => $pendingtotal,
                'unanswered' => count($unanswered),
                'atrisk'     => $this->atrisk_total($courseids),
                'nextclass'  => $nextclass ? [
                    'name' => $nextclass->name,
                    'time' => userdate($nextclass->timestart, get_string('strftimetime', 'langconfig')),
                    'url'  => $nextclass->joinurl,
                ] : null,
                'nextclasssoon' => $nextclass && abs($nextclass->timestart - time()) <= 3600,
            ],
            'grading'       => $this->grading_rows($queue, $courses),
            'discussions'   => $this->discussion_rows($unanswered, $courses),
            'atriskrows'    => $this->atrisk_rows($courseids, $courses),
            'overdue'       => $this->overdue_rows($courseids, $courses),
            'timeline'      => $this->timeline_rows($events, $courses),
            // Zone 2.
            'courses'       => $coursecards,
            // Zone 3.
            'chart'         => ['labels' => $chartlabels, 'values' => $chartvalues],
            'sectionbars'   => $sectionbars,
            'engageall'     => $engageall,
            'engagemap'     => $engagemap,
            'lastupdated'   => $this->last_updated(),
        ];
    }

    /**
     * Focus stats + quick-action URLs for one course.
     */
    protected function focus_stats(int $cid, \stdClass $course, int $live, int $assign, int $disc, int $quiz): array {
        return [
            'live'    => $live,
            'assign'  => $assign,
            'disc'    => $disc,
            'quiz'    => $quiz,
            'label'   => get_string('qa_course', 'local_edzfaculty', format_string($course->shortname)),
            'ctx'     => format_string($course->fullname) . ' · ' . format_string($course->shortname),
            'actions' => $this->quick_actions($cid, $course),
        ];
    }

    /**
     * Deep-link URLs for the quick-action buttons.
     */
    protected function quick_actions(int $cid, ?\stdClass $course): array {
        if (!$cid) {
            $u = (new moodle_url('/my/'))->out(false);
            return [
                'review' => $u, 'attendance' => $u, 'discuss' => $u,
                'upload' => $u, 'schedule' => $u, 'assess' => $u,
            ];
        }
        return [
            'review'     => (new moodle_url('/mod/assign/index.php', ['id' => $cid]))->out(false),
            'attendance' => (new moodle_url('/mod/attendance/index.php', ['id' => $cid]))->out(false),
            'discuss'    => (new moodle_url('/mod/forum/index.php', ['id' => $cid]))->out(false),
            'upload'     => (new moodle_url('/course/view.php', ['id' => $cid]))->out(false),
            'schedule'   => (new moodle_url('/course/view.php', ['id' => $cid]))->out(false),
            'assess'     => (new moodle_url('/mod/quiz/index.php', ['id' => $cid]))->out(false),
        ];
    }

    /**
     * Total at-risk students across courses.
     */
    protected function atrisk_total(array $courseids): int {
        global $DB;
        if (!$courseids) {
            return 0;
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        return $DB->count_records_select('local_edzfaculty_cache',
            "courseid $insql AND risklevel IN ('watch','critical')", $params);
    }

    /**
     * Grading worklist rows (oldest first).
     */
    protected function grading_rows(array $queue, array $courses): array {
        $rows = [];
        foreach ($queue as $q) {
            [$age, $sev] = self::age((int)$q->oldest);
            $c = $courses[$q->course] ?? null;
            $rows[] = [
                'name'     => format_string($q->name),
                'section'  => $c ? format_string($c->shortname) : '',
                'pending'  => (int)$q->pending,
                'age'      => $age,
                'sev'      => $sev,
                'url'      => (new moodle_url('/mod/assign/view.php',
                                ['id' => 0, 'a' => $q->assignid, 'action' => 'grading']))->out(false),
            ];
        }
        return $rows;
    }

    /**
     * Unanswered discussion rows.
     */
    protected function discussion_rows(array $unanswered, array $courses): array {
        $rows = [];
        foreach ($unanswered as $d) {
            [$age, $sev] = self::age((int)$d->lastcreated);
            $c = $courses[$d->course] ?? null;
            $rows[] = [
                'name'    => format_string($d->name),
                'section' => ($c ? format_string($c->shortname) . ' · ' : '') . format_string($d->forumname),
                'age'     => $age,
                'sev'     => $sev,
                'url'     => (new moodle_url('/mod/forum/discuss.php', ['d' => $d->discussionid]))->out(false),
            ];
        }
        return $rows;
    }

    /**
     * At-risk student rows from the cache.
     */
    protected function atrisk_rows(array $courseids, array $courses): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT c.id, c.courseid, c.userid, c.attendancepct, c.avgscore, c.scoretrend,
                       c.lastaccess, c.misseddeadlines, c.forumposts, c.riskscore, c.risklevel, c.riskreasons,
                       u.firstname, u.lastname
                  FROM {local_edzfaculty_cache} c
                  JOIN {user} u ON u.id = c.userid
                 WHERE c.courseid $insql AND c.risklevel IN ('watch','critical')
              ORDER BY c.riskscore DESC";
        $records = $DB->get_records_sql($sql, $params, 0, 12);

        $rows = [];
        foreach ($records as $r) {
            $c = $courses[$r->courseid] ?? null;
            $reasons = json_decode($r->riskreasons ?? '[]') ?: [];
            $rows[] = [
                'userid'     => (int)$r->userid,
                'courseid'   => (int)$r->courseid,
                'fullname'   => fullname($r),
                'initials'   => strtoupper(mb_substr($r->firstname, 0, 1) . mb_substr($r->lastname, 0, 1)),
                'section'    => $c ? format_string($c->shortname) : '',
                'level'      => $r->risklevel,
                'levellabel' => get_string('risk_' . $r->risklevel, 'local_edzfaculty'),
                'attendance' => $r->attendancepct !== null ? round($r->attendancepct) : '—',
                'avgscore'   => $r->avgscore !== null ? round($r->avgscore) : '—',
                'lastaccess' => $r->lastaccess ? format_time(time() - $r->lastaccess) : '—',
                'missed'     => (int)$r->misseddeadlines,
                'forumposts' => (int)$r->forumposts,
                'trend'      => $r->scoretrend !== null ? round($r->scoretrend) : 0,
                'reasons'    => array_map(fn($x) => ['text' => $x], $reasons),
                'reasonsjson' => json_encode(array_values((array)$reasons)),
                'reasonssummary' => implode(' · ', (array)$reasons),
                'profileurl' => (new moodle_url('/user/view.php',
                                    ['id' => $r->userid, 'course' => $r->courseid]))->out(false),
            ];
        }
        return $rows;
    }

    /**
     * Overdue worklist — assignments past due with students who have not submitted.
     */
    protected function overdue_rows(array $courseids, array $courses): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['now'] = time();
        $sql = "SELECT a.id AS assignid, a.name, a.course, a.duedate,
                       (SELECT COUNT(s.id) FROM {assign_submission} s
                         WHERE s.assignment = a.id AND s.latest = 1 AND s.status = 'submitted') AS submitted
                  FROM {assign} a
                 WHERE a.course $insql AND a.duedate > 0 AND a.duedate < :now
              ORDER BY a.duedate DESC";
        $assigns = $DB->get_records_sql($sql, $params, 0, 20);

        $rows = [];
        foreach ($assigns as $a) {
            $c = $courses[$a->course] ?? null;
            $enrolled = count(courses::student_ids((int)$a->course));
            $nonsub = max(0, $enrolled - (int)$a->submitted);
            if ($nonsub <= 0) {
                continue;
            }
            $rows[] = [
                'name'    => format_string($a->name),
                'section' => $c ? format_string($c->shortname) : '',
                'nonsub'  => $nonsub,
                'due'     => userdate((int)$a->duedate, get_string('strftimedateshort', 'langconfig')),
                'url'     => (new moodle_url('/mod/assign/view.php',
                                ['a' => $a->assignid, 'action' => 'grading']))->out(false),
            ];
        }
        return $rows;
    }

    /**
     * Timeline rows.
     */
    protected function timeline_rows(array $events, array $courses): array {
        $now  = time();
        $rows = [];
        foreach ($events as $e) {
            $c = $courses[$e->courseid] ?? null;
            $soon = ($e->type === 'liveclass' && $e->timestart <= $now + 3600
                     && ($e->timestart + $e->duration) >= $now);
            $rows[] = [
                'name'      => $e->name,
                'section'   => $c ? format_string($c->shortname) : '',
                'date'      => userdate($e->timestart, get_string('strftimedatetimeshort', 'langconfig')),
                'islive'    => $e->type === 'liveclass',
                'isexam'    => $e->type === 'assessment',
                'joinurl'   => $e->joinurl,
                'soon'      => $soon,
                'reltime'   => $soon ? $this->reltime($e->timestart) : '',
            ];
        }
        return $rows;
    }

    /**
     * Human "last updated" string from the newest cache row.
     */
    protected function last_updated(): ?string {
        global $DB;
        $ts = $DB->get_field_sql("SELECT MAX(timemodified) FROM {local_edzfaculty_coursecache}");
        return $ts ? get_string('lastupdated', 'local_edzfaculty', format_time(time() - $ts)) : null;
    }

    /**
     * Relative time label for an imminent event.
     *
     * @param int $ts
     * @return string
     */
    protected function reltime(int $ts): string {
        $d = $ts - time();
        if ($d <= 0) {
            return get_string('livenow', 'local_edzfaculty');
        }
        return get_string('inminutes', 'local_edzfaculty', (int)ceil($d / 60));
    }

    /**
     * Short age label + severity class from a timestamp.
     *
     * @param int $ts
     * @return array [string label, string sev]
     */
    protected static function age(int $ts): array {
        $days = (int)floor((time() - $ts) / DAYSECS);
        if ($days <= 0) {
            return [get_string('today', 'calendar'), 'lo'];
        }
        if ($days >= 3) {
            return [$days . 'd', 'hi'];
        }
        return [$days . 'd', 'md'];
    }
}
