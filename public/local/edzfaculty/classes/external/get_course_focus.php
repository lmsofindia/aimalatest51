<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_edzfaculty\helper\dashboard_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * AJAX: per-course Course Focus stats + quick-action URLs.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_course_focus extends external_api {

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

        $helper = new dashboard_helper($USER->id);
        $model  = $helper->get_dashboard();
        $cid    = $params['courseid'];

        $f = ($cid && isset($model['focusmap'][$cid])) ? $model['focusmap'][$cid] : $model['focusall'];

        return [
            'live'   => (int)$f['live'],
            'assign' => (int)$f['assign'],
            'disc'   => (int)$f['disc'],
            'quiz'   => (int)$f['quiz'],
            'label'  => $f['label'],
            'ctx'    => $f['ctx'],
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'live'   => new external_value(PARAM_INT, 'Live classes upcoming'),
            'assign' => new external_value(PARAM_INT, 'Assignments to review'),
            'disc'   => new external_value(PARAM_INT, 'Discussion new posts'),
            'quiz'   => new external_value(PARAM_INT, 'Quizzes to grade'),
            'label'  => new external_value(PARAM_TEXT, 'Quick-actions label'),
            'ctx'    => new external_value(PARAM_TEXT, 'Context line'),
        ]);
    }
}
