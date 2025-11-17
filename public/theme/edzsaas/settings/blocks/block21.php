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


defined('MOODLE_INTERNAL') || die();
global $CFG;

// If plan is NOT business, hide settings page completely
// $default_enable=0;
// if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
//     return;
// } else{
//     $default_enable=1;
// }

$name = 'theme_edzsaas/block21info';
$heading = get_string('block21info', 'theme_edzsaas');
$information = get_string('block21infodesc', 'theme_edzsaas');

$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
// Passing default value as 0 so that it defaults to zero
// Enable/Disable Block 21.
$name = 'theme_edzsaas/block21enabled';
$title = get_string('block21enabled', 'theme_edzsaas');
$description = get_string('block21enableddesc', 'theme_edzsaas');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$page->add($setting);

// Block 21 background colour.
$name = 'theme_edzsaas/block21bgcolor';
$title = get_string('block21bgcolor', 'theme_edzsaas');
$description = get_string('block21bgcolordesc', 'theme_edzsaas');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#dee2e6');
$page->add($setting);

// Block 21 caption.
$name = 'theme_edzsaas/block21caption';
$title = get_string('block21caption', 'theme_edzsaas');
$description = get_string('block21captiondesc', 'theme_edzsaas');
$default='Our Achievements';
$setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);

// Block 21 action button text.
$name = 'theme_edzsaas/block21buttontext';
$title = get_string('block21buttontext', 'theme_edzsaas');
$description = get_string('block21buttontextdesc', 'theme_edzsaas');
$default='All Courses';
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);

// Block 21 button URL.
$name = 'theme_edzsaas/block21buttonurl';
$title = get_string('block21buttonurl', 'theme_edzsaas');
$description = get_string('block21buttonurldesc', 'theme_edzsaas');
$default='course/';
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
$page->add($setting);

// Counter 1 value.
$name = 'theme_edzsaas/block21_counter1';
$title = get_string('block21_counter1', 'theme_edzsaas');
$description = get_string('block21_counter1_desc', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, '126', PARAM_INT);
$page->add($setting);

// Counter 1 description.
$name = 'theme_edzsaas/block21_desc1';
$title = get_string('block21_desc1', 'theme_edzsaas');
$description = get_string('block21_desc1_desc', 'theme_edzsaas');
$default='Happy Clients';
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);

// Counter 2 value.
$name = 'theme_edzsaas/block21_counter2';
$title = get_string('block21_counter2', 'theme_edzsaas');
$description = get_string('block21_counter2_desc', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, '535', PARAM_INT);
$page->add($setting);

// Counter 2 description.
$name = 'theme_edzsaas/block21_desc2';
$title = get_string('block21_desc2', 'theme_edzsaas');
$description = get_string('block21_desc2_desc', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, 'Awards', PARAM_TEXT);
$page->add($setting);

// Counter 3 value.
$name = 'theme_edzsaas/block21_counter3';
$title = get_string('block21_counter3', 'theme_edzsaas');
$description = get_string('block21_counter3_desc', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, '896', PARAM_INT);
$page->add($setting);

// Counter 3 description.
$name = 'theme_edzsaas/block21_desc3';
$title = get_string('block21_desc3', 'theme_edzsaas');
$description = get_string('block21_desc3_desc', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, 'Total Hours', PARAM_TEXT);
$page->add($setting);

// Counter 4 value.
$name = 'theme_edzsaas/block21_counter4';
$title = get_string('block21_counter4', 'theme_edzsaas');
$description = get_string('block21_counter4_desc', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, '777', PARAM_INT);
$page->add($setting);

// Counter 4 description.
$name = 'theme_edzsaas/block21_desc4';
$title = get_string('block21_desc4', 'theme_edzsaas');
$description = get_string('block21_desc4_desc', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, 'Projects Complete', PARAM_TEXT);
$page->add($setting);
