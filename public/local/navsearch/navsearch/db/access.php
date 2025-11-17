<?php


/**
 * access information for local_navsearch
 *
 * @package    local_navsearch
 * @copyright  2025 Edz Lms <marketing@edzlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/navsearch:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW
        ]
    ]
];
