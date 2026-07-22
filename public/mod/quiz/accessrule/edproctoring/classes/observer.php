<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring;

use quizaccess_edproctoring\helper\session_manager;

/**
 * Event observers for quizaccess_edproctoring.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Fired when a quiz attempt is submitted normally.
     * Mark the proctoring session completed and compute trust score.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function attempt_submitted(\mod_quiz\event\attempt_submitted $event): void {
        global $DB;

        $attemptid = $event->objectid;
        $session   = $DB->get_record('quizaccess_edproctoring_session',
            ['attemptid' => $attemptid, 'status' => session_manager::STATUS_ACTIVE]);

        if (!$session) {
            return; // Not a proctored attempt or already processed.
        }

        session_manager::mark_completed($session->id);
    }

    /**
     * Fired when a quiz attempt is abandoned (timed out, connection lost).
     * Mark session as abandoned without computing trust score.
     *
     * @param \mod_quiz\event\attempt_abandoned $event
     */
    public static function attempt_abandoned(\mod_quiz\event\attempt_abandoned $event): void {
        session_manager::mark_abandoned($event->objectid);
    }
}
