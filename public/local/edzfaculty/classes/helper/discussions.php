<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Forum discussions with unanswered student questions.
 *
 * A thread is "unanswered" when its most recent post was written by a student
 * (i.e. not by any teacher of the course) — the teacher hasn't replied yet.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discussions {

    /**
     * Unanswered discussions across the given courses.
     *
     * @param int[] $courseids
     * @return \stdClass[] rows: discussionid, name, course, forumname, forumid, lastuserid, lastcreated
     */
    public static function unanswered(array $courseids): array {
        global $DB;
        if (!$courseids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        // Last post per discussion.
        $sql = "SELECT d.id AS discussionid, d.name, d.course, f.id AS forumid, f.name AS forumname,
                       lp.userid AS lastuserid, lp.created AS lastcreated
                  FROM {forum_discussions} d
                  JOIN {forum} f ON f.id = d.forum
                  JOIN {forum_posts} lp ON lp.id = (
                        SELECT p2.id
                          FROM {forum_posts} p2
                         WHERE p2.discussion = d.id
                      ORDER BY p2.created DESC, p2.id DESC
                         LIMIT 1)
                 WHERE d.course $insql
              ORDER BY lp.created DESC";
        $rows = $DB->get_records_sql($sql, $params, 0, 100);
        if (!$rows) {
            return [];
        }

        // Build a teacher lookup per course so we can tell student posts from staff replies.
        $teachermap = [];
        foreach (array_unique(array_map(fn($r) => (int)$r->course, $rows)) as $cid) {
            $teachermap[$cid] = courses::teacher_map($cid);
        }

        $unanswered = [];
        foreach ($rows as $r) {
            $cid = (int)$r->course;
            if (!isset($teachermap[$cid][(int)$r->lastuserid])) {
                // Last post is from a student → needs a reply.
                $unanswered[] = $r;
            }
        }
        return $unanswered;
    }

    /**
     * Unanswered count per course.
     *
     * @param \stdClass[] $rows result of unanswered()
     * @return array [courseid => count]
     */
    public static function count_by_course(array $rows): array {
        $out = [];
        foreach ($rows as $r) {
            $cid = (int)$r->course;
            $out[$cid] = ($out[$cid] ?? 0) + 1;
        }
        return $out;
    }
}
