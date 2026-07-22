<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\task;

use quizaccess_edproctoring\helper\trust_score;

/**
 * Scheduled task: compute trust scores for completed sessions that have none yet.
 * Handles cases where the event observer failed or session was completed externally.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recalculate_trust_scores extends \core\task\scheduled_task {

    public function get_name(): string {
        return 'EDP: Recalculate missing trust scores';
    }

    public function execute(): void {
        global $DB;

        $sessions = $DB->get_records_select(
            'quizaccess_edproctoring_session',
            "status = 'completed' AND trust_score IS NULL",
            [],
            '',
            'id'
        );

        if (empty($sessions)) {
            mtrace('EDP trust score task: no sessions with missing scores.');
            return;
        }

        mtrace('EDP trust score task: recalculating ' . count($sessions) . ' session(s).');

        foreach ($sessions as $session) {
            $score = trust_score::calculate($session->id);
            $DB->set_field('quizaccess_edproctoring_session', 'trust_score', $score,
                ['id' => $session->id]);
            mtrace("  Session {$session->id}: score = $score");
        }
    }
}
