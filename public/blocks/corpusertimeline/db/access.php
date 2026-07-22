<?php
defined('MOODLE_INTERNAL') || die();
$capabilities = [
    'block/corpusertimeline:myaddinstance' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['user' => CAP_ALLOW],
    ],
    'block/corpusertimeline:addinstance' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => ['manager' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW],
    ],
];
