<?php
defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'suspicioussession' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
        ],
        'capability' => 'quizaccess/edproctoring:viewreport',
    ],
];
