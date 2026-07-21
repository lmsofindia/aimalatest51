<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/edzallcourse:view' => [
        'riskbitmask' => 0,
        'captype'     => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'  => [
            'guest' => CAP_ALLOW,
            'user'  => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
