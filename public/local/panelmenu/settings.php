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
 * Settings for local_panelmenu plugin.
 *
 * @package   local_panelmenu
 * @copyright 2025 Rashid
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

if ($hassiteconfig) {

    // If plan is NOT business, hide settings page completely
    // $default_enable=0;
    // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
    //     return;
    // } else{
    //     $default_enable=1;
    // }

    $settings = new admin_settingpage(
        'local_panelmenu',
        get_string('pluginname', 'local_panelmenu')
    );
    
    // Passing default value as 0 so that it defaults to zero
    $settings->add(new admin_setting_configcheckbox(
        'local_panelmenu/enable',
        get_string('enable', 'local_panelmenu'),
        get_string('enable_desc', 'local_panelmenu'),
        1
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_panelmenu/bgcolor',
        get_string('bgcolor', 'local_panelmenu'),
        get_string('bgcolor_desc', 'local_panelmenu'),
        '#F7F7F7'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_panelmenu/headingcolor',
        get_string('headingcolor', 'local_panelmenu'),
        get_string('headingcolor_desc', 'local_panelmenu'),
        '#000000'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_panelmenu/textcolor',
        get_string('textcolor', 'local_panelmenu'),
        get_string('textcolor_desc', 'local_panelmenu'),
        '#000000'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_panelmenu/hovercolor',
        get_string('hovercolor', 'local_panelmenu'),
        get_string('hovercolor_desc', 'local_panelmenu'),
        '#2841FA'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'local_panelmenu/hoverbgcolor',
        get_string('hoverbgcolor', 'local_panelmenu'),
        get_string('hoverbgcolor_desc', 'local_panelmenu'),
        '#011755'
    ));

    $ADMIN->add('localplugins', $settings);
}
