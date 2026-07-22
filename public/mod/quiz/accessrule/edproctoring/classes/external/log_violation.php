<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use quizaccess_edproctoring\helper\violation_classifier;
use quizaccess_edproctoring\helper\session_manager;

/**
 * External API: log a proctoring violation event.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log_violation extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid'     => new external_value(PARAM_INT,        'Proctoring session ID'),
            'violationtype' => new external_value(PARAM_ALPHANUMEXT,'Violation type (e.g. FACE_ABSENT)'),
            'details'       => new external_value(PARAM_RAW,        'JSON extra context', VALUE_DEFAULT, '{}'),
            'quizpage'      => new external_value(PARAM_INT,        'Quiz page number', VALUE_DEFAULT, 0),
            'elapsed'       => new external_value(PARAM_INT,        'Seconds into attempt', VALUE_DEFAULT, 0),
            'snapid'        => new external_value(PARAM_INT,        'Associated snapshot ID', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $sessionid, string $violationtype,
            string $details = '{}', int $quizpage = 0,
            int $elapsed = 0, int $snapid = 0): array {

        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'sessionid'     => $sessionid,
            'violationtype' => $violationtype,
            'details'       => $details,
            'quizpage'      => $quizpage,
            'elapsed'       => $elapsed,
            'snapid'        => $snapid,
        ]);

        // Security: verify session belongs to current user.
        $session = $DB->get_record('quizaccess_edproctoring_session',
            ['id' => $params['sessionid'], 'userid' => $USER->id],
            '*', MUST_EXIST);

        $context = \context_module::instance($session->cmid);
        self::validate_context($context);

        // Validate violation type.
        $type = strtoupper($params['violationtype']);
        if (!violation_classifier::is_valid($type)) {
            throw new \invalid_parameter_exception("Invalid violation type: $type");
        }

        $severity = violation_classifier::get_severity($type);
        $weight   = violation_classifier::get_weight($type);

        $violationid = $DB->insert_record('quizaccess_edproctoring_violation', (object)[
            'sessionid'      => $session->id,
            'userid'         => $USER->id,
            'attemptid'      => $session->attemptid,
            'snap_id'        => $params['snapid'] ?: null,
            'violation_type' => $type,
            'severity'       => $severity,
            'severity_weight'=> $weight,
            'details'        => $params['details'],
            'quiz_page'      => $params['quizpage'] ?: null,
            'elapsed_seconds'=> $params['elapsed'],
            'dismissed'      => 0,
            'timecreated'    => time(),
        ]);

        // Update session counters.
        session_manager::increment_violation_count($session->id, $severity);

        // Check if auto-submit threshold is reached.
        $quizsettings = $DB->get_record('quizaccess_edproctoring', ['quizid' => $session->quizid]);
        $autosubmit   = false;

        if ($quizsettings && $quizsettings->auto_submit && $severity === 'critical') {
            $critcount = $DB->count_records('quizaccess_edproctoring_violation', [
                'sessionid' => $session->id,
                'severity'  => 'critical',
                'dismissed' => 0,
            ]);
            if ($critcount >= $quizsettings->auto_submit_threshold) {
                $autosubmit = true;
            }
        }

        // Emit event.
        $event = \quizaccess_edproctoring\event\violation_logged::create([
            'context'  => $context,
            'objectid' => $violationid,
            'userid'   => $USER->id,
            'other'    => [
                'sessionid'      => $session->id,
                'violation_type' => $type,
                'severity'       => $severity,
                'weight'         => $weight,
            ],
        ]);
        $event->trigger();

        // Get updated counts for JS.
        $updatedSession = $DB->get_record('quizaccess_edproctoring_session',
            ['id' => $session->id], 'total_violations, critical_violations, warning_violations');

        return [
            'violationid'  => $violationid,
            'warningcount' => (int) $updatedSession->total_violations,
            'autosubmit'   => $autosubmit,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'violationid'  => new external_value(PARAM_INT,  'ID of logged violation'),
            'warningcount' => new external_value(PARAM_INT,  'Total violations so far'),
            'autosubmit'   => new external_value(PARAM_BOOL, 'Whether JS should auto-submit quiz'),
        ]);
    }
}
