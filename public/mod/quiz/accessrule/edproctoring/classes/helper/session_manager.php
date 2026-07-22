<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\helper;

use quizaccess_edproctoring\helper\trust_score as TrustScore;

/**
 * Session lifecycle manager.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_manager {

    const STATUS_ACTIVE    = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABANDONED = 'abandoned';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Get an existing active session or create one for this attempt.
     *
     * @param int $quizid
     * @param int $cmid
     * @param int $userid
     * @param int $attemptid
     * @return int session id
     */
    public static function get_or_create(int $quizid, int $cmid,
            int $userid, ?int $attemptid): int {
        global $DB;

        $existing = $DB->get_record('quizaccess_edproctoring_session',
            ['attemptid' => $attemptid]);

        if ($existing) {
            return (int) $existing->id;
        }

        $now = time();
        $record = (object)[
            'quizid'              => $quizid,
            'cmid'                => $cmid,
            'userid'              => $userid,
            'attemptid'           => $attemptid ?? 0,
            'status'              => self::STATUS_ACTIVE,
            'consent_given'       => 0,
            'consent_time'        => 0,
            'started_at'          => $now,
            'ended_at'            => 0,
            'trust_score'         => null,
            'total_snapshots'     => 0,
            'total_violations'    => 0,
            'critical_violations' => 0,
            'warning_violations'  => 0,
            'timecreated'         => $now,
            'timemodified'        => $now,
        ];

        return (int) $DB->insert_record('quizaccess_edproctoring_session', $record);
    }

    /**
     * Record consent for a session.
     *
     * @param int $sessionid
     */
    public static function record_consent(int $sessionid): void {
        global $DB;
        $DB->set_field('quizaccess_edproctoring_session', 'consent_given', 1,
            ['id' => $sessionid]);
        $DB->set_field('quizaccess_edproctoring_session', 'consent_time', time(),
            ['id' => $sessionid]);
    }

    /**
     * Mark session as completed, compute trust score, send notification.
     *
     * @param int $sessionid
     * @return float computed trust score
     */
    public static function mark_completed(int $sessionid): float {
        global $DB;

        $score = TrustScore::calculate($sessionid);
        $now   = time();

        $DB->update_record('quizaccess_edproctoring_session', (object)[
            'id'           => $sessionid,
            'status'       => self::STATUS_COMPLETED,
            'ended_at'     => $now,
            'trust_score'  => $score,
            'timemodified' => $now,
        ]);

        // Emit event.
        $session = $DB->get_record('quizaccess_edproctoring_session', ['id' => $sessionid]);
        $context = \context_module::instance($session->cmid);
        $event   = \quizaccess_edproctoring\event\session_completed::create([
            'context'  => $context,
            'objectid' => $sessionid,
            'userid'   => $session->userid,
            'other'    => ['trust_score' => $score, 'attemptid' => $session->attemptid],
        ]);
        $event->trigger();

        // Send teacher notification if below threshold.
        self::maybe_notify_teacher($session, $score);

        return $score;
    }

    /**
     * Mark session as abandoned.
     *
     * @param int $attemptid quiz_attempts.id
     */
    public static function mark_abandoned(int $attemptid): void {
        global $DB;
        $DB->set_field('quizaccess_edproctoring_session', 'status', self::STATUS_ABANDONED,
            ['attemptid' => $attemptid]);
        $DB->set_field('quizaccess_edproctoring_session', 'ended_at', time(),
            ['attemptid' => $attemptid]);
    }

    /**
     * Increment snapshot counter on session.
     *
     * @param int $sessionid
     */
    public static function increment_snapshot_count(int $sessionid): void {
        global $DB;
        $DB->execute(
            'UPDATE {quizaccess_edproctoring_session}
                SET total_snapshots = total_snapshots + 1, timemodified = ?
              WHERE id = ?',
            [time(), $sessionid]
        );
    }

    /**
     * Increment violation counters on session.
     *
     * @param int    $sessionid
     * @param string $severity  'critical'|'warning'|'info'
     */
    public static function increment_violation_count(int $sessionid, string $severity): void {
        global $DB;
        $col = ($severity === 'critical') ? 'critical_violations'
             : (($severity === 'warning') ? 'warning_violations' : null);

        $DB->execute(
            'UPDATE {quizaccess_edproctoring_session}
                SET total_violations = total_violations + 1, timemodified = ?
              WHERE id = ?',
            [time(), $sessionid]
        );

        if ($col) {
            $DB->execute(
                "UPDATE {quizaccess_edproctoring_session}
                    SET $col = $col + 1
                  WHERE id = ?",
                [$sessionid]
            );
        }
    }

    /**
     * Send a Moodle notification to all teachers of the quiz.
     *
     * @param \stdClass $session session record
     * @param float     $score
     */
    private static function maybe_notify_teacher(\stdClass $session, float $score): void {
        global $DB;

        // Get quiz settings to check notify_teacher and notify_threshold.
        $settings = $DB->get_record('quizaccess_edproctoring',
            ['quizid' => $session->quizid]);

        if (!$settings || !$settings->notify_teacher) {
            return;
        }

        if ($score >= $settings->notify_threshold) {
            return;
        }

        $student = \core_user::get_user($session->userid);
        $course  = $DB->get_record_sql(
            'SELECT c.* FROM {course} c
               JOIN {course_modules} cm ON cm.course = c.id
              WHERE cm.id = ?',
            [$session->cmid]
        );
        $quiz    = $DB->get_record('quiz', ['id' => $session->quizid]);

        // Find all editing teachers in the course.
        $context  = \context_module::instance($session->cmid);
        $teachers = get_users_by_capability($context, 'quizaccess/edproctoring:viewreport');

        $reporturl = new \moodle_url('/quizaccess/edproctoring/report/attempt_detail.php',
            ['sessionid' => $session->id]);

        $band = trust_score::get_band($score);

        foreach ($teachers as $teacher) {
            $message                     = new \core\message\message();
            $message->component          = 'quizaccess_edproctoring';
            $message->name               = 'suspicioussession';
            $message->userfrom           = \core_user::get_noreply_user();
            $message->userto             = $teacher;
            $message->subject            = get_string('notifysubject', 'quizaccess_edproctoring',
                ['student' => fullname($student), 'quiz' => $quiz->name]);
            $message->fullmessage        = get_string('notifybody', 'quizaccess_edproctoring', [
                'student'   => fullname($student),
                'quiz'      => $quiz->name,
                'score'     => number_format($score, 1),
                'band'      => get_string('band_' . $band, 'quizaccess_edproctoring'),
                'critical'  => $session->critical_violations,
                'warning'   => $session->warning_violations,
                'reporturl' => $reporturl->out(false),
            ]);
            $message->fullmessageformat  = FORMAT_PLAIN;
            $message->fullmessagehtml    = '';
            $message->smallmessage       = get_string('notifysmall', 'quizaccess_edproctoring', [
                'student' => fullname($student),
                'score'   => number_format($score, 1),
            ]);
            $message->notification       = 1;
            $message->contexturl         = $reporturl->out(false);
            $message->contexturlname     = get_string('viewreport', 'quizaccess_edproctoring');

            message_send($message);
        }
    }
}
