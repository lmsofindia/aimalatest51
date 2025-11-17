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
 * Theme edzsaas page.
 *
 * @package   theme_edzsaas
 * @copyright 2025 ThemesEdzsaas  - https://edzlms.com/
 * @author    ThemesEDzsaas - Developer Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$page = new admin_settingpage('theme_edzsaas_edzsaaspage', get_string('edzsaaspage', 'theme_edzsaas'));
$page->add(new admin_setting_heading('theme_edzsaas_edzsaaspage', get_string('edzsaaspageheading', 'theme_edzsaas'),
format_text(get_string('edzsaaspageheadingdesc', 'theme_edzsaas'), FORMAT_MARKDOWN)));
// Enable or disable page settings.
$name = 'theme_edzsaas/edzsaaspageenabled';
$title = get_string('edzsaaspageenabled', 'theme_edzsaas');
$description = get_string('edzsaaspageenableddesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Count page settings.
$name = 'theme_edzsaas/edzsaaspagecount';
$title = get_string('edzsaaspagecount', 'theme_edzsaas');
$description = get_string('edzsaaspagecountdesc', 'theme_edzsaas');
$default = 1;
$options = [];
for ($i = 1; $i <= 10; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// If we don't have an slide yet, default to the preset.
$edzsaaspagecount = get_config('theme_edzsaas', 'edzsaaspagecount');
if (!$edzsaaspagecount) {
    $edzsaaspagecount = 2;
}
for ($count = 1; $count <= $edzsaaspagecount; $count++) {
    $name = 'theme_edzsaas/edzsaaspage' . $count . 'info';
    $heading = get_string('edzsaaspageno', 'theme_edzsaas', ['edzsaaspage' => $count]);
    $information = get_string('edzsaaspagenodesc', 'theme_edzsaas', ['edzsaaspage' => $count]);
    $setting = new admin_setting_heading($name, $heading, $information);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Page title.
    $name = 'theme_edzsaas/edzsaaspagetitle' . $count;
    $title = get_string('edzsaaspagetitle', 'theme_edzsaas');
    $description = get_string('edzsaaspagetitledesc', 'theme_edzsaas');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Page caption.
    $name = 'theme_edzsaas/edzsaaspagecap' . $count;
    $title = get_string('edzsaaspagecaption', 'theme_edzsaas');
    $description = get_string('edzsaaspagecaptiondesc', 'theme_edzsaas');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Page css link.
    $name = 'theme_edzsaas/edzsaaspagecsslink' . $count;
    $title = get_string('edzsaaspagecsslink', 'theme_edzsaas');
    $description = get_string('edzsaaspagecsslinkdesc', 'theme_edzsaas');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, '1', '1');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Page img folder link.
    $name = 'theme_edzsaas/edzsaaspageimglink' . $count;
    $title = get_string('edzsaaspageimglink', 'theme_edzsaas');
    $description = get_string('edzsaaspageimglinkdesc', 'theme_edzsaas');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, '1', '1');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Page css.
    $name = 'theme_edzsaas/edzsaaspagecss' . $count;
    $title = get_string('edzsaaspagecss', 'theme_edzsaas');
    $description = get_string('edzsaaspagecssdesc', 'theme_edzsaas');
    $default = '';
    $setting = new admin_setting_scsscode($name, $title, $description, $default, PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Add navbar to info page.
    $name = 'theme_edzsaas/edzsaaspagenavbar'. $count;
    $title = get_string('edzsaaspagenavbar', 'theme_edzsaas');
    $description = get_string('edzsaaspagenavbardesc', 'theme_edzsaas');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Add header to info page.
    $name = 'theme_edzsaas/edzsaaspageheader'. $count;
    $title = get_string('edzsaaspageheader', 'theme_edzsaas');
    $description = get_string('edzsaaspageheaderdesc', 'theme_edzsaas');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Add footer to info page.
    $name = 'theme_edzsaas/edzsaaspagefooter'. $count;
    $title = get_string('edzsaaspagefooter', 'theme_edzsaas');
    $description = get_string('edzsaaspagefooterdesc', 'theme_edzsaas');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
}
// Simple page.
$name = 'theme_edzsaas/edzsaaspageheadingsimple';
$heading = get_string('edzsaaspageheadingsimple', 'theme_edzsaas');
$information = get_string('edzsaaspageheadingsimpledesc', 'theme_edzsaas');
$setting = new admin_setting_heading($name, $heading, $information);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Enable or disable page settings.
$name = 'theme_edzsaas/edzsaaspageenabledsimple';
$title = get_string('edzsaaspageenabledsimple', 'theme_edzsaas');
$description = get_string('edzsaaspageenabledsimpledesc', 'theme_edzsaas');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// Count page settings.
$name = 'theme_edzsaas/edzsaaspagecountsimple';
$title = get_string('edzsaaspagecountsimple', 'theme_edzsaas');
$description = get_string('edzsaaspagecountsimpledesc', 'theme_edzsaas');
$default = 1;
$options = [];
for ($i = 1; $i <= 10; $i++) {
    $options[$i] = $i;
}
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);
// If we don't have an page yet, default to the preset.
$edzsaaspagecount = get_config('theme_edzsaas', 'edzsaaspagecountsimple');
if (!$edzsaaspagecount) {
    $edzsaaspagecount = 2;
}
for ($count = 1; $count <= $edzsaaspagecount; $count++) {
    $name = 'theme_edzsaas/edzsaaspagesimple' . $count . 'info';
    $heading = get_string('edzsaaspagenosimple', 'theme_edzsaas', ['edzsaaspage' => $count]);
    $information = get_string('edzsaaspagenosimpledesc', 'theme_edzsaas', ['edzsaaspage' => $count]);
    $setting = new admin_setting_heading($name, $heading, $information);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Page title.
    $name = 'theme_edzsaas/edzsaaspagetitlesimple' . $count;
    $title = get_string('edzsaaspagetitlesimple', 'theme_edzsaas');
    $description = get_string('edzsaaspagetitlesimpledesc', 'theme_edzsaas');
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_TEXT);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Page image.
    $fileid = 'sliderimageedzsaaspagesimple'.$count;
    $name = 'theme_edzsaas/sliderimageedzsaaspagesimple'.$count;
    $title = get_string('edzsaaspageimagesimple', 'theme_edzsaas');
    $description = get_string('edzsaaspageimagesimpledesc', 'theme_edzsaas');
    $opts = ['accepted_types' => ['.png', '.jpg', '.gif', '.webp', '.tiff', '.svg'], 'maxfiles' => 1];
    $setting = new admin_setting_configstoredfile($name, $title, $description, $fileid,  0, $opts);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Count page settings.
    $name = 'theme_edzsaas/edzsaaspageimgpositionsimple'.$count;
    $title = get_string('edzsaaspageimgpositionsimple', 'theme_edzsaas');
    $description = get_string('edzsaaspageimgpositionsimpledesc', 'theme_edzsaas');
    $default = 1;
    $options = [
        "1" => "Background",
        "2" => "Top",
        "21" => "Full Top",
        "3" => "Left",
        "4" => "Right",
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Page caption.
    $name = 'theme_edzsaas/edzsaaspagecapsimple'.$count;
    $title = get_string('edzsaaspagecaptionsimple', 'theme_edzsaas');
    $description = get_string('edzsaaspagecaptionsimpledesc', 'theme_edzsaas');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Add header to info page.
    $name = 'theme_edzsaas/edzsaaspageheadersimple'. $count;
    $title = get_string('edzsaaspageheadersimple', 'theme_edzsaas');
    $description = get_string('edzsaaspageheadersimpledesc', 'theme_edzsaas');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Add footer to info page.
    $name = 'theme_edzsaas/edzsaaspagefootersimple'. $count;
    $title = get_string('edzsaaspagefootersimple', 'theme_edzsaas');
    $description = get_string('edzsaaspagefootersimpledesc', 'theme_edzsaas');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
}
$page->add(new admin_setting_heading('theme_edzsaas_edzsaaspageend', get_string('edzsaaspageend', 'theme_edzsaas'),
format_text(get_string('edzsaaspageenddesc', 'theme_edzsaas'), FORMAT_MARKDOWN)));
$settings->add($page);
