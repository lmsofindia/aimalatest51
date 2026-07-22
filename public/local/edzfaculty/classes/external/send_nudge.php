<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_edzfaculty\helper\courses;
use local_edzfaculty\helper\messaging;

defined('MOODLE_INTERNAL') || die();

/**
 * AJAX: send a one-click nudge to an at-risk student.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_nudge extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'studentid' => new external_value(PARAM_INT, 'Student user id'),
            'courseid'  => new external_value(PARAM_INT, 'Course id'),
            'message'   => new external_value(PARAM_TEXT, 'Message body (empty = default template)', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * @param int $studentid
     * @param int $courseid
     * @param string $message
     * @return array
     */
    public static function execute(int $studentid, int $courseid, string $message = ''): array {
        global $USER, $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'studentid' => $studentid, 'courseid' => $courseid, 'message' => $message,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        \local_edzfaculty\helper\access::require_teacher();

        // The teacher must teach this course.
        $teaching = courses::teaching_courses($USER->id);
        if (!isset($teaching[$params['courseid']])) {
            return ['sent' => 0, 'message' => get_string('nudgefailed', 'local_edzfaculty')];
        }

        // The student must be enrolled in that course.
        $coursectx = \context_course::instance($params['courseid']);
        if (!is_enrolled($coursectx, $params['studentid'])) {
            return ['sent' => 0, 'message' => get_string('nudgefailed', 'local_edzfaculty')];
        }

        $result = messaging::send($USER->id, $params['studentid'], $params['courseid'], $params['message']);

        return [
            'sent'    => $result ? 1 : 0,
            'message' => get_string($result ? 'nudgesent' : 'nudgefailed', 'local_edzfaculty'),
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sent'    => new external_value(PARAM_INT, '1 if sent'),
            'message' => new external_value(PARAM_TEXT, 'User-facing result'),
        ]);
    }
}
