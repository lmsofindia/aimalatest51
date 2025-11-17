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
 * Theme edzsaas frontpage block setting.
 *
 * @package   theme_edzsaas
 * @copyright 2025 ThemesEdzsaas  - https://edzlms.com/
 * @author    ThemesEDzsaas - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$page = new admin_settingpage('theme_edzsaas_frontpage', get_string('frontpageedzsaas', 'theme_edzsaas'));
// Frontpage heading select.
$page->add(new admin_setting_heading('theme_edzsaas_frontpagenav', get_string('frontpagenav', 'theme_edzsaas'),
format_text(get_string('frontpagenavdesc', 'theme_edzsaas'), FORMAT_MARKDOWN)));
$name = 'theme_edzsaas/frontpagenavchoice';
$title = get_string('frontpagenavchoice', 'theme_edzsaas');
$description = get_string('frontpagenavchoicedesc', 'theme_edzsaas');
$default = 1;
$options = [];
for ($i = 1; $i < 4; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Add container to navigation bar.
$name = 'theme_edzsaas/navbarcontainer';
$title = get_string('navbarcontainer', 'theme_edzsaas');
$description = get_string('navbarcontainerdesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 0);
$page->add($setting);
// Frontpage header logo select.
$name = 'theme_edzsaas/headerlogo';
$title = get_string('headerlogo', 'theme_edzsaas');
$description = get_string('headerlogodesc', 'theme_edzsaas');
$default = "Logo";
$options = [
    'Logo' => 'Logo',
    'Compact logo' => 'Compact logo',
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Navbar back color.
$name = 'theme_edzsaas/navbarcolor';
$title = get_string('navbarcolor', 'theme_edzsaas');
$description = get_string('navbarcolor_desc', 'theme_edzsaas');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Menu light-dark.
$name = 'theme_edzsaas/frontpagenavlightdark';
$title = get_string('frontpagenavlightdark', 'theme_edzsaas');
$description = get_string('frontpagenavlightdarkdesc', 'theme_edzsaas');
$default = "navbar-light";
$options = [];
$options = [
    'navbar-light' => 'navbar-light',
    'navbar-dark' => 'navbar-dark',
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Frontpage nav link area.
$name = 'theme_edzsaas/frontpagenavlink';
$title = get_string('frontpagenavlink', 'theme_edzsaas');
$description = get_string('frontpagenavlinkdesc', 'theme_edzsaas');
$default = get_string('frontpagenavlinkdefault', 'theme_edzsaas');
$setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, '1', '6');
$page->add($setting);
// Frontpage header 3 phone number.
$name = 'theme_edzsaas/header3phone';
$title = get_string('header3phone', 'theme_edzsaas');
$description = get_string('header3phonedesc', 'theme_edzsaas');
$default = "";
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
$page->add($setting);
// Frontpage choice.
$page->add(new admin_setting_heading('theme_edzsaas_frontpagehead', get_string('frontpageheading', 'theme_edzsaas'),
format_text(get_string('frontpagedesc', 'theme_edzsaas'), FORMAT_MARKDOWN)));

// Frontpage design select.
$name = 'theme_edzsaas/frontpagechoice';
$title = get_string('frontpagechoice', 'theme_edzsaas');
$description = get_string('frontpagechoicedesc', 'theme_edzsaas');
$default = 1;
$options = [];
$options = [];
for ($i = 1; $i <= 1; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Frontpage color select.
$name = 'theme_edzsaas/frontpagecolor';
$title = get_string('frontpagecolor', 'theme_edzsaas');
$description = get_string('frontpagecolordesc', 'theme_edzsaas');
$default = '#4272d7';
$options = [];
$options = [
    '#4272d7' => '1',
    '#f98012' => '2',
    '#fa4251' => '3',
    '#c45e28' => '4',
    '#63c76a' => '5',
    '#024E64' => '6',
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Frontpage color palet.
$name = 'theme_edzsaas/sitecolor';
$title = get_string('sitecolor', 'theme_edzsaas');
$description = get_string('sitecolor_desc', 'theme_edzsaas');
$setting = new admin_setting_configcolourpicker($name, $title, $description, '#442490');
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$page->add(new admin_setting_heading('theme_edzsaas_frontpageend', get_string('frontpageend', 'theme_edzsaas'),
format_text(get_string('frontpageenddesc', 'theme_edzsaas'), FORMAT_MARKDOWN)));
$settings->add($page);
