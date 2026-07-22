<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

use context_course;

defined('MOODLE_INTERNAL') || die();

/**
 * Teaching-course discovery and section (group) helpers.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses {

    /**
     * Courses the given user teaches (has grading capability in).
     *
     * @param int $teacherid
     * @return \stdClass[] keyed by courseid
     */
    public static function teaching_courses(int $teacherid): array {
        $courses  = enrol_get_all_users_courses($teacherid, true, '*');
        $teaching = [];
        foreach ($courses as $c) {
            if ($c->id == SITEID) {
                continue;
            }
            $ctx = context_course::instance($c->id);
            if (has_capability('mod/assign:grade', $ctx, $teacherid)
                    || has_capability('moodle/grade:edit', $ctx, $teacherid)) {
                $teaching[$c->id] = $c;
            }
        }
        return $teaching;
    }

    /**
     * Enrolled student user ids for a course (users who can submit).
     *
     * @param int $courseid
     * @return int[]
     */
    public static function student_ids(int $courseid): array {
        $ctx   = context_course::instance($courseid);
        $users = get_enrolled_users($ctx, 'mod/assign:submit', 0, 'u.id', null, 0, 0, true);
        return array_keys($users);
    }

    /**
     * Teacher user ids for a course.
     *
     * @param int $courseid
     * @return int[] as a lookup map [id => true]
     */
    public static function teacher_map(int $courseid): array {
        $ctx = context_course::instance($courseid);
        $map = [];
        foreach (get_enrolled_users($ctx, 'mod/assign:grade', 0, 'u.id') as $u) {
            $map[$u->id] = true;
        }
        return $map;
    }

    /**
     * First group name for a user in a course (used as the "section/batch" label).
     *
     * @param int $courseid
     * @param int $userid
     * @return string
     */
    public static function section_label(int $courseid, int $userid): string {
        $groups = groups_get_all_groups($courseid, $userid);
        if ($groups) {
            $g = reset($groups);
            return format_string($g->name);
        }
        return '';
    }

    /**
     * Percentage of course modules that are visible/published.
     *
     * @param int $courseid
     * @return float
     */
    public static function content_delivered_pct(int $courseid): float {
        $modinfo = get_fast_modinfo($courseid);
        $total = 0;
        $visible = 0;
        foreach ($modinfo->get_cms() as $cm) {
            $total++;
            if ($cm->visible) {
                $visible++;
            }
        }
        return $total ? round(($visible / $total) * 100, 1) : 0;
    }
}
