<?php
defined('MOODLE_INTERNAL') || die();

$services = [
    'mod_edztrackvideo_services' => [
        'functions' => [
            'mod_edztrackvideo_get_progress',
            'mod_edztrackvideo_update_progress',
            'mod_edztrackvideo_mark_dismissed',
            'mod_edztrackvideo_get_config',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];

$functions = [
    'mod_edztrackvideo_get_progress' => [
        'classname' => 'mod_edztrackvideo\\external',
        'methodname' => 'get_progress',
        'classpath' => '',
        'description' => 'Get per-user progress for a module',
        'type' => 'read',
        'capabilities' => 'mod/edztrackvideo:view',
        'ajax' => true,
    ],
    'mod_edztrackvideo_update_progress' => [
        'classname' => 'mod_edztrackvideo\\external',
        'methodname' => 'update_progress',
        'classpath' => '',
        'description' => 'Update per-user progress',
        'type' => 'write',
        'capabilities' => 'mod/edztrackvideo:view',
        'ajax' => true,
    ],
    'mod_edztrackvideo_mark_dismissed' => [
        'classname' => 'mod_edztrackvideo\\external',
        'methodname' => 'mark_dismissed',
        'classpath' => '',
        'description' => 'Mark resume dismissed',
        'type' => 'write',
        'capabilities' => 'mod/edztrackvideo:view',
        'ajax' => true,
    ],
    'mod_edztrackvideo_get_config' => [
        'classname' => 'mod_edztrackvideo\\external',
        'methodname' => 'get_config',
        'classpath' => '',
        'description' => 'Get player config for a module (numeric-id fallback init path)',
        'type' => 'read',
        'capabilities' => 'mod/edztrackvideo:view',
        'ajax' => true,
    ],
];
