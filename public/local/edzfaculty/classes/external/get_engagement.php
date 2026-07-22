<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_edzfaculty\helper\courses;
use local_edzfaculty\helper\engagement;

defined('MOODLE_INTERNAL') || die();

/**
 * AJAX: engagement metrics + trend for a course scope.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_engagement extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id, 0 for all courses'),
        ]);
    }

    /**
     * @param int $courseid
     * @return array
     */
    public static function execute(int $courseid): array {
        global $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $context = \context_system::instance();
        self::validate_context($context);
        \local_edzfaculty\helper\access::require_teacher();

        $cid = $params['courseid'];
        if ($cid) {
            // Only allow courses the teacher actually teaches.
            $teaching = courses::teaching_courses($USER->id);
            if (!isset($teaching[$cid])) {
                $cid = 0;
            }
        }

        if ($cid) {
            $e = engagement::for_course($cid);
        } else {
            $e = engagement::for_all(array_keys(courses::teaching_courses($USER->id)));
        }

        return [
            'active'      => (int)$e['active']['value'],
            'activetrend' => (float)$e['active']['trend'],
            'views'       => (int)$e['views']['value'],
            'viewstrend'  => (float)$e['views']['trend'],
            'assess'      => (int)$e['assess']['value'],
            'assesstrend' => (float)$e['assess']['trend'],
            'score'       => (int)$e['score']['value'],
            'scoretrend'  => (float)$e['score']['trend'],
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'active'      => new external_value(PARAM_INT, 'Active students %'),
            'activetrend' => new external_value(PARAM_FLOAT, 'Active students trend %'),
            'views'       => new external_value(PARAM_INT, 'Content views'),
            'viewstrend'  => new external_value(PARAM_FLOAT, 'Content views trend %'),
            'assess'      => new external_value(PARAM_INT, 'Assessments taken'),
            'assesstrend' => new external_value(PARAM_FLOAT, 'Assessments trend %'),
            'score'       => new external_value(PARAM_INT, 'Average score %'),
            'scoretrend'  => new external_value(PARAM_FLOAT, 'Average score trend %'),
        ]);
    }
}
