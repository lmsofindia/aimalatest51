<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Version details for mod_edzsession.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_edzsession';
$plugin->version   = 2026080401;        // YYYYMMDDXX.
$plugin->requires  = 2024100700;        // Moodle 4.5+ (works on 5.0+).
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.2';           // Calendar sync; backup disabled (no session backup).
$plugin->cron      = 0;
