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
 * CSS Settings File
 *
 * @package   theme_edzsaas
 * @copyright 2024 Eduardo Kraus https://eduardokraus.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $PAGE, $CFG;

$fontslist = \theme_edzsaas\fonts\font_util::site();

// $description = "";
// foreach ($fontslist as $font) {
//     $description .= "<div style='font-family:\"{$font}\";font-size:1.2em'>
//                          <a href='https://fonts.google.com/specimen/{$font}'
//                             target='_blank' style='font-family:\"{$font}\"'>{$font}</a>
//                          - \"Lorem ipsum dolor sit amet.
//                             <strong style='font-family:\"{$font}\"'>strong</strong>
//                             <em style='font-family:\"{$font}\"'>em</em>
//                             <strong><em style='font-family:\"{$font}\"'>strong/em</em></strong>\"</div>";
// }


// If plan is NOT business, hide settings page completely
// $default_enable=0;
// if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
//     return;
// } else{
//     $default_enable=1;
// }

$page = new admin_settingpage(
    'theme_edzsaas_css',
    get_string('settings_css_heading', 'theme_edzsaas')
);


$setting = new admin_setting_heading(
    "theme_edzsaas/fonts",
    get_string('fontpreview', 'theme_edzsaas'),
    "<blockquote>{$description}</blockquote>"
);
$page->add($setting);

$setting = new admin_setting_configselect(
    'theme_edzsaas/fontfamily_title',
    get_string('fontfamily_title', 'theme_edzsaas'),
    get_string('fontfamily_title_desc', 'theme_edzsaas'),
    'Saira',
    $fontslist
);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$setting = new admin_setting_configselect(
    'theme_edzsaas/fontfamily',
    get_string('fontfamily', 'theme_edzsaas'),
    get_string('fontfamily_desc', 'theme_edzsaas'),
    'Saira',
    $fontslist
);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$setting = new admin_setting_configselect(
    'theme_edzsaas/fontfamily_menus',
    get_string('fontfamily_menus', 'theme_edzsaas'),
    get_string('fontfamily_menus_desc', 'theme_edzsaas'),
    'Saira',
    $fontslist
);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$setting = new admin_setting_configselect(
    'theme_edzsaas/fontfamily_sitename',
    get_string('fontfamily_sitename', 'theme_edzsaas'),
    get_string('fontfamily_sitename_desc', 'theme_edzsaas'),
    'Saira',
    $fontslist
);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$icon = $OUTPUT->image_url("google-fonts", "theme_edzsaas")->out(false);
$extra = "<br><a href=\"https://fonts.google.com/selection/embed\"
                 target=\"google\">Embed code Page</a><br><img src=\"{$icon}\"
                 style=\"max-width: 100%;width: 420px;\">";
$setting = new admin_setting_configtextarea(
    'theme_edzsaas/sitefonts',
    get_string('sitefonts', 'theme_edzsaas'),
    get_string('sitefonts_desc', 'theme_edzsaas') . $extra,
    ""
);
$page->add($setting);

$setting = new admin_setting_configtextarea(
    'theme_edzsaas/customcss',
    get_string('customcss', 'theme_edzsaas'),
    get_string('customcss_desc', 'theme_edzsaas'),
    ''
);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

$settings->add($page);
