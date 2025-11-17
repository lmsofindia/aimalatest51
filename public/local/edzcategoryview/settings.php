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
 * TODO describe file settings
 *
 * @package    local_edzcategoryview
 * @copyright  2025 Edz Lms <marketing@edzlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();



if ($hassiteconfig ) {
    $settings = new admin_settingpage(
        'local_edzcategoryview',
        get_string('pluginname', 'local_edzcategoryview')
    );

    // ---------------------------
    // Department Section Settings
    // ---------------------------

    // Get only top-level categories (parent = 0).
     $settings->add(new admin_setting_configcheckbox(
        'local_edzcategoryview/enabledepartment',
        get_string('enabledepartment', 'local_edzcategoryview'),
        get_string('enabledepartment_desc', 'local_edzcategoryview'),
        0
    ));


    $topcategories = $DB->get_records_menu('course_categories', ['parent' => 0], 'name ASC', 'id, name');

    $settings->add(new admin_setting_configselect(
        'local_edzcategoryview/departmentcategoryid',
        get_string('departmentcategory', 'local_edzcategoryview'),
        get_string('departmentcategory_desc', 'local_edzcategoryview'),
        0,
        $topcategories
    ));

   
    $settings->add(new admin_setting_configtext(
        'local_edzcategoryview/departmenttitle',
        get_string('departmenttitle', 'local_edzcategoryview'),
        '',
        get_string('defaultdepartmenttitle', 'local_edzcategoryview')
    ));

    $settings->add(new admin_setting_configtext(
        'local_edzcategoryview/departmentsubtitle',
        get_string('departmentsubtitle', 'local_edzcategoryview'),
        '',
        get_string('defaultdepartmentsubtitle', 'local_edzcategoryview')
    ));
     $settings->add(new admin_setting_configcolourpicker(
        'local_edzcategoryview/departmentbgcolor',
        get_string('departmentbgcolor', 'local_edzcategoryview'),
        '',
        '#b1d0ec'
    ));

       $settings->add(new admin_setting_configcolourpicker(
        'local_edzcategoryview/departmenttitlecolor',
        get_string('departmenttitlecolor', 'local_edzcategoryview'),
        '',
        '#b1d0ec'
    ));


    

    // ---------------------------
    // Topic Section Settings
    // ---------------------------
    $settings->add(new admin_setting_configcheckbox(
        'local_edzcategoryview/enabletopic',
        get_string('enabletopic', 'local_edzcategoryview'),
        get_string('enabletopic_desc', 'local_edzcategoryview'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_edzcategoryview/topictitle',
        get_string('topictitle', 'local_edzcategoryview'),
        '',
        get_string('defaulttopictitle', 'local_edzcategoryview')
    ));

    $settings->add(new admin_setting_configtext(
        'local_edzcategoryview/topicsubtitle',
        get_string('topicsubtitle', 'local_edzcategoryview'),
        '',
        get_string('defaulttopicsubtitle', 'local_edzcategoryview')
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_edzcategoryview/topicbgcolor',
        get_string('topicbgcolor', 'local_edzcategoryview'),
        '',
        '#b1d0ec'
    ));

     $settings->add(new admin_setting_configcolourpicker(
        'local_edzcategoryview/topictitlecolor',
        get_string('topictitlecolor', 'local_edzcategoryview'),
        '',
        '#b1d0ec'
    ));


    $ADMIN->add('localplugins', $settings);
}
