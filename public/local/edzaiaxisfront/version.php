<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin version information.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_edzaiaxisfront';
$plugin->version   = 2026072114;   // 2026-07-22 v1.1.9o: FIX quiz sync writing wrong columns (question_hash/answer -> question_text/correct_answer/axis_question_id); save_quiz_edit correct_answer; hide blank-text quiz rows
$plugin->requires  = 2022112800;   // Moodle 4.1+ minimum (tested through 5.1)
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '1.1.9o';

// PHP 8.2+ required (Moodle 5.0+ mandate; also required for named arguments
// and intersection types used internally).
// Moodle itself enforces this — this comment is for developer reference only.
