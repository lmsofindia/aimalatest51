<?php
namespace quizaccess_edproctoring\external;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use quizaccess_edproctoring\helper\session_manager;
defined('MOODLE_INTERNAL') || die();

class complete_session extends external_api {
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
        $score = session_manager::mark_completed($session->id);
        return ['trust_score' => $score, 'success' => true];
    }
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'trust_score' => new external_value(PARAM_FLOAT, 'Computed trust score 0-100'),
            'success'     => new external_value(PARAM_BOOL,  'Whether completion succeeded'),
        ]);
    }
}
