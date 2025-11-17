<?php
$functions = array(
    'local_edzworkplacerpt_get_myteam_report' => array(
        'classname'   => 'local_edzworkplacerpt\\external\\api',
        'methodname'  => 'get_myteam_report',
        'classpath'   => '',
        'description' => 'Get my team report',
        'type'        => 'read',
    ),
    'local_edzworkplacerpt_send_message' => array(
        'classname'   => 'local_edzworkplacerpt\\external\\api',
        'methodname'  => 'send_message',
        'classpath'   => '',
        'description' => 'Send message to selected users',
        'type'        => 'write',
        'ajax'        => true,
    ),
);

$services = array(
    'Edz Workplace Report service' => array(
        'functions' => array_keys($functions),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);
