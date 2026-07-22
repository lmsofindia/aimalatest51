<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * External (AJAX) service definitions.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_edzfaculty_get_course_focus' => [
        'classname'   => 'local_edzfaculty\external\get_course_focus',
        'description' => 'Return per-course stat counts and quick-action URLs for the Course Focus panel.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/edzfaculty:view',
        'loginrequired' => true,
    ],
    'local_edzfaculty_get_engagement' => [
        'classname'   => 'local_edzfaculty\external\get_engagement',
        'description' => 'Return engagement metrics + trend + AI insight for a course scope.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/edzfaculty:view',
        'loginrequired' => true,
    ],
    'local_edzfaculty_refresh_cache' => [
        'classname'   => 'local_edzfaculty\external\refresh_cache',
        'description' => 'Queue a cache rebuild for the current teacher (throttled).',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/edzfaculty:view',
        'loginrequired' => true,
    ],
    'local_edzfaculty_send_nudge' => [
        'classname'   => 'local_edzfaculty\external\send_nudge',
        'description' => 'Send a one-click nudge notification to an at-risk student.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/edzfaculty:view',
        'loginrequired' => true,
    ],
];
