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
 * Activity coverage (Domain E): per-activity metrics for a course. For every module in
 * the course it reports, across enrolled learners, the completion split (done / not
 * done / overdue), the average grade (if gradable) and the average time on task (from
 * local_trackmytime). Assignments additionally get submission stats.
 *
 * Extensible by module type: {@see assignment_extra()} shows the pattern — add a
 * `<modname>_extra()` method and branch in {@see build()} to enrich other module types.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activities {

    /**
     * Build the per-activity table for a course.
     *
     * @param int $courseid
     * @return array {hasrows:bool, rows:array}
     */
    public static function build(int $courseid): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');

        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course || (int)$course->id === (int)SITEID) {
            return ['hasrows' => false, 'rows' => []];
        }

        $coursecontext = \context_course::instance($courseid);
        $enrolledids = array_keys(get_enrolled_users($coursecontext, '', 0, 'u.id', null, 0, 0, true));
        $totalusers = count($enrolledids);
        if ($totalusers === 0) {
            return ['hasrows' => false, 'rows' => []];
        }

        $modinfo = get_fast_modinfo($course);
        $completioninfo = new \completion_info($course);

        // Gather visible, non-deleted modules.
        $cms = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->visible || !empty($cm->deletioninprogress)) {
                continue;
            }
            $cms[$cm->id] = $cm;
        }
        if (empty($cms)) {
            return ['hasrows' => false, 'rows' => []];
        }
        $cmids = array_keys($cms);

        $completionmap = self::completion_counts($cmids, $enrolledids);
        $grademap = self::grade_averages($courseid, $enrolledids);
        $timemap = timetracking::module_time_totals($cmids);
        $assignmap = self::assignment_stats($modinfo, $enrolledids);

        $rows = [];
        foreach ($cms as $cmid => $cm) {
            $tracked = ($completioninfo->is_enabled($cm) != COMPLETION_TRACKING_NONE);
            $done = 0;
            $overdue = 0;
            if ($tracked && isset($completionmap[$cmid])) {
                $done = $completionmap[$cmid]['done'];
                $overdue = $completionmap[$cmid]['overdue'];
            }
            $notdone = max(0, $totalusers - $done);

            // Average grade (percentage), if the module has a grade item with grades.
            $gradelabel = '—';
            $gkey = $cm->modname . ':' . $cm->instance;
            if (isset($grademap[$gkey]) && $grademap[$gkey]['graded'] > 0) {
                $gradelabel = round($grademap[$gkey]['avgpct']) . '%';
            }

            // Average time on task across learners with tracked time on this module.
            $timelabel = '—';
            if (!empty($timemap[$cmid])) {
                $avgsecs = (int)round($timemap[$cmid] / max(1, $totalusers));
                $timelabel = timetracking::format_seconds($avgsecs);
            }

            $row = [
                'name' => format_string($cm->name),
                'type' => get_string('modulename', $cm->modname),
                'icon' => $cm->get_icon_url()->out(false),
                'url'  => $cm->url ? $cm->url->out(false) : '',
                'tracked' => $tracked,
                'done' => $done,
                'notdone' => $notdone,
                'overdue' => $overdue,
                'hasoverdue' => $overdue > 0,
                'overduelabel' => (string)$overdue,
                'donelabel' => $tracked ? ($done . ' / ' . $totalusers) : '—',
                'grade' => $gradelabel,
                'time' => $timelabel,
                'extra' => $assignmap[$cmid] ?? '',
            ];
            $rows[] = $row;
        }

        return ['hasrows' => !empty($rows), 'rows' => $rows, 'totalusers' => $totalusers];
    }

    /**
     * Completion done/overdue counts per cmid across the enrolled users.
     * "done" = state complete or complete-pass; "overdue" = expected date passed and
     * the learner has no completion record yet.
     *
     * @param int[] $cmids
     * @param int[] $userids
     * @return array [cmid => {done:int, overdue:int}]
     */
    protected static function completion_counts(array $cmids, array $userids): array {
        global $DB;
        if (empty($cmids) || empty($userids)) {
            return [];
        }
        [$cmsql, $cmparams] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cm');
        [$usql, $uparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');

        $rows = $DB->get_records_sql(
            "SELECT coursemoduleid, completionstate, COUNT(*) AS cnt
               FROM {course_modules_completion}
              WHERE coursemoduleid $cmsql AND userid $usql
           GROUP BY coursemoduleid, completionstate",
            array_merge($cmparams, $uparams)
        );
        $out = [];
        foreach ($rows as $r) {
            $cmid = (int)$r->coursemoduleid;
            if (!isset($out[$cmid])) {
                $out[$cmid] = ['done' => 0, 'overdue' => 0];
            }
            // COMPLETION_COMPLETE = 1, COMPLETION_COMPLETE_PASS = 2.
            if ((int)$r->completionstate === 1 || (int)$r->completionstate === 2) {
                $out[$cmid]['done'] += (int)$r->cnt;
            }
        }

        // Overdue: modules with a past completionexpected, counted as enrolled-minus-done.
        $now = time();
        $expected = $DB->get_records_sql(
            "SELECT id, completionexpected
               FROM {course_modules}
              WHERE id $cmsql AND completionexpected > 0 AND completionexpected < :now",
            array_merge($cmparams, ['now' => $now])
        );
        foreach ($expected as $cmrow) {
            $cmid = (int)$cmrow->id;
            $done = $out[$cmid]['done'] ?? 0;
            $out[$cmid]['overdue'] = max(0, count($userids) - $done);
            if (!isset($out[$cmid]['done'])) {
                $out[$cmid]['done'] = 0;
            }
        }
        return $out;
    }

    /**
     * Average grade percentage + graded count per module grade item.
     *
     * @param int $courseid
     * @param int[] $userids
     * @return array ['<modname>:<instance>' => {avgpct:float, graded:int}]
     */
    protected static function grade_averages(int $courseid, array $userids): array {
        global $DB;
        if (empty($userids)) {
            return [];
        }
        [$usql, $uparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
        $rows = $DB->get_records_sql(
            "SELECT gi.itemmodule, gi.iteminstance,
                    AVG(CASE WHEN gi.grademax > 0 THEN gg.finalgrade / gi.grademax * 100 END) AS avgpct,
                    COUNT(gg.finalgrade) AS graded
               FROM {grade_items} gi
               JOIN {grade_grades} gg ON gg.itemid = gi.id
              WHERE gi.courseid = :courseid
                AND gi.itemtype = 'mod'
                AND gg.finalgrade IS NOT NULL
                AND gg.userid $usql
           GROUP BY gi.itemmodule, gi.iteminstance",
            array_merge(['courseid' => $courseid], $uparams)
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r->itemmodule . ':' . (int)$r->iteminstance] = [
                'avgpct' => (float)$r->avgpct,
                'graded' => (int)$r->graded,
            ];
        }
        return $out;
    }

    /**
     * Assignment submission stats per cmid: "12 submitted · 8 graded · 3 late".
     *
     * @param \course_modinfo $modinfo
     * @param int[] $userids
     * @return array [cmid => string]
     */
    protected static function assignment_stats(\course_modinfo $modinfo, array $userids): array {
        global $DB;
        $assigns = $modinfo->get_instances_of('assign');
        if (empty($assigns) || empty($userids)) {
            return [];
        }
        [$usql, $uparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
        $out = [];
        foreach ($assigns as $cm) {
            $instance = (int)$cm->instance;
            $duedate = (int)$DB->get_field('assign', 'duedate', ['id' => $instance]);

            $submitted = (int)$DB->count_records_select('assign_submission',
                "assignment = :a AND status = 'submitted' AND latest = 1 AND userid $usql",
                array_merge(['a' => $instance], $uparams));

            $graded = (int)$DB->count_records_select('assign_grades',
                "assignment = :a AND grade IS NOT NULL AND grade >= 0 AND userid $usql",
                array_merge(['a' => $instance], $uparams));

            $late = 0;
            if ($duedate > 0) {
                $late = (int)$DB->count_records_select('assign_submission',
                    "assignment = :a AND status = 'submitted' AND latest = 1 AND timemodified > :due AND userid $usql",
                    array_merge(['a' => $instance, 'due' => $duedate], $uparams));
            }

            $out[(int)$cm->id] = get_string('act_assignsummary', 'local_reportpanel',
                (object)['submitted' => $submitted, 'graded' => $graded, 'late' => $late]);
        }
        return $out;
    }
}
