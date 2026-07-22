<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use quizaccess_edproctoring\helper\image_store;
use quizaccess_edproctoring\helper\session_manager;

/**
 * External API: save a webcam snapshot from a quiz attempt.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_snapshot extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid'   => new external_value(PARAM_INT,   'Proctoring session ID'),
            'imagedata'   => new external_value(PARAM_RAW,   'Base64-encoded PNG image data'),
            'capturetype' => new external_value(PARAM_ALPHA, 'interval|burst|prequiz|manual', VALUE_DEFAULT, 'interval'),
            'elapsed'     => new external_value(PARAM_INT,   'Seconds into attempt', VALUE_DEFAULT, 0),
            'quizpage'    => new external_value(PARAM_INT,   'Current quiz page', VALUE_DEFAULT, 0),
            'facedetected'=> new external_value(PARAM_INT,   '1=face found, 0=no face, -1=not checked', VALUE_DEFAULT, -1),
            'facecount'   => new external_value(PARAM_INT,   'Number of faces detected', VALUE_DEFAULT, 0),
            'brightness'  => new external_value(PARAM_FLOAT, 'Brightness score 0.0-1.0', VALUE_DEFAULT, -1),
            'isviolation' => new external_value(PARAM_INT,   '1 if this is a burst-on-violation capture', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $sessionid, string $imagedata, string $capturetype = 'interval',
            int $elapsed = 0, int $quizpage = 0, int $facedetected = -1,
            int $facecount = 0, float $brightness = -1, int $isviolation = 0): array {

        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'sessionid'    => $sessionid,
            'imagedata'    => $imagedata,
            'capturetype'  => $capturetype,
            'elapsed'      => $elapsed,
            'quizpage'     => $quizpage,
            'facedetected' => $facedetected,
            'facecount'    => $facecount,
            'brightness'   => $brightness,
            'isviolation'  => $isviolation,
        ]);

        // Security: verify session belongs to current user.
        $session = $DB->get_record('quizaccess_edproctoring_session',
            ['id' => $params['sessionid'], 'userid' => $USER->id],
            '*', MUST_EXIST);

        $context = \context_module::instance($session->cmid);
        self::validate_context($context);

        // Strip data: URI prefix if JS sends it.
        $imagedata = preg_replace('/^data:image\/\w+;base64,/', '', $params['imagedata']);

        // Store file in Moodledata.
        $pathnamehash = image_store::store_snapshot(
            $context->id,
            $session->id,
            $USER->id,
            $imagedata,
            $params['capturetype']
        );

        // Insert snap record.
        $now    = time();
        $snapid = $DB->insert_record('quizaccess_edproctoring_snap', (object)[
            'sessionid'       => $session->id,
            'userid'          => $USER->id,
            'attemptid'       => $session->attemptid,
            'pathnamehash'    => $pathnamehash,
            'capture_type'    => $params['capturetype'],
            'timecaptured'    => $now,
            'elapsed_seconds' => $params['elapsed'],
            'quiz_page'       => $params['quizpage'] ?: null,
            'face_detected'   => $params['facedetected'] === -1 ? null : $params['facedetected'],
            'face_count'      => $params['facedetected'] === -1 ? null : $params['facecount'],
            'brightness_score'=> $params['brightness'] === -1.0 ? null : $params['brightness'],
            'face_match_score'=> null,
            'is_violation'    => $params['isviolation'],
            'timecreated'     => $now,
        ]);

        // Increment session snapshot counter.
        session_manager::increment_snapshot_count($session->id);

        return ['snapid' => $snapid, 'success' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'snapid'  => new external_value(PARAM_INT,  'ID of stored snapshot'),
            'success' => new external_value(PARAM_BOOL, 'Whether storage succeeded'),
        ]);
    }
}
