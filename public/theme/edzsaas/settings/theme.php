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
 * Theme edzsaas theme.
 *
 * @package   theme_edzsaas
 * @copyright 2025 ThemesEdzsaas  - https://edzlms.com/
 * @author    ThemesEDzsaas - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// Theme settings.
$page = new admin_settingpage('theme_edzsaas_theme', get_string('themesettings', 'theme_edzsaas'));
// Back color.
$name = 'theme_edzsaas/backcolor';
$title = get_string('backcolor', 'theme_edzsaas');
$description = get_string('backcolor_desc', 'theme_edzsaas');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Must add the page after definiting all the settings!
// Login page position select.
$name = 'theme_edzsaas/loginposition';
$title = get_string('loginposition', 'theme_edzsaas');
$description = get_string('loginpositiondesc', 'theme_edzsaas');
$default = "center";
$options = [
    'Center' => 'Center',
    'flex-start' => 'Left',
    'flex-end' => 'Right',
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Dashboard footer select.
$name = 'theme_edzsaas/footerselect';
$title = get_string('footerselect', 'theme_edzsaas');
$description = get_string('footerselectdesc', 'theme_edzsaas');
$default = "3";
$options = [
    '1' => 'Moodle footer',
    '2' => 'Frontpage footer',
    '3' => 'Social media footer',
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$settings->add($page);
