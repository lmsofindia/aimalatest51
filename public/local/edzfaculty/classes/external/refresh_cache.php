<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_edzfaculty\helper\courses;

defined('MOODLE_INTERNAL') || die();

/**
 * AJAX: queue a cache rebuild for the current teacher (throttled).
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_cache extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * @return array
     */
    public static function execute(): array {
        global $USER;
        $context = \context_system::instance();
        self::validate_context($context);
        \local_edzfaculty\helper\access::require_teacher();

        $throttle = (int)(get_config('local_edzfaculty', 'refreshthrottle') ?: 30);
        $last     = (int)get_user_preferences('local_edzfaculty_lastrefresh', 0);
        $now      = time();

        if ($last && ($now - $last) < ($throttle * 60)) {
            return [
                'queued'  => 0,
                'message' => get_string('refreshthrottled', 'local_edzfaculty'),
            ];
        }

        set_user_preference('local_edzfaculty_lastrefresh', $now);

        $courseids = array_keys(courses::teaching_courses($USER->id));
        $task = new \local_edzfaculty\task\refresh_cache_adhoc();
        $task->set_custom_data(['courseids' => array_values($courseids)]);
        $task->set_userid($USER->id);
        \core\task\manager::queue_adhoc_task($task);

        return [
            'queued'  => 1,
            'message' => get_string('refreshqueued', 'local_edzfaculty'),
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'queued'  => new external_value(PARAM_INT, '1 if queued, 0 if throttled'),
            'message' => new external_value(PARAM_TEXT, 'User-facing message'),
        ]);
    }
}
