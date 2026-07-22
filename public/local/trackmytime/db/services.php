<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Web service definitions for local_trackmytime.
 *
 * @package    local_trackmytime
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_trackmytime_record_session' => [
        'classname'     => 'local_trackmytime\external\record_session',
        'description'   => 'Records seconds spent on a course module for the current session.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => '',
        'loginrequired' => true,
    ],
];

$services = [];
