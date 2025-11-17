<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/customenrol:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'guest' => CAP_ALLOW,
            'user' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        ]
    ],
];
