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
// Block 19 info.
$name = 'theme_edzsaas/block19info';
$heading = get_string('block19info', 'theme_edzsaas');
$information = get_string('block19infodesc', 'theme_edzsaas');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
// Enable or disable block 19 settings.
$name = 'theme_edzsaas/block19enabled';
$title = get_string('block19enabled', 'theme_edzsaas');
$description = get_string('block19enableddesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$page->add($setting);
// Block 19 design select.
$name = 'theme_edzsaas/block19design';
$title = get_string('block19design', 'theme_edzsaas');
$description = get_string('block19designdesc', 'theme_edzsaas');
$default = 1;
$options = [];
for ($i = 1; $i < 3; $i++) {
     $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Enable or disable block 19 header settings.
$name = 'theme_edzsaas/block19headerenabled';
$title = get_string('block19headerenabled', 'theme_edzsaas');
$description = get_string('block19headerenableddesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$page->add($setting);
// Block 19 header.
$name = 'theme_edzsaas/block19header';
$title = get_string('block19header', 'theme_edzsaas');
$description = get_string('block19headerdesc', 'theme_edzsaas');
$default = get_string('block19headerdefault', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);
// Block 19 caption.
$name = 'theme_edzsaas/block19caption';
$title = get_string('block19caption', 'theme_edzsaas');
$description = get_string('block19captiondesc', 'theme_edzsaas');
$description = $description.get_string('underline', 'theme_edzsaas');
$default = get_string('block19captiondefault', 'theme_edzsaas');
$setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, '1', '2');
$page->add($setting);
// Block 19 general settings END.
// ------------------------------------------------------------------------------------.
for ($i = 1; $i <= 6; $i++) {
     // Block 19 img.
     $name = 'theme_edzsaas/sliderimageblock19img'.$i;
     $title = get_string('sliderimageblock19img', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block19imgdesc', 'theme_edzsaas');
     $setting = new admin_setting_configstoredfile($name, $title, $description, 'sliderimageblock19img'.$i);
     $setting->set_updatedcallback('theme_reset_all_caches');
     $page->add($setting);
     // Block 19 link .
     $name = 'theme_edzsaas/block19link'.$i;
     $title = get_string('block19link', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block19linkdesc', 'theme_edzsaas');
     $description = $description.get_string('underline', 'theme_edzsaas');
     $default = get_string('buttonlink', 'theme_edzsaas');
     $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
     $page->add($setting);
}
