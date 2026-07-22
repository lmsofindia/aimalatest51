<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Scheduled tasks for quizaccess_edproctoring.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [

    // Daily cleanup: delete images older than retention_days.
    [
        'classname' => '\quizaccess_edproctoring\task\cleanup_images',
        'blocking'  => 0,
        'minute'    => '0',
        'hour'      => '3',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],

    // Hourly: compute trust scores for sessions missing them.
    [
        'classname' => '\quizaccess_edproctoring\task\recalculate_trust_scores',
        'blocking'  => 0,
        'minute'    => '15',
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],
];
