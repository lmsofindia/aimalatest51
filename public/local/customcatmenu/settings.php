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
 * Settings for local_customcatmenu plugin.
 *
 * @package   local_customcatmenu
 * @copyright 2025 Rashid
* @license    https://edzlms.com/
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
// Only add settings if admin tree is loaded.
if ($hassiteconfig) {

    // If plan is NOT business, hide settings page completely
    // $default_enable=0;
    // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
    //     return;
    // } else{
    //     $default_enable=1;
    // }

    // Create a settings page under "Local plugins".
    $settings = new admin_settingpage(
        'local_customcatmenu',
        get_string('pluginname', 'local_customcatmenu')
    );

    // Passing default value as 0 so that it defaults to zero
    // Enable/disable panel menu setting.
    $settings->add(new admin_setting_configcheckbox(
        'local_customcatmenu/enable',
        get_string('enable', 'local_customcatmenu'),
        get_string('enable_desc', 'local_customcatmenu'),
        1
    ));

    // Panel menu background color.
    $settings->add(new admin_setting_configcolourpicker(
        'local_customcatmenu/bgcolor',
        get_string('bgcolor', 'local_customcatmenu'),
        get_string('bgcolor_desc', 'local_customcatmenu'),
        '#000000'
    ));

    // Panel menu text color.
    $settings->add(new admin_setting_configcolourpicker(
        'local_customcatmenu/textcolor',
        get_string('textcolor', 'local_customcatmenu'),
        get_string('textcolor_desc', 'local_customcatmenu'),
        '#ffffff'
    ));

    // Add this settings page to the admin tree.
    $ADMIN->add('localplugins', $settings);
}
