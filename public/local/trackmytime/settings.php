<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin settings for local_trackmytime.
 *
 * @package    local_trackmytime
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_trackmytime', get_string('pluginname', 'local_trackmytime'));

    $settings->add(new admin_setting_configcheckbox(
        'local_trackmytime/enabletracking',
        get_string('enabletracking', 'local_trackmytime'),
        get_string('enabletracking_desc', 'local_trackmytime'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_trackmytime/showstudyheatmap',
        get_string('showstudyheatmap', 'local_trackmytime'),
        get_string('showstudyheatmap_desc', 'local_trackmytime'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}
