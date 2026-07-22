<?php
namespace quizaccess_edproctoring\external;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
defined('MOODLE_INTERNAL') || die();

class get_session extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT, 'Proctoring session ID'),
        ]);
    }
    public static function execute(int $sessionid): array {
        global $DB, $USER;
        $params  = self::validate_parameters(self::execute_parameters(), ['sessionid' => $sessionid]);
        $session = $DB->get_record('quizaccess_edproctoring_session',
            ['id' => $params['sessionid'], 'userid' => $USER->id], '*', MUST_EXIST);
        $context = \context_module::instance($session->cmid);
        self::validate_context($context);
        return [
            'id'                  => (int) $session->id,
            'status'              => $session->status,
            'total_snapshots'     => (int) $session->total_snapshots,
            'total_violations'    => (int) $session->total_violations,
            'critical_violations' => (int) $session->critical_violations,
            'trust_score'         => $session->trust_score !== null ? (float) $session->trust_score : -1,
        ];
    }
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id'                  => new external_value(PARAM_INT,   'Session ID'),
            'status'              => new external_value(PARAM_ALPHA,  'active|completed|abandoned'),
            'total_snapshots'     => new external_value(PARAM_INT,   'Snapshot count'),
            'total_violations'    => new external_value(PARAM_INT,   'Total violations'),
            'critical_violations' => new external_value(PARAM_INT,   'Critical violations'),
            'trust_score'         => new external_value(PARAM_FLOAT, 'Trust score (-1 if not yet computed)'),
        ]);
    }
}
