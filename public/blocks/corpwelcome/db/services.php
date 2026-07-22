<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_corpwelcome_search_courses' => [
        'classname'     => 'block_corpwelcome\external\search_courses',
        'methodname'    => 'execute',
        'description'   => 'Search courses and categories for the welcome block',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => '',
    ],
];
