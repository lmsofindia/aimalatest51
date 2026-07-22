<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Cache definitions (MUC).
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Assembled dashboard model keyed by teacher id (short-lived; heavy work is in the DB cache tables).
    'dashboard' => [
        'mode'          => cache_store::MODE_APPLICATION,
        'simplekeys'    => true,
        'simpledata'    => false,
        'staticacceleration' => true,
        'staticaccelerationsize' => 2,
        'ttl'           => 900,
    ],
];
