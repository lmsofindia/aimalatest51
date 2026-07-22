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
 * Admin settings for local_reportpanel.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_reportpanel',
        get_string('pluginname', 'local_reportpanel'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading('local_reportpanel/general',
        get_string('settings_general', 'local_reportpanel'), ''));

    // Panel heading text.
    $settings->add(new admin_setting_configtext('local_reportpanel/heading',
        get_string('settings_heading', 'local_reportpanel'),
        get_string('settings_heading_desc', 'local_reportpanel'),
        get_string('panelheading', 'local_reportpanel'), PARAM_TEXT));

    $settings->add(new admin_setting_heading('local_reportpanel/cards',
        get_string('settings_cards', 'local_reportpanel'),
        get_string('settings_cards_desc', 'local_reportpanel')));

    // Each external card: a URL and an enable toggle.
    $externalcards = [
        'leaderboard' => '/blocks/xp/index.php/ladder/1',
        'logreport'   => '/report/log/index.php?id=0',
        'teamreports' => '/local/edzteams/index.php',
        'badges'      => '/badges/mybadges.php',
    ];
    foreach ($externalcards as $key => $default) {
        $settings->add(new admin_setting_configcheckbox("local_reportpanel/enable_{$key}",
            get_string("card_{$key}", 'local_reportpanel'),
            get_string('settings_enablecard_desc', 'local_reportpanel'), 1));
        $settings->add(new admin_setting_configtext("local_reportpanel/url_{$key}",
            get_string('settings_url', 'local_reportpanel',
                get_string("card_{$key}", 'local_reportpanel')),
            get_string('settings_url_desc', 'local_reportpanel'), $default, PARAM_RAW_TRIMMED));
    }

    $settings->add(new admin_setting_heading('local_reportpanel/engagement',
        get_string('settings_engagement', 'local_reportpanel'), ''));

    // Active/inactive threshold (days) used by the Engagement report.
    $settings->add(new admin_setting_configtext('local_reportpanel/activedays',
        get_string('settings_activedays', 'local_reportpanel'),
        get_string('settings_activedays_desc', 'local_reportpanel'), 7, PARAM_INT));
}
