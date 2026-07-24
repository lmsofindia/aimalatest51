<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Admin settings for mod_edzsession.
 *
 * Global policies live here. Each provider contributes its OWN settings via
 * its static add_settings() — so a new provider self-registers its config with
 * no edits to this file (beyond it already looping over the registry).
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_edzsession\local\provider_manager;

// Register the credential-vault management page in the admin tree (site config).
// Added outside fulltree so the node always appears; access is capability-gated.
if ($hassiteconfig) {
    $ADMIN->add('modsettings', new admin_externalpage(
        'edzsessionaccounts',
        get_string('manageaccounts', 'mod_edzsession'),
        new moodle_url('/mod/edzsession/manage_accounts.php'),
        'mod/edzsession:manageaccounts'
    ));
    $ADMIN->add('modsettings', new admin_externalpage(
        'edzsessiontestconnections',
        get_string('testconnections', 'mod_edzsession'),
        new moodle_url('/mod/edzsession/test_connections.php'),
        'mod/edzsession:manageaccounts'
    ));
}

if ($ADMIN->fulltree) {

    // ---- Provider selection --------------------------------------------------
    $settings->add(new admin_setting_heading('mod_edzsession/providers_heading',
        get_string('settings_providers', 'mod_edzsession'),
        get_string('settings_providers_desc', 'mod_edzsession')));

    $settings->add(new admin_setting_configselect('mod_edzsession/defaultmeetingprovider',
        get_string('setting_defaultmeeting', 'mod_edzsession'),
        get_string('setting_defaultmeeting_desc', 'mod_edzsession'),
        'zoom', provider_manager::meeting_provider_menu()));

    $settings->add(new admin_setting_configselect('mod_edzsession/defaultstorageprovider',
        get_string('setting_defaultstorage', 'mod_edzsession'),
        get_string('setting_defaultstorage_desc', 'mod_edzsession'),
        'none', provider_manager::storage_provider_menu()));

    // ---- Global attendance policy -------------------------------------------
    $settings->add(new admin_setting_heading('mod_edzsession/attendance_heading',
        get_string('settings_attendance', 'mod_edzsession'), ''));

    $settings->add(new admin_setting_configselect('mod_edzsession/attendancebasis',
        get_string('setting_attendancebasis', 'mod_edzsession'),
        get_string('setting_attendancebasis_desc', 'mod_edzsession'),
        'meeting_duration', [
            'meeting_duration' => get_string('basis_meeting', 'mod_edzsession'),
            'scheduled_duration' => get_string('basis_scheduled', 'mod_edzsession'),
        ]));

    $settings->add(new admin_setting_configselect('mod_edzsession/matchstrategy',
        get_string('setting_matchstrategy', 'mod_edzsession'),
        get_string('setting_matchstrategy_desc', 'mod_edzsession'),
        'email', [
            'email' => get_string('match_email', 'mod_edzsession'),
            'registrantid' => get_string('match_registrantid', 'mod_edzsession'),
            'name' => get_string('match_name', 'mod_edzsession'),
        ]));

    // ---- Global recording policy --------------------------------------------
    $settings->add(new admin_setting_heading('mod_edzsession/recording_heading',
        get_string('settings_recording', 'mod_edzsession'), ''));

    $settings->add(new admin_setting_configselect('mod_edzsession/sourcedeletionpolicy',
        get_string('setting_deletionpolicy', 'mod_edzsession'),
        get_string('setting_deletionpolicy_desc', 'mod_edzsession'),
        'never', [
            'never' => get_string('deletion_never', 'mod_edzsession'),
            'after_verified' => get_string('deletion_verified', 'mod_edzsession'),
            'after_verified_grace' => get_string('deletion_verified_grace', 'mod_edzsession'),
        ]));

    $settings->add(new admin_setting_configtext('mod_edzsession/gracehours',
        get_string('setting_gracehours', 'mod_edzsession'),
        get_string('setting_gracehours_desc', 'mod_edzsession'), 48, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_edzsession/retentiondays',
        get_string('setting_retentiondays', 'mod_edzsession'),
        get_string('setting_retentiondays_desc', 'mod_edzsession'), 0, PARAM_INT));

    // ---- Per-provider settings (self-registered from the registry) ----------
    // This loop is why adding a provider needs no edit here: each driver's
    // add_settings() paints its own fields.
    foreach (provider_manager::storage_providers() as $name => $class) {
        $class::add_settings($settings);
    }
}
