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
// Block 4 info.
$name = 'theme_edzsaas/block04info';
$heading = get_string('block04info', 'theme_edzsaas');
$information = get_string('block04infodesc', 'theme_edzsaas');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
// Enable or disable block 4 settings.
$name = 'theme_edzsaas/block04enabled';
$title = get_string('block04enabled', 'theme_edzsaas');
$description = get_string('block04enableddesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, '0');
$page->add($setting);
// Block 4 design select.
$name = 'theme_edzsaas/block04design';
$title = get_string('block04design', 'theme_edzsaas');
$description = get_string('block04designdesc', 'theme_edzsaas');
$default = 1;
$options = [];
for ($i = 1; $i < 3; $i++) {
     $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Block 4 header text.
$name = 'theme_edzsaas/block04header';
$title = get_string('block04header', 'theme_edzsaas');
$description = get_string('block04headerdesc', 'theme_edzsaas');
$default = get_string('block04headerdefault', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);
// Block 4 link button text and link.
$name = 'theme_edzsaas/block04button';
$title = get_string('block04button', 'theme_edzsaas');
$description = get_string('block04buttondesc', 'theme_edzsaas');
$default = get_string('block04buttondefault', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);

$name = 'theme_edzsaas/block04buttonlink';
$title = get_string('block04buttonlink', 'theme_edzsaas');
$description = get_string('block04buttonlinkdesc', 'theme_edzsaas');
$description = $description.get_string('underline', 'theme_edzsaas');
$default = get_string('block04buttonlinkdefault', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);

// Block 04 general settings END.
// ------------------------------------------------------------------------------------.
for ($i = 1; $i <= 8; $i++) {
     // Block 04 title.
     $name = 'theme_edzsaas/block04title'.$i;
     $title = get_string('block04title', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block04titledesc', 'theme_edzsaas');
     $default = 'Latest '.$i;
     $setting = new admin_setting_configtext($name, $title, $description, $default);
     $page->add($setting);
     // Block 04 caption.
     $name = 'theme_edzsaas/block04caption'.$i;
     $title = get_string('block04caption', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block04captiondesc', 'theme_edzsaas');
     $default = 'Latest caption'.$i;
     $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, '1', '2');
     $page->add($setting);
     // Block 04 img.
     $name = 'theme_edzsaas/sliderimageblock04img'.$i;
     $title = get_string('sliderimageblock04img', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block04imgdesc', 'theme_edzsaas');
     $setting = new admin_setting_configstoredfile($name, $title, $description, 'sliderimageblock04img'.$i);
     $setting->set_updatedcallback('theme_reset_all_caches');
     $page->add($setting);
     // Block 04 link.
     $name = 'theme_edzsaas/block04link'.$i;
     $title = get_string('block04link', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block04linkdesc', 'theme_edzsaas');
     $description = $description.get_string('underline', 'theme_edzsaas');
     $default = "https://edzlms.com/";
     $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
     $page->add($setting);
}
