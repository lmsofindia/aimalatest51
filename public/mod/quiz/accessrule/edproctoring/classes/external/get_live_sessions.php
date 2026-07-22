<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * External API: list active proctoring sessions for the live monitor wall.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_edproctoring\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use quizaccess_edproctoring\helper\live_monitor;

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the set of currently active proctoring sessions visible to the caller.
 */
class get_live_sessions extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT,
                'Quiz course module id to scope to; 0 for site-wide', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * @param int $cmid
     * @return array
     */
    public static function execute(int $cmid = 0): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        $cmid   = (int) $params['cmid'];

        if ($cmid > 0) {
            $cm      = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
            $context = \context_module::instance($cmid);
            self::validate_context($context);
            require_capability('quizaccess/edproctoring:viewreport', $context);

            $quiz  = $DB->get_record('quiz', ['id' => $cm->instance], 'id', MUST_EXIST);
            $tiles = live_monitor::get_active_sessions((int) $quiz->id);
        } else {
            $context = \context_system::instance();
            self::validate_context($context);
            require_capability('quizaccess/edproctoring:viewallreports', $context);

            $tiles = live_monitor::get_active_sessions(0);
        }

        return [
            'servertime' => time(),
            'sessions'   => array_values($tiles),
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'servertime' => new external_value(PARAM_INT, 'Server epoch seconds'),
            'sessions'   => new external_multiple_structure(new external_single_structure([
                'sessionid'              => new external_value(PARAM_INT,     'Proctoring session id'),
                'userid'                 => new external_value(PARAM_INT,     'Student user id'),
                'fullname'               => new external_value(PARAM_NOTAGS,  'Student full name'),
                'quizid'                 => new external_value(PARAM_INT,     'Quiz id'),
                'cmid'                   => new external_value(PARAM_INT,     'Quiz course module id'),
                'quizname'               => new external_value(PARAM_NOTAGS,  'Quiz name'),
                'courseshort'            => new external_value(PARAM_NOTAGS,  'Course short name'),
                'attemptid'              => new external_value(PARAM_INT,     'Quiz attempt id'),
                'started_at'             => new external_value(PARAM_INT,     'Attempt start epoch'),
                'elapsed'                => new external_value(PARAM_INT,     'Seconds since start'),
                'thumburl'               => new external_value(PARAM_RAW,     'Latest snapshot URL (empty if none)'),
                'lastseen'               => new external_value(PARAM_INT,     'Seconds since last snapshot, -1 if none'),
                'stale'                  => new external_value(PARAM_INT,     '1 if connection considered lost'),
                'total_violations'       => new external_value(PARAM_INT,     'Total violations so far'),
                'critical_violations'    => new external_value(PARAM_INT,     'Critical violations'),
                'warning_violations'     => new external_value(PARAM_INT,     'Warning violations'),
                'trust'                  => new external_value(PARAM_FLOAT,   'Provisional trust score 0-100'),
                'band'                   => new external_value(PARAM_ALPHAEXT, 'Trust band (low_risk|review|high_risk)'),
                'lastviolation'          => new external_value(PARAM_NOTAGS,  'Most recent violation type'),
                'lastviolation_severity' => new external_value(PARAM_ALPHA,   'Most recent violation severity'),
            ])),
        ]);
    }
}
