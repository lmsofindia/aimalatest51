<?php
// This file is part of Moodle - http://moodle.org/

/**
 * External API service definitions for quizaccess_edproctoring.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'quizaccess_edproctoring_save_snapshot' => [
        'classname'     => '\quizaccess_edproctoring\external\save_snapshot',
        'methodname'    => 'execute',
        'description'   => 'Store a webcam snapshot from a quiz attempt.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'quizaccess_edproctoring_log_violation' => [
        'classname'     => '\quizaccess_edproctoring\external\log_violation',
        'methodname'    => 'execute',
        'description'   => 'Log a proctoring violation event.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'quizaccess_edproctoring_get_session' => [
        'classname'     => '\quizaccess_edproctoring\external\get_session',
        'methodname'    => 'execute',
        'description'   => 'Get the current proctoring session status.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'quizaccess_edproctoring_complete_session' => [
        'classname'     => '\quizaccess_edproctoring\external\complete_session',
        'methodname'    => 'execute',
        'description'   => 'Mark a proctoring session as completed and return trust score.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'quizaccess_edproctoring_get_live_sessions' => [
        'classname'     => '\\quizaccess_edproctoring\\external\\get_live_sessions',
        'methodname'    => 'execute',
        'description'   => 'List active proctoring sessions for the live monitor wall.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'quizaccess_edproctoring_dismiss_violation' => [
        'classname'     => '\quizaccess_edproctoring\external\dismiss_violation',
        'methodname'    => 'execute',
        'description'   => 'Dismiss a violation as a false positive (teacher only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'quizaccess/edproctoring:viewreport',
    ],
];
