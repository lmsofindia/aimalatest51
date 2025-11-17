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

// ==============================


defined('MOODLE_INTERNAL') || die();
global $CFG;
$page = new admin_settingpage('theme_edzsaas_hero', get_string('heroedzsaas', 'theme_edzsaas'));
//print_r($CFG->plan); die();
// If plan is NOT business, hide settings page completely
// $default_enable=0;
// if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
//    // echo "rashid";die();
//     return;
// } else{
//     // echo "rashidss";die();
//     $default_enable=1;
// }

// Hero section heading.
$page->add(new admin_setting_heading('theme_edzsaas_heropagenav', get_string('heropagenav', 'theme_edzsaas'),
    format_text(get_string('heropagenavdesc', 'theme_edzsaas'), FORMAT_MARKDOWN)));

// Passing default value as 0 so that it defaults to zero
// Enable or disable the hero block.
$name = 'theme_edzsaas/heroenabled';
$title = get_string('heroenabled', 'theme_edzsaas');
$description = get_string('heroenableddesc', 'theme_edzsaas');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);

// Hero design select.
$name = 'theme_edzsaas/herodesign';
$title = get_string('herodesign', 'theme_edzsaas');
$description = get_string('herodesigndesc', 'theme_edzsaas');
$default = 2;
$options = [
    1 => 'Design 1',
    2 => 'Design 2',
    3 => 'Design 3',
    4 => 'Design 4',
];
$setting = new admin_setting_configselect($name, $title, $description, $default, $options);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// hero bg color
$page->add(new admin_setting_configcolourpicker(
    'theme_edzsaas/herobgcolor',
    get_string('herobgcolor', 'theme_edzsaas'),
    get_string('herobgcolordesc', 'theme_edzsaas'),
    '#FFFFFF'   
));

// Hero title color
$page->add(new admin_setting_configcolourpicker(
    'theme_edzsaas/herotitlecolor',
    get_string('herotitlecolor', 'theme_edzsaas'),
    get_string('herotitlecolordesc', 'theme_edzsaas'),
   '#FFFFFF'
));

// Hero description color
$page->add(new admin_setting_configcolourpicker(
    'theme_edzsaas/herodesccolor',
    get_string('herodesccolor', 'theme_edzsaas'),
    get_string('herodesccolordesc', 'theme_edzsaas'),
    '#FFFFFF'
));

// Hero button background color
$page->add(new admin_setting_configcolourpicker(
    'theme_edzsaas/herobtnbgcolor',
    get_string('herobtnbgcolor', 'theme_edzsaas'),
    get_string('herobtnbgcolordesc', 'theme_edzsaas'),
    ''
));

// Hero button text color
$page->add(new admin_setting_configcolourpicker(
    'theme_edzsaas/herobtntextcolor',
    get_string('herobtntextcolor', 'theme_edzsaas'),
    get_string('herobtntextcolordesc', 'theme_edzsaas'),
    '#ffffff'
));

// Hero button hover background color
$page->add(new admin_setting_configcolourpicker(
    'theme_edzsaas/herobtnhoverbg',
    get_string('herobtnhoverbg', 'theme_edzsaas'),
    get_string('herobtnhoverbgdesc', 'theme_edzsaas'),
    ''
));

// Hero background overlay color
$page->add(new admin_setting_configcolourpicker(
    'theme_edzsaas/herobgoverlay',
    get_string('herobgoverlay', 'theme_edzsaas'),
    get_string('herobgoverlaydesc', 'theme_edzsaas'),
    ''
));


// 🎯 HERO 1 SECTION
$page->add(new admin_setting_heading('theme_edzsaas_herohead', get_string('hero1', 'theme_edzsaas'),
    format_text(get_string('hero1desc', 'theme_edzsaas'), FORMAT_MARKDOWN)));

// Title
$page->add(new admin_setting_configtext(
    'theme_edzsaas/nicehero1title',
    get_string('nicehero1title', 'theme_edzsaas'),
    get_string('nicehero1title_desc', 'theme_edzsaas'),
    'Welcome to EDZ LMS'
));

// Description
$page->add(new admin_setting_configtextarea(
    'theme_edzsaas/nicehero1desc',
    get_string('nicehero1desc', 'theme_edzsaas'),
    get_string('nicehero1desc_desc', 'theme_edzsaas'),
    'Learn, grow, and succeed with our platform.'
));

// Background color 
$page->add(new admin_setting_configcolourpicker(
    'theme_edzsaas/nicehero1bg',
    get_string('nicehero1bg', 'theme_edzsaas'),
    get_string('nicehero1bg_desc', 'theme_edzsaas'),
     '#ffffff' 
));

// Image
$page->add(new admin_setting_configstoredfile(
    'theme_edzsaas/nicehero1image',
    get_string('nicehero1image', 'theme_edzsaas'),
    get_string('nicehero1image_desc', 'theme_edzsaas'),
    'nicehero1image'
));

// 🎯 HERO 2 SECTION
$page->add(new admin_setting_heading('theme_edzsaas_hero2', get_string('hero2heading', 'theme_edzsaas'),
    format_text(get_string('hero2desc', 'theme_edzsaas'), FORMAT_MARKDOWN)));

$page->add(new admin_setting_configstoredfile(
    'theme_edzsaas/hero2bg',
    get_string('hero2bg', 'theme_edzsaas'),
    get_string('hero2bgdesc', 'theme_edzsaas'),
    'hero2bg'
));

$page->add(new admin_setting_configtext(
    'theme_edzsaas/hero2title',
    get_string('hero2title', 'theme_edzsaas'),
    get_string('hero2titledesc', 'theme_edzsaas'),
    'Explore Courses',
    PARAM_TEXT
));

$page->add(new admin_setting_configtextarea(
    'theme_edzsaas/hero2desc',
    get_string('hero2text', 'theme_edzsaas'),
    get_string('hero2textdesc', 'theme_edzsaas'),
    'Find courses that match your learning goals.'
));

$page->add(new admin_setting_configcheckbox(
    'theme_edzsaas/hero2searchtoggle',
    get_string('hero2searchtoggle', 'theme_edzsaas'),
    get_string('hero2searchtoggledesc', 'theme_edzsaas'),
    1
));

$page->add(new admin_setting_configtext(
    'theme_edzsaas/hero2searchplaceholder',
    get_string('hero2searchplaceholder', 'theme_edzsaas'),
    get_string('hero2searchplaceholderdesc', 'theme_edzsaas'),
    'Search...',
    PARAM_TEXT
));


// 🎯 HERO 3 SECTION
$page->add(new admin_setting_heading('theme_edzsaas_hero3', get_string('hero3heading', 'theme_edzsaas'),
    format_text(get_string('hero3desc', 'theme_edzsaas'), FORMAT_MARKDOWN)));

$page->add(new admin_setting_configstoredfile(
    'theme_edzsaas/hero3bg',
    get_string('hero3bg', 'theme_edzsaas'),
    get_string('hero3bgdesc', 'theme_edzsaas'),
    'hero3bg'
));

$page->add(new admin_setting_configtext(
    'theme_edzsaas/hero3title',
    get_string('hero3title', 'theme_edzsaas'),
    get_string('hero3titledesc', 'theme_edzsaas'),
    'Achieve Your Dreams',
    PARAM_TEXT
));

$page->add(new admin_setting_configtext(
    'theme_edzsaas/hero3btntext',
    get_string('hero3btntext', 'theme_edzsaas'),
    get_string('hero3btntextdesc', 'theme_edzsaas'),
    'Get Started',
    PARAM_TEXT
));


// 🎯 HERO 4 SECTION
$page->add(new admin_setting_heading('theme_edzsaas_hero4', get_string('hero4heading', 'theme_edzsaas'),
    format_text(get_string('hero4desc', 'theme_edzsaas'), FORMAT_MARKDOWN)));

$page->add(new admin_setting_configstoredfile(
    'theme_edzsaas/hero4bg',
    get_string('hero4bg', 'theme_edzsaas'),
    get_string('hero4bgdesc', 'theme_edzsaas'),
    'hero4bg'
));

$page->add(new admin_setting_configtext(
    'theme_edzsaas/hero4title',
    get_string('hero4title', 'theme_edzsaas'),
    get_string('hero4titledesc', 'theme_edzsaas'),
    'Skill Up Today!',
    PARAM_TEXT
));

$page->add(new admin_setting_configtextarea(
    'theme_edzsaas/hero4desc',
    get_string('hero4text', 'theme_edzsaas'),
    get_string('hero4textdesc', 'theme_edzsaas'),
    'Join a global community of learners.'
));

$settings->add($page);


