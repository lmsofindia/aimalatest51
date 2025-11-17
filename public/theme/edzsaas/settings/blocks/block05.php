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
// Block 5 info.
$name = 'theme_edzsaas/block05info';
$heading = get_string('block05info', 'theme_edzsaas');
$information = get_string('block05infodesc', 'theme_edzsaas');
$setting = new admin_setting_heading($name, $heading, $information);
$page->add($setting);
// Enable or disable block 5 settings.
$name = 'theme_edzsaas/block05enabled';
$title = get_string('block05enabled', 'theme_edzsaas');
$description = get_string('block05enableddesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$page->add($setting);
// Block 5 design select.
$name = 'theme_edzsaas/block05design';
$title = get_string('block05design', 'theme_edzsaas');
$description = get_string('block05designdesc', 'theme_edzsaas');
$default = 1;
$options = [];
for ($i = 1; $i < 2; $i++) {
     $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Block 5 img select.
$name = 'theme_edzsaas/sliderimageblock05img';
$title = get_string('sliderimageblock05img', 'theme_edzsaas');
$description = get_string('block05imgdesc', 'theme_edzsaas');
$setting = new admin_setting_configstoredfile($name, $title, $description, 'sliderimageblock05img');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Block 5 header text.
$name = 'theme_edzsaas/block05header';
$title = get_string('block05header', 'theme_edzsaas');
$description = get_string('block05headerdesc', 'theme_edzsaas');
$description = $description.get_string('underline', 'theme_edzsaas');
$default = get_string('block05headerdefault', 'theme_edzsaas');
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_TEXT);
$page->add($setting);
// Block 05 general settings END.
// ------------------------------------------------------------------------------------.
for ($i = 1; $i <= 3; $i++) {
     // Block 05 icon .
     $name = 'theme_edzsaas/block05icon'.$i;
     $title = get_string('block05icon', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block05icondesc', 'theme_edzsaas');
     $default = get_string('block05icondefault'.$i, 'theme_edzsaas');
     $options = [];
     $options[] = theme_edzsaas_get_core_icon_list();
     $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
     $setting->set_updatedcallback('theme_reset_all_caches');
     $page->add($setting);
     // Block 05 title.
     $name = 'theme_edzsaas/block05title'.$i;
     $title = get_string('block05title', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block05titledesc', 'theme_edzsaas');
     $default = 'Start Your Online Learning '.$i;
     $setting = new admin_setting_configtext($name, $title, $description, $default);
     $page->add($setting);
     // Block 05 caption.
     $name = 'theme_edzsaas/block05caption'.$i;
     $title = get_string('block05caption', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block05captiondesc', 'theme_edzsaas');
     $default = 'Get started with a diverse selection of courses, resources, and tools tailored to various learning needs. The platforms flexibility allows students to progress at their own pace.'.$i;
     $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, '1', '2');
     $page->add($setting);
     // Block 05 link .
     $name = 'theme_edzsaas/block05link'.$i;
     $title = get_string('block05link', 'theme_edzsaas', ['block' => $i]);
     $description = get_string('block05linkdesc', 'theme_edzsaas');
     $description = $description.get_string('underline', 'theme_edzsaas');
     $default = "https://edzlms.com/";
     $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_URL);
     $page->add($setting);
}
