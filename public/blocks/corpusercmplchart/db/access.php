<?php
defined('MOODLE_INTERNAL') || die();
$capabilities = [
    'block/corpusercmplchart:myaddinstance' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['user' => CAP_ALLOW],
    ],
    'block/corpusercmplchart:addinstance' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => ['manager' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW],
    ],
];
