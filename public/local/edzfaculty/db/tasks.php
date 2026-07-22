<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Scheduled task registration.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_edzfaculty\task\refresh_cache',
        'blocking'  => 0,
        // Every 6 hours (Vidya cache pattern).
        'minute'    => '15',
        'hour'      => '*/6',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
];
