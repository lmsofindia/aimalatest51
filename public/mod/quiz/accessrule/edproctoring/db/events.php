<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Event observer registrations for quizaccess_edproctoring.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [

    // When a quiz attempt is submitted: complete the session and compute trust score.
    [
        'eventname'   => '\mod_quiz\event\attempt_submitted',
        'callback'    => '\quizaccess_edproctoring\observer::attempt_submitted',
        'includefile' => null,
        'internal'    => false,
        'priority'    => 0,
    ],

    // When a quiz attempt is abandoned (timed out, left): mark session abandoned.
    [
        'eventname'   => '\mod_quiz\event\attempt_abandoned',
        'callback'    => '\quizaccess_edproctoring\observer::attempt_abandoned',
        'includefile' => null,
        'internal'    => false,
        'priority'    => 0,
    ],
];
