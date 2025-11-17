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
 * Theme edzsaas frontpage block.
 *
 * @package   theme_edzsaas
 * @copyright 20225 Rashid  - https://edzlms.com/
 * @author    EDzlms - Edzlms Team
 * @license   https://edzlms.com/
 */


// ==============================
//  Rashid:Counter Block Settings as on 11-07-25
// ==============================

$page = new admin_settingpage('theme_edzsaas_counter', get_string('counterinfo', 'theme_edzsaas'));

$setting = new admin_setting_heading(
    'theme_edzsaas/counterheading',
    get_string('counterinfo', 'theme_edzsaas'),
    get_string('counterinfodesc', 'theme_edzsaas')
);
$page->add($setting);

// Enable or disable the Counter block.
$name = 'theme_edzsaas/counterenabled';
$title = get_string('counterenabled', 'theme_edzsaas');
$description = get_string('counterenableddesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$page->add($setting);

// Background Color
$name = 'theme_edzsaas/counterbgcolor';
$title = get_string('counterbgcolor', 'theme_edzsaas');
$description = get_string('counterbgcolordesc', 'theme_edzsaas');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#ffffff');
$page->add($setting);

// Text Color
$name = 'theme_edzsaas/countertextcolor';
$title = get_string('countertextcolor', 'theme_edzsaas');
$description = get_string('countertextcolordesc', 'theme_edzsaas');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#000000');
$page->add($setting);

// Titles 1 to 4
for ($i = 1; $i <= 4; $i++) {
    $name = "theme_edzsaas/countertitle$i";
    $title = get_string("countertitle$i", 'theme_edzsaas');
    $description = get_string("countertitle{$i}desc", 'theme_edzsaas');
    $setting = new admin_setting_configtext($name, $title, $description, "Title $i", PARAM_TEXT);
    $page->add($setting);
}

// Numbers 1 to 4
for ($i = 1; $i <= 4; $i++) {
    $name = "theme_edzsaas/counternumber$i";
    $title = get_string("counternumber$i", 'theme_edzsaas');
    $description = get_string("counternumber{$i}desc", 'theme_edzsaas');
    $setting = new admin_setting_configtext($name, $title, $description, "0", PARAM_INT);
    $page->add($setting);
}

$settings->add($page);

