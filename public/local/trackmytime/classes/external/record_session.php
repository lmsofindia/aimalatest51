<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External function: local_trackmytime_record_session
 *
 * Called by AMD module (incourse JS) via Moodle AJAX to persist time-on-task
 * data to the {trackmytime} table.
 *
 * @package    local_trackmytime
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_trackmytime\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function: record_session.
 *
 * @package    local_trackmytime
 * @copyright  2026 EDZLMS
 */
class record_session extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'       => new external_value(PARAM_INT, 'Course module ID being studied'),
            'timespent'  => new external_value(PARAM_INT, 'Seconds spent in this session (max 3600)'),
            'timestart'  => new external_value(PARAM_INT, 'Unix timestamp when this session started'),
        ]);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True when record was saved'),
        ]);
    }

    public static function execute(int $cmid, int $timespent, int $timestart): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'      => $cmid,
            'timespent' => $timespent,
            'timestart' => $timestart,
        ]);

        // Validate cm exists and user has access.
        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid']);
        $modcontext = \context_module::instance($cm->id);
        self::validate_context($modcontext);

        // Sanity-cap session at 1 hour to prevent abuse.
        $timespent = min($params['timespent'], 3600);
        if ($timespent <= 0) {
            return ['success' => false];
        }

        $record = new \stdClass();
        $record->userid    = (int) $USER->id;
        $record->courseid  = (int) $course->id;
        $record->cmid      = (int) $cm->id;
        $record->timespent = $timespent;
        $record->timestart = $params['timestart'];
        $record->timeend   = $params['timestart'] + $timespent;
        $record->timecreated = time();

        $DB->insert_record('trackmytime', $record);

        return ['success' => true];
    }
}
