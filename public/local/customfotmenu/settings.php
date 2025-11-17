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
 * Settings for local_customfotmenu plugin.
 *
 * @package    local_customfotmenu
 * @copyright  2025 customfotmenu  - https://edzlms.com/
 * @author    ThemesEDzsaas - Developer Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;
// Only add settings if admin tree is loaded.
if ($hassiteconfig) { // Only add settings if admin tree is loaded.

    // If plan is NOT business, hide settings page completely
    // $default_enable=0;
    // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
    //     return;
    // } else{
    //     $default_enable=1;
    // }

    // Create a settings page under "Local plugins".
    $settings = new admin_settingpage(
        'local_customfotmenu',
        get_string('pluginname', 'local_customfotmenu')
    );

    // Enable/disable panel menu setting (default 0 = disabled).
    $settings->add(new admin_setting_configcheckbox(
        'local_customfotmenu/enable',
        get_string('enable', 'local_customfotmenu'),
        get_string('enable_desc', 'local_customfotmenu'),
        1
    ));

    // Panel menu background color (default black).
    $settings->add(new admin_setting_configcolourpicker(
        'local_customfotmenu/bgcolor',
        get_string('bgcolor', 'local_customfotmenu'),
        get_string('bgcolor_desc', 'local_customfotmenu'),
        '#000000'
    ));

    // Panel menu text color (default white).
    $settings->add(new admin_setting_configcolourpicker(
        'local_customfotmenu/textcolor',
        get_string('textcolor', 'local_customfotmenu'),
        get_string('textcolor_desc', 'local_customfotmenu'),
        '#ffffff'
    ));

    // Add this settings page to the admin tree.
    $ADMIN->add('localplugins', $settings);
}
