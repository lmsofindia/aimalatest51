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
 * Settings for local_edzallcourse plugin.
 *
 * @package    local_edzallcourse
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_edzallcourse',
        get_string('pluginname', 'local_edzallcourse')
    );
    $settings->add(new admin_setting_configcheckbox(
        'local_edzallcourse/enable',
        get_string('enable', 'local_edzallcourse'),
        get_string('enable_desc', 'local_edzallcourse'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}


