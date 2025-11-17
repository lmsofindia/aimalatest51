<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/edzworkplacerpt:viewreport' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        // Give this capability to all common archetypes by default.
        // Admins can still change role permissions later.
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
