<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Upcoming calendar events — live classes, exams, assessments, office hours.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timeline {

    /** Module names treated as a joinable live class. */
    private const LIVE_MODS = ['bigbluebuttonbn', 'zoom', 'webexactivity', 'teams'];

    /**
     * Upcoming events for the teacher across their courses.
     *
     * @param int $teacherid
     * @param int[] $courseids
     * @param int $days look-ahead window
     * @return array normalised event rows
     */
    public static function upcoming(int $teacherid, array $courseids, int $days = 14): array {
        global $DB;
        $now   = time();
        $until = $now + ($days * DAYSECS);

        $params = ['now' => $now, 'until' => $until];
        $where  = "timestart <= :until AND (timestart + timeduration) >= :now";
        if ($courseids) {
            [$insql, $cp] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
            $where .= " AND (courseid $insql OR userid = :uid)";
            $params += $cp;
            $params['uid'] = $teacherid;
        } else {
            $where .= " AND userid = :uid";
            $params['uid'] = $teacherid;
        }

        $sql = "SELECT id, name, courseid, timestart, timeduration, modulename, instance, eventtype
                  FROM {event}
                 WHERE $where
              ORDER BY timestart ASC";
        $events = $DB->get_records_sql($sql, $params, 0, 25);

        $out = [];
        foreach ($events as $e) {
            $type = 'event';
            $joinurl = null;
            $mod = (string)$e->modulename;

            if (in_array($mod, self::LIVE_MODS, true)) {
                $type = 'liveclass';
                $cm = get_coursemodule_from_instance($mod, $e->instance, $e->courseid, false, IGNORE_MISSING);
                if ($cm) {
                    $joinurl = (new \moodle_url("/mod/$mod/view.php", ['id' => $cm->id]))->out(false);
                }
            } else if ($mod === 'quiz' || $e->eventtype === 'exam') {
                $type = 'assessment';
            }

            $out[] = (object)[
                'id'        => (int)$e->id,
                'name'      => format_string($e->name),
                'courseid'  => (int)$e->courseid,
                'timestart' => (int)$e->timestart,
                'duration'  => (int)$e->timeduration,
                'modulename' => $mod,
                'type'      => $type,
                'joinurl'   => $joinurl,
            ];
        }
        return $out;
    }

    /**
     * Count of upcoming live classes per course.
     *
     * @param array $events result of upcoming()
     * @return array [courseid => count]
     */
    public static function liveclass_count_by_course(array $events): array {
        $out = [];
        foreach ($events as $e) {
            if ($e->type === 'liveclass') {
                $out[$e->courseid] = ($out[$e->courseid] ?? 0) + 1;
            }
        }
        return $out;
    }
}
