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
 * Parent theme: boost
 *
 * @package   theme_edzsaas
 * @copyright 2025 ThemesEdzsaas  - https://edzlms.com/
 * @author    ThemesEDzsaas - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Block 22 added by proteek 12/08/25

defined('MOODLE_INTERNAL') || die();
global $CFG;

// If plan is NOT business, hide settings page completely
// $default_enable=0;
// if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
//     return;
// } else{
//     $default_enable=1;
// }

$name = 'theme_edzsaas/block22info';
$heading = get_string('block22info', 'theme_edzsaas');
$information = get_string('block22infodesc', 'theme_edzsaas');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);

// Passing default value as 0 so that it defaults to zero
// Enable/Disable Block 22.
$name = 'theme_edzsaas/block22enabled';
$title = get_string('block22enabled', 'theme_edzsaas');
$description = get_string('block22enableddesc', 'theme_edzsaas');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

// Block 22 title.
$name = 'theme_edzsaas/block22title';
$title = get_string('block22title', 'theme_edzsaas');
$description = get_string('block22titledesc', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, 'Frequently Asked Questions', PARAM_TEXT);
$page->add($setting);

// Block 22 background colour.
$name = 'theme_edzsaas/block22bgcolor';
$title = get_string('block22bgcolor', 'theme_edzsaas');
$description = get_string('block22bgcolordesc', 'theme_edzsaas');
$default='#f5f5f5';
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
$page->add($setting);

// Add 10 question/answer fields with pre-filled values.
$questions = [
    "What is the capital of Iceland?",
    "Which gas do plants primarily take in for photosynthesis?",
    "Who painted the Mona Lisa?",
    "What is the square root of 144?",
    "Which planet is known as the Red Planet?",
    "In computing, what does “HTTP” stand for?",
    "Who wrote Romeo and Juliet?",
    "How many bones are in the adult human body?",
    "What is the chemical symbol for gold?",
    "Which ocean is the largest in the world?"
];

$answers = [
    "Reykjavík.",
    "Carbon dioxide (CO₂).",
    "Leonardo da Vinci.",
    "12.",
    "Mars.",
    "HyperText Transfer Protocol.",
    "William Shakespeare.",
    "206 bones.",
    "Au.",
    "The Pacific Ocean."
];

for ($i = 1; $i <= 10; $i++) {
    $name = 'theme_edzsaas/block22question' . $i;
    $title = get_string('block22question', 'theme_edzsaas', $i);
    $description = get_string('block22questiondesc', 'theme_edzsaas', $i);
    $setting = new admin_setting_configtext($name, $title, $description, $questions[$i - 1], PARAM_TEXT);
    $page->add($setting);

    // Answer
    $name = 'theme_edzsaas/block22answer' . $i;
    $title = get_string('block22answer', 'theme_edzsaas', $i);
    $description = get_string('block22answerdesc', 'theme_edzsaas', $i);
    $setting = new admin_setting_configtextarea($name, $title, $description, $answers[$i - 1], PARAM_RAW);
    $page->add($setting);
}