<?php
/**
 * Scheduled tasks definition.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\local_edzaiaxisfront\task\poll_jobs',
        'blocking'  => 0,
        'minute'    => '*/2',   // every 2 minutes
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],
    [
        'classname' => '\local_edzaiaxisfront\task\sync_outputs',
        'blocking'  => 0,
        'minute'    => '*/5',   // every 5 minutes — pulls any new outputs from axis-ai
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
        'disabled'  => 0,
    ],
];
