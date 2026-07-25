<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Assembles the detailed per-course report model.
 * On-demand page (not the main dashboard), so heavier queries are acceptable;
 * derived analytics still come from the caches where possible.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_report {

    /** @var int */
    protected $courseid;

    /**
     * @param int $courseid
     */
    public function __construct(int $courseid) {
        $this->courseid = $courseid;
    }

    /**
     * @return array
     */
    public function get_report(): array {
        global $DB;
        $cid = $this->courseid;
        $course = get_course($cid);

        $cache = $DB->get_records('local_edzfaculty_cache', ['courseid' => $cid]);
        $cc    = $DB->get_record('local_edzfaculty_coursecache', ['courseid' => $cid]);
        $students = courses::student_ids($cid);
        $total = count($students);

        $passmark = (int)(get_config('local_edzfaculty', 'minscore') ?: 40);
        $minatt   = (int)(get_config('local_edzfaculty', 'minattendance') ?: 60);

        // KPI aggregates from the per-student cache.
        $scored = 0; $sumscore = 0; $pass = 0; $atrisk = 0;
        $attvals = []; $attbelow = 0;
        foreach ($cache as $r) {
            if ($r->avgscore !== null) {
                $sumscore += $r->avgscore; $scored++;
                if ($r->avgscore >= $passmark) { $pass++; }
            }
            if ($r->risklevel && $r->risklevel !== 'ok') { $atrisk++; }
            if ($r->attendancepct !== null) { $attvals[] = (float)$r->attendancepct; if ($r->attendancepct < $minatt) { $attbelow++; } }
        }
        $avgscore = $scored ? round($sumscore / $scored) : 0;
        $passrate = $scored ? round(($pass / $scored) * 100) : 0;
        $attavg   = $attvals ? round(array_sum($attvals) / count($attvals)) : 0;

        return [
            'courseid'   => $cid,
            'name'       => format_string($course->fullname),
            'code'       => $this->code_line($course),
            'backurl'    => (new moodle_url('/local/edzfaculty/index.php'))->out(false),
            'messageurl' => (new moodle_url('/user/index.php', ['id' => $cid]))->out(false),
            'kpi' => [
                'students'  => $total,
                'active'    => $cc ? round((float)$cc->activestudentspct) : 0,
                'avgscore'  => $avgscore,
                'passrate'  => $passrate,
                'delivered' => $cc ? round((float)$cc->contentdeliveredpct) : round(courses::content_delivered_pct($cid)),
                'atrisk'    => $atrisk,
            ],
            'gradedist'   => $this->grade_distribution($cache),
            'hasattend'   => !empty($attvals),
            'attavg'      => $attavg,
            'attbelow'    => $attbelow,
            'attoffset'   => 100 - $attavg,
            'assessments' => $this->assessments($cid),
            'atriskrows'  => $this->atrisk_rows($cid),
            'trend'       => $this->engagement_trend($cid),
            'liveclasses' => $this->live_classes($cid),
            'roster'      => $this->roster($cid, $cache, $students),
            'labels'      => terminology::labels(),
        ];
    }

    /**
     * Course code / section / semester line.
     */
    protected function code_line(\stdClass $course): string {
        $bits = [];
        if (!empty($course->idnumber)) { $bits[] = $course->idnumber; }
        $bits[] = format_string($course->shortname);
        return implode(' · ', $bits);
    }

    /**
     * Grade distribution buckets from cached average scores.
     */
    protected function grade_distribution(array $cache): array {
        $bands = [
            ['label' => '90-100', 'min' => 90, 'cls' => ''],
            ['label' => '75-89',  'min' => 75, 'cls' => ''],
            ['label' => '60-74',  'min' => 60, 'cls' => ''],
            ['label' => '40-59',  'min' => 40, 'cls' => 'd'],
            ['label' => '<40',    'min' => 0,  'cls' => 'f'],
        ];
        $counts = array_fill(0, 5, 0);
        foreach ($cache as $r) {
            if ($r->avgscore === null) { continue; }
            $v = (float)$r->avgscore;
            foreach ($bands as $i => $b) {
                if ($v >= $b['min']) { $counts[$i]++; break; }
            }
        }
        $max = max(1, max($counts));
        $out = [];
        foreach ($bands as $i => $b) {
            $out[] = [
                'label'  => $b['label'],
                'count'  => $counts[$i],
                'height' => round(($counts[$i] / $max) * 100),
                'cls'    => $b['cls'],
            ];
        }
        return $out;
    }

    /**
     * Assessment status: assignments and quizzes with submitted / graded / pending / avg / due.
     */
    protected function assessments(int $cid): array {
        global $DB;
        $rows = [];
        $dm = $DB->get_manager();

        // Averages from the gradebook (one query per module type).
        $assignavg = $this->module_averages($cid, 'assign');
        $quizavg   = $this->module_averages($cid, 'quiz');

        if ($dm->table_exists('assign')) {
            $sql = "SELECT a.id, a.name, a.duedate,
                           (SELECT COUNT(*) FROM {assign_submission} s
                             WHERE s.assignment = a.id AND s.latest = 1 AND s.status = 'submitted') AS submitted,
                           (SELECT COUNT(*) FROM {assign_grades} g
                             WHERE g.assignment = a.id AND g.grade IS NOT NULL AND g.grade >= 0) AS graded
                      FROM {assign} a WHERE a.course = :cid ORDER BY a.duedate DESC";
            foreach ($DB->get_records_sql($sql, ['cid' => $cid]) as $a) {
                $pending = max(0, (int)$a->submitted - (int)$a->graded);
                $rows[] = $this->assessment_row(format_string($a->name), (int)$a->submitted,
                    (int)$a->graded, $pending, $assignavg[$a->id] ?? null, (int)$a->duedate);
            }
        }
        if ($dm->table_exists('quiz')) {
            $sql = "SELECT q.id, q.name, q.timeclose,
                           (SELECT COUNT(DISTINCT qa.userid) FROM {quiz_attempts} qa
                             WHERE qa.quiz = q.id AND qa.state = 'finished') AS submitted,
                           (SELECT COUNT(DISTINCT qa.userid) FROM {quiz_attempts} qa
                             WHERE qa.quiz = q.id AND qa.state = 'finished' AND qa.sumgrades IS NOT NULL) AS graded
                      FROM {quiz} q WHERE q.course = :cid ORDER BY q.timeclose DESC";
            foreach ($DB->get_records_sql($sql, ['cid' => $cid]) as $q) {
                $pending = max(0, (int)$q->submitted - (int)$q->graded);
                $rows[] = $this->assessment_row(format_string($q->name), (int)$q->submitted,
                    (int)$q->graded, $pending, $quizavg[$q->id] ?? null, (int)$q->timeclose);
            }
        }
        return $rows;
    }

    /**
     * Shape one assessment row.
     */
    protected function assessment_row(string $name, int $sub, int $graded, int $pending, $avg, int $due): array {
        return [
            'name'      => $name,
            'submitted' => $sub,
            'graded'    => $graded,
            'pending'   => $pending,
            'haspending' => $pending > 0,
            'avg'       => $avg !== null ? round($avg) . '%' : '—',
            'due'       => $due ? userdate($due, get_string('strftimedateshort', 'langconfig')) : '—',
        ];
    }

    /**
     * Average grade percentage per module instance from the gradebook.
     *
     * @return array [instanceid => float]
     */
    protected function module_averages(int $cid, string $module): array {
        global $DB;
        $sql = "SELECT gi.iteminstance AS iid,
                       AVG(CASE WHEN gi.grademax > 0 THEN (gg.finalgrade / gi.grademax) * 100 END) AS avg
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.courseid = :cid AND gi.itemmodule = :mod AND gg.finalgrade IS NOT NULL
              GROUP BY gi.iteminstance";
        $out = [];
        foreach ($DB->get_records_sql($sql, ['cid' => $cid, 'mod' => $module]) as $r) {
            $out[(int)$r->iid] = (float)$r->avg;
        }
        return $out;
    }

    /**
     * At-risk students for this course.
     */
    protected function atrisk_rows(int $cid): array {
        global $DB;
        $sql = "SELECT c.userid, c.attendancepct, c.avgscore, c.lastaccess, c.risklevel, c.riskreasons,
                       u.firstname, u.lastname
                  FROM {local_edzfaculty_cache} c
                  JOIN {user} u ON u.id = c.userid
                 WHERE c.courseid = :cid AND c.risklevel IN ('watch','critical')
              ORDER BY c.riskscore DESC";
        $out = [];
        foreach ($DB->get_records_sql($sql, ['cid' => $cid], 0, 15) as $r) {
            $reasons = json_decode($r->riskreasons ?? '[]') ?: [];
            $out[] = [
                'userid'     => (int)$r->userid,
                'courseid'   => $cid,
                'fullname'   => fullname($r),
                'initials'   => strtoupper(mb_substr($r->firstname, 0, 1) . mb_substr($r->lastname, 0, 1)),
                'attendance' => $r->attendancepct !== null ? round($r->attendancepct) : '—',
                'avgscore'   => $r->avgscore !== null ? round($r->avgscore) : '—',
                'lastaccess' => $r->lastaccess ? format_time(time() - $r->lastaccess) : '—',
                'reasons'    => implode(', ', (array)$reasons),
            ];
        }
        return $out;
    }

    /**
     * Weekly active-student counts for the last 8 weeks → an SVG polyline.
     */
    protected function engagement_trend(int $cid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('logstore_standard_log')) {
            return ['has' => false];
        }
        $now = time();
        $vals = [];
        for ($w = 7; $w >= 0; $w--) {
            $from = $now - (($w + 1) * WEEKSECS);
            $to   = $now - ($w * WEEKSECS);
            $vals[] = (int)$DB->get_field_sql(
                "SELECT COUNT(DISTINCT userid) FROM {logstore_standard_log}
                  WHERE courseid = :cid AND timecreated >= :from AND timecreated < :to",
                ['cid' => $cid, 'from' => $from, 'to' => $to]);
        }
        $max = max(1, max($vals));
        $n = count($vals);
        $pts = [];
        foreach ($vals as $i => $v) {
            $x = round(($i / max(1, $n - 1)) * 320);
            $y = round(80 - ($v / $max) * 72);
            $pts[] = $x . ',' . $y;
        }
        $line = implode(' ', $pts);
        return [
            'has'   => true,
            'line'  => $line,
            'area'  => $line . ' 320,90 0,90',
        ];
    }

    /**
     * mod_zoom live classes in this course (with view links).
     */
    protected function live_classes(int $cid): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('zoom')) {
            return [];
        }
        $out = [];
        foreach ($DB->get_records('zoom', ['course' => $cid], 'start_time DESC', 'id, name, start_time', 0, 8) as $z) {
            $cm = get_coursemodule_from_instance('zoom', $z->id, $cid, false, IGNORE_MISSING);
            $out[] = [
                'name' => format_string($z->name),
                'when' => $z->start_time ? userdate($z->start_time, get_string('strftimedateshort', 'langconfig')) : '',
                'url'  => $cm ? (new moodle_url('/mod/zoom/view.php', ['id' => $cm->id]))->out(false) : '',
            ];
        }
        return $out;
    }

    /**
     * Full class roster with per-student metrics.
     */
    protected function roster(int $cid, array $cache, array $students): array {
        global $DB;
        // Submitted-assignment counts per student.
        $subs = [];
        if ($DB->get_manager()->table_exists('assign') && $students) {
            [$insql, $params] = $DB->get_in_or_equal($students, SQL_PARAMS_NAMED);
            $params['cid'] = $cid;
            $sql = "SELECT s.userid, COUNT(DISTINCT s.assignment) AS n
                      FROM {assign_submission} s JOIN {assign} a ON a.id = s.assignment
                     WHERE a.course = :cid AND s.latest = 1 AND s.status = 'submitted' AND s.userid $insql
                  GROUP BY s.userid";
            foreach ($DB->get_records_sql($sql, $params) as $r) {
                $subs[(int)$r->userid] = (int)$r->n;
            }
        }
        $totalassign = $DB->get_field('assign', 'COUNT(id)', ['course' => $cid]) ?: 0;

        $cachebyuser = [];
        foreach ($cache as $r) { $cachebyuser[(int)$r->userid] = $r; }

        $users = $students ? $DB->get_records_list('user', 'id', $students, '', 'id, firstname, lastname') : [];
        $rows = [];
        foreach ($users as $u) {
            $m = $cachebyuser[$u->id] ?? null;
            $level = $m && $m->risklevel ? $m->risklevel : 'ok';
            $rows[] = [
                'fullname'   => fullname($u),
                'initials'   => strtoupper(mb_substr($u->firstname, 0, 1) . mb_substr($u->lastname, 0, 1)),
                'attendance' => ($m && $m->attendancepct !== null) ? round($m->attendancepct) . '%' : '—',
                'avgscore'   => ($m && $m->avgscore !== null) ? round($m->avgscore) : '—',
                'subs'       => ($subs[$u->id] ?? 0) . '/' . $totalassign,
                'lastaccess' => ($m && $m->lastaccess) ? format_time(time() - $m->lastaccess) : '—',
                'level'      => $level,
                'badgecls'   => $level === 'critical' ? 'crit' : $level,
                'levellabel' => get_string('risk_' . $level, 'local_edzfaculty'),
                'sortrisk'   => $level === 'critical' ? 0 : ($level === 'watch' ? 1 : 2),
            ];
        }
        usort($rows, fn($a, $b) => [$a['sortrisk'], $a['fullname']] <=> [$b['sortrisk'], $b['fullname']]);
        return $rows;
    }
}
