<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Capability definitions.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // See your own faculty dashboard (teachers).
    'local/edzfaculty:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
        ],
    ],

    // View any teacher's dashboard (oversight).
    'local/edzfaculty:viewall' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'riskbitmask'  => RISK_PERSONAL,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Manage plugin settings.
    'local/edzfaculty:managesettings' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'riskbitmask'  => RISK_CONFIG,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
