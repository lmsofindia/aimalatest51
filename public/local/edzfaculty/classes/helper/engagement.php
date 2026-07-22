<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Per-course engagement rollups read from local_edzfaculty_coursecache.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class engagement {

    /**
     * Engagement metrics for a single course.
     *
     * @param int $courseid
     * @return array metric => [value, prev, trend]
     */
    public static function for_course(int $courseid): array {
        global $DB;
        $row = $DB->get_record('local_edzfaculty_coursecache', ['courseid' => $courseid]);
        return self::shape($row ?: null);
    }

    /**
     * Aggregated engagement across the given courses.
     *
     * @param int[] $courseids
     * @return array
     */
    public static function for_all(array $courseids): array {
        global $DB;
        if (!$courseids) {
            return self::shape(null);
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT AVG(activestudentspct) AS activestudentspct,
                       SUM(contentviews) AS contentviews,
                       SUM(assessmentstaken) AS assessmentstaken,
                       AVG(avgscore) AS avgscore,
                       AVG(activestudentsprev) AS activestudentsprev,
                       SUM(contentviewsprev) AS contentviewsprev,
                       SUM(assessmentstakenprev) AS assessmentstakenprev,
                       AVG(avgscoreprev) AS avgscoreprev
                  FROM {local_edzfaculty_coursecache}
                 WHERE courseid $insql";
        $row = $DB->get_record_sql($sql, $params);
        return self::shape($row ?: null);
    }

    /**
     * Normalise a cache row into value/prev/trend triples.
     *
     * @param object|null $row
     * @return array
     */
    private static function shape(?object $row): array {
        $val = fn($f) => $row && $row->$f !== null ? (float)$row->$f : 0;
        $trend = fn($now, $prev) => $prev > 0 ? round((($now - $prev) / $prev) * 100, 1) : 0;

        $active = $val('activestudentspct');
        $views  = $val('contentviews');
        $assess = $val('assessmentstaken');
        $score  = $val('avgscore');

        return [
            'active' => [
                'value' => round($active),
                'trend' => $trend($active, $val('activestudentsprev')),
            ],
            'views' => [
                'value' => (int)$views,
                'trend' => $trend($views, $val('contentviewsprev')),
            ],
            'assess' => [
                'value' => (int)$assess,
                'trend' => $trend($assess, $val('assessmentstakenprev')),
            ],
            'score' => [
                'value' => round($score),
                'trend' => $trend($score, $val('avgscoreprev')),
            ],
        ];
    }
}
