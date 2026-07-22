<?php
defined('MOODLE_INTERNAL') || die();
$functions = [
    'block_corpusertimeline_get_timeline' => [
        'classname'     => 'block_corpusertimeline\external\get_timeline',
        'methodname'    => 'execute',
        'description'   => 'Get paginated timeline events for current user',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
