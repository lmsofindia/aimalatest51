<?php
defined('MOODLE_INTERNAL') || die();
$functions = [
    'block_corpusercmplchart_get_data' => [
        'classname'     => 'block_corpusercmplchart\external\get_completion_data',
        'methodname'    => 'execute',
        'description'   => 'Get course completion data for the completion chart block',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
