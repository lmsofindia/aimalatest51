<?php
namespace quizaccess_edproctoring\external;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use quizaccess_edproctoring\helper\trust_score;
defined('MOODLE_INTERNAL') || die();

class dismiss_violation extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'violationid' => new external_value(PARAM_INT, 'Violation record ID'),
        ]);
    }
    public static function execute(int $violationid): array {
        global $DB, $USER;
        $params    = self::validate_parameters(self::execute_parameters(), ['violationid' => $violationid]);
        $violation = $DB->get_record('quizaccess_edproctoring_violation',
            ['id' => $params['violationid']], '*', MUST_EXIST);
        $session   = $DB->get_record('quizaccess_edproctoring_session',
            ['id' => $violation->sessionid], '*', MUST_EXIST);
        $context   = \context_module::instance($session->cmid);
        self::validate_context($context);
        require_capability('quizaccess/edproctoring:viewreport', $context);
        $DB->update_record('quizaccess_edproctoring_violation', (object)[
            'id'            => $violation->id,
            'dismissed'     => 1,
            'dismissed_by'  => $USER->id,
            'dismissed_time'=> time(),
        ]);

        // Recount non-dismissed violations and recalculate the trust score so
        // dismissing a false positive immediately gives the points back.
        $counts = $DB->get_records_sql_menu("
            SELECT severity, COUNT(*)
              FROM {quizaccess_edproctoring_violation}
             WHERE sessionid = :sid AND dismissed = 0
          GROUP BY severity", ['sid' => $session->id]);

        $update = (object)[
            'id'                  => $session->id,
            'critical_violations' => (int)($counts['critical'] ?? 0),
            'warning_violations'  => (int)($counts['warning'] ?? 0),
        ];
        if ($session->status === 'completed') {
            $update->trust_score = trust_score::calculate($session->id);
        }
        $DB->update_record('quizaccess_edproctoring_session', $update);

        return ['success' => true];
    }
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether dismissal succeeded'),
        ]);
    }
}
