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
// Block 03 info.
$name = 'theme_edzsaas/block03info';
$heading = get_string('block03info', 'theme_edzsaas');
$information = get_string('block03infodesc', 'theme_edzsaas');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
// Enable or disable block 03 settings.
$name = 'theme_edzsaas/block03enabled';
$title = get_string('block03enabled', 'theme_edzsaas');
$description = get_string('block03enableddesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$page->add($setting);

// Block 3 design select.
$name = 'theme_edzsaas/block03design';
$title = get_string('block03design', 'theme_edzsaas');
$description = get_string('block03designdesc', 'theme_edzsaas');
$default = 1;
$options = [];
for ($i = 1; $i < 4; $i++) {
     $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$name = 'theme_edzsaas/block03header';
$title = get_string('block03header', 'theme_edzsaas');
$description = get_string('block03headerdesc', 'theme_edzsaas');
$description = $description . get_string('underline', 'theme_edzsaas');
$default = get_string('block03headerdefault', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);

// Block 03 general settings END.
// ------------------------------------------------------------------------------------.
for ($i = 1; $i <= 6; $i++) {
     // Block 03 icon .
     $name = 'theme_edzsaas/block03icon' . $i;
     $title = get_string('block03icon', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block03icondesc', 'theme_edzsaas');
     $default = get_string('block03icondefault' . $i, 'theme_edzsaas');
     $options = [];
     $options[] = theme_edzsaas_get_core_icon_list();
     $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
     $setting->set_updatedcallback('theme_reset_all_caches');
     $page->add($setting);
     // Block 03 title.
     $name = 'theme_edzsaas/block03title' . $i;
     $title = get_string('block03title', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block03titledesc', 'theme_edzsaas');
     $default = 'Online Classes';
     $setting = new admin_setting_configtext($name, $title, $description, $default);
     $page->add($setting);
     // Block 03 caption.
     $name = 'theme_edzsaas/block03caption' . $i;
     $title = get_string('block03caption', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block03captiondesc', 'theme_edzsaas');
     $default = get_string('block03captiondefault', 'theme_edzsaas');
     $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
     $page->add($setting);
     // Block 03 link.
     $name = 'theme_edzsaas/block03link' . $i;
     $title = get_string('block03link', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block03linkdesc', 'theme_edzsaas');
     $description = $description . get_string('underline', 'theme_edzsaas');
     $default = get_string('buttonlink', 'theme_edzsaas');
     $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
     $page->add($setting);
}
