<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the admin/manager "All Faculty" oversight table — every teacher with
 * their course count, students, to-grade, unanswered questions and at-risk totals.
 *
 * Efficient: a handful of grouped queries over ALL teaching courses at once,
 * then summed per teacher (not per-teacher queries).
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview {

    /**
     * Build the overview model.
     *
     * @return array
     */
    public function get_overview(): array {
        global $DB;

        // teacher -> [courseids] from role assignments in course contexts.
        $sql = "SELECT ra.id, ra.userid, ctx.instanceid AS courseid
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :courselevel
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE r.archetype IN ('editingteacher', 'teacher')";
        $pairs = $DB->get_records_sql($sql, ['courselevel' => CONTEXT_COURSE]);

        $byteacher = [];
        $allcourses = [];
        foreach ($pairs as $p) {
            if ((int)$p->courseid == SITEID) {
                continue;
            }
            $byteacher[(int)$p->userid][(int)$p->courseid] = (int)$p->courseid;
            $allcourses[(int)$p->courseid] = true;
        }
        if (!$byteacher) {
            return ['rows' => [], 'hasrows' => false, 'summary' => $this->empty_summary(),
                    'labels' => terminology::labels()];
        }

        $allcourseids = array_keys($allcourses);

        // Per-course aggregates computed once.
        $pendingbycourse = grading::pending_by_course(grading::assign_queue($allcourseids));
        $discbycourse    = discussions::count_by_course(discussions::unanswered($allcourseids));
        $atriskbycourse  = $this->atrisk_by_course($allcourseids);
        $studentsbycourse = $this->students_by_course($allcourseids);

        $users = $DB->get_records_list('user', 'id', array_keys($byteacher),
            '', 'id, firstname, lastname');

        $rows = [];
        $totaltograde = 0;
        $totalatrisk = 0;
        foreach ($byteacher as $uid => $courseids) {
            if (!isset($users[$uid])) {
                continue;
            }
            $u = $users[$uid];
            $tograde = 0;
            $unanswered = 0;
            $atrisk = 0;
            $students = 0;
            foreach ($courseids as $cid) {
                $tograde    += $pendingbycourse[$cid] ?? 0;
                $unanswered += $discbycourse[$cid] ?? 0;
                $atrisk     += $atriskbycourse[$cid] ?? 0;
                $students   += $studentsbycourse[$cid] ?? 0;
            }
            $totaltograde += $tograde;
            $totalatrisk  += $atrisk;
            $rows[] = [
                'teacherid'  => (int)$uid,
                'fullname'   => fullname($u),
                'initials'   => strtoupper(mb_substr($u->firstname, 0, 1) . mb_substr($u->lastname, 0, 1)),
                'courses'    => count($courseids),
                'students'   => $students,
                'tograde'    => $tograde,
                'unanswered' => $unanswered,
                'atrisk'     => $atrisk,
                'hasatrisk'  => $atrisk > 0,
                'url'        => (new moodle_url('/local/edzfaculty/index.php', ['teacherid' => $uid]))->out(false),
            ];
        }

        // Most-needing-attention first.
        usort($rows, function($a, $b) {
            return [$b['atrisk'], $b['tograde']] <=> [$a['atrisk'], $a['tograde']];
        });

        return [
            'rows'    => $rows,
            'hasrows' => !empty($rows),
            'summary' => [
                'faculty' => count($rows),
                'tograde' => $totaltograde,
                'atrisk'  => $totalatrisk,
            ],
            'labels'  => terminology::labels(),
        ];
    }

    /**
     * At-risk count per course from the course cache.
     *
     * @param int[] $courseids
     * @return array [courseid => int]
     */
    protected function atrisk_by_course(array $courseids): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $rows = $DB->get_records_select('local_edzfaculty_coursecache',
            "courseid $insql", $params, '', 'courseid, atriskcount');
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->courseid] = (int)$r->atriskcount;
        }
        return $out;
    }

    /**
     * Student count per course (rows in the per-student cache).
     *
     * @param int[] $courseids
     * @return array [courseid => int]
     */
    protected function students_by_course(array $courseids): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT courseid, COUNT(*) AS cnt
                  FROM {local_edzfaculty_cache}
                 WHERE courseid $insql
              GROUP BY courseid";
        $out = [];
        foreach ($DB->get_records_sql($sql, $params) as $r) {
            $out[(int)$r->courseid] = (int)$r->cnt;
        }
        return $out;
    }

    /**
     * @return array
     */
    protected function empty_summary(): array {
        return ['faculty' => 0, 'tograde' => 0, 'atrisk' => 0];
    }
}
