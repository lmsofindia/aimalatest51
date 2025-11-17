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
require_once($CFG->dirroot . '/course/lib.php');

// If plan is NOT business, hide settings page completely
// $default_enable=0;
// if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
//     return;
// } else{
//     $default_enable=1;
// }

$page = new admin_settingpage('theme_edzsaas_fcourse', get_string('fcourse', 'theme_edzsaas'));

// fcourse section heading.
$page->add(new admin_setting_heading('theme_edzsaas_fcoursenav', get_string('fcoursenav', 'theme_edzsaas'),
    format_text(get_string('fcoursenavnavdesc', 'theme_edzsaas'), FORMAT_MARKDOWN)));

// Passing default value as 0 so that it defaults to zero
// Enable or disable the fcourse block.
$name = 'theme_edzsaas/fcourseenabled';
$title = get_string('fcourseenabled', 'theme_edzsaas');
$description = get_string('fcourseenableddesc', 'theme_edzsaas');
$default = 1;
$setting = new admin_setting_configcheckbox($name, $title, $description, $default);
$setting->set_updatedcallback('theme_reset_all_caches');
$page->add($setting);


// for all active course
global $DB;
$sql = "SELECT id, fullname 
        FROM {course} 
        WHERE visible = 1 AND category != 0 
        ORDER BY fullname ASC";

$courses = $DB->get_records_sql($sql);

foreach ($courses as $course) {
    $context = context_course::instance($course->id);
    $courseoptions[$course->id] = format_string($course->fullname, true, ['context' => $context]);
}


// Add the dropdown setting
$page->add(new admin_setting_configselect(
    'theme_edzsaas/featuredcourseid',
    get_string('featuredcourseid', 'theme_edzsaas'),
    get_string('featuredcourseiddesc', 'theme_edzsaas'),
    0,
    $courseoptions
));


// Background color of course card
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/coursecardbg',
    get_string('coursecardbg', 'theme_edzsaas'),
    get_string('coursecardbgdesc', 'theme_edzsaas'),
    '#f6f6f6'
));

// Title text
$page->add(new admin_setting_configtext('theme_edzsaas/coursecardtitle',
    get_string('coursecardtitle', 'theme_edzsaas'),
    get_string('coursecardtitledesc', 'theme_edzsaas'),
    'Featured Course',
    PARAM_TEXT
));

// Title  text color
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/coursecardtitleclr',
    get_string('coursecardtitleclr', 'theme_edzsaas'),
    get_string('coursecardtitleclrdesc', 'theme_edzsaas'),
    '#0066cc'
));

// Subtitle text
$page->add(new admin_setting_configtext('theme_edzsaas/coursecardsubtitle',
    get_string('coursecardsubtitle', 'theme_edzsaas'),
    get_string('coursecardsubtitledesc', 'theme_edzsaas'),
    'Start Learning Today',
    PARAM_TEXT
));

// subtitle  text color
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/coursecardsubtitleclr',
    get_string('coursecardsubtitleclr', 'theme_edzsaas'),
    get_string('coursecardsubtitleclrdesc', 'theme_edzsaas'),
    '#0066cc'
));


// Course name text color
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/coursenamecolor',
    get_string('coursenamecolor', 'theme_edzsaas'),
    get_string('coursenamecolordesc', 'theme_edzsaas'),
    '#000000'
));


// Course description text
$page->add(new admin_setting_configtextarea('theme_edzsaas/coursecarddesc',
    get_string('coursecarddesc', 'theme_edzsaas'),
    get_string('coursecarddescdesc', 'theme_edzsaas'),
    'Explore this featured course to boost your skills!',
    PARAM_TEXT
));

// Course description text color
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/coursecarddesccolor',
    get_string('coursecarddesccolor', 'theme_edzsaas'),
    get_string('coursecarddesccolordesc', 'theme_edzsaas'),
    '#333333'
));
// Enrolled students color
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/enrolledcolor',
    get_string('enrolledcolor', 'theme_edzsaas'),
    get_string('enrolledcolordesc', 'theme_edzsaas'),
    '#888888'
));

// Date text color
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/datecolor',
    get_string('datecolor', 'theme_edzsaas'),
    get_string('datecolordesc', 'theme_edzsaas'),
    '#666666'
));

// View button background
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/viewbtnbg',
    get_string('viewbtnbg', 'theme_edzsaas'),
    get_string('viewbtnbgdesc', 'theme_edzsaas'),
    '#0066cc'
));

// View button text color
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/viewbtntextcolor',
    get_string('viewbtntextcolor', 'theme_edzsaas'),
    get_string('viewbtntextcolordesc', 'theme_edzsaas'),
    '#ffffff'
));

// View button text
$page->add(new admin_setting_configtext('theme_edzsaas/viewbtntext',
    get_string('viewbtntext', 'theme_edzsaas'),
    get_string('viewbtntextdesc', 'theme_edzsaas'),
    'View Course',
    PARAM_TEXT
));

// Course box background color
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/courseboxbgclr',
    get_string('courseboxbgclr', 'theme_edzsaas'),
    get_string('courseboxbgclrdesc', 'theme_edzsaas'),
    '#ffffff'
));

// Background color for category, date, and enrolled info blocks.
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/courseinfobgcolor',
    get_string('courseinfobgcolor', 'theme_edzsaas'),
    get_string('courseinfobgcolordesc', 'theme_edzsaas'),
    '#f6f6f6' 
));

// Color for Font Awesome icons in course info blocks.
$page->add(new admin_setting_configcolourpicker('theme_edzsaas/courseinfotexticoncolor',
    get_string('courseinfotexticoncolor', 'theme_edzsaas'),
    get_string('courseinfotexticoncolordesc', 'theme_edzsaas'),
    '#000' // Default: bLACK
));


$settings->add($page);


