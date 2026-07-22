<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Derives per-student attendance % from mod_zoom participant reports.
 *
 * mod_zoom stores attendance in {zoom_meeting_participants} (userid, duration,
 * detailsid) → {zoom_meeting_details} (a meeting instance) → {zoom} (course).
 * Attendance % = distinct meeting instances the student attended (for at least
 * the configured minimum minutes) / total meeting instances held in the course.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class zoom_attendance {

    /**
     * Is mod_zoom present with participant data?
     *
     * @return bool
     */
    public static function available(): bool {
        global $DB;
        return $DB->get_manager()->table_exists('zoom_meeting_participants')
            && $DB->get_manager()->table_exists('zoom_meeting_details')
            && $DB->get_manager()->table_exists('zoom');
    }

    /**
     * Per-student attendance percentages for a course.
     *
     * @param int $courseid
     * @return array [userid => float pct] (empty if no meetings held yet)
     */
    public static function pct_by_course(int $courseid): array {
        global $DB;
        if (!self::available()) {
            return [];
        }

        // Total distinct meeting instances held in the course.
        $held = (int)$DB->get_field_sql(
            "SELECT COUNT(DISTINCT d.id)
               FROM {zoom_meeting_details} d
               JOIN {zoom} z ON z.id = d.zoomid
              WHERE z.course = :courseid",
            ['courseid' => $courseid]);
        if ($held <= 0) {
            return [];
        }

        $minseconds = (int)(get_config('local_edzfaculty', 'zoomminminutes') ?: 5) * 60;

        // Distinct instances each matched student attended for >= threshold.
        $sql = "SELECT p.userid, COUNT(DISTINCT p.detailsid) AS attended
                  FROM {zoom_meeting_participants} p
                  JOIN {zoom_meeting_details} d ON d.id = p.detailsid
                  JOIN {zoom} z ON z.id = d.zoomid
                 WHERE z.course = :courseid AND p.userid > 0 AND p.duration >= :minseconds
              GROUP BY p.userid";
        $rows = $DB->get_records_sql($sql, ['courseid' => $courseid, 'minseconds' => $minseconds]);

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r->userid] = round(min(100, ($r->attended / $held) * 100), 2);
        }
        return $out;
    }
}
