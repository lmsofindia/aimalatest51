<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * TODO describe file index
 *
 * @package    local_cohortmanager
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

$url = new moodle_url('/local/cohortmanager/', []);
$PAGE->set_url($url);

$context = context_system::instance();
$PAGE->set_context($context);

// This page performs cohort management actions; restrict access accordingly.
require_capability('moodle/cohort:manage', $context);

$PAGE->set_heading($SITE->fullname);
$PAGE->requires->css(new moodle_url('/local/cohortmanager/amd/build/app.min.css'));
$PAGE->requires->js_call_amd('local_cohortmanager/app', 'init', ['#cohort-manager-app']);
echo $OUTPUT->header();
echo html_writer::div('','',['id' => 'cohort-manager-app']);
echo $OUTPUT->footer();
