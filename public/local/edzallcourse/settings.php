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
 * Admin settings for local_edzallcourse.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_edzallcourse',
        get_string('pluginname', 'local_edzallcourse')
    );

    // Master enable.
    $settings->add(new admin_setting_configcheckbox(
        'local_edzallcourse/enable',
        get_string('enable', 'local_edzallcourse'),
        get_string('enable_desc', 'local_edzallcourse'),
        1
    ));

    // Courses per page.
    $settings->add(new admin_setting_configtext(
        'local_edzallcourse/perpage',
        get_string('perpage', 'local_edzallcourse'),
        get_string('perpage_desc', 'local_edzallcourse'),
        12,
        PARAM_INT
    ));

    // Default sort.
    $settings->add(new admin_setting_configselect(
        'local_edzallcourse/defaultsort',
        get_string('defaultsort', 'local_edzallcourse'),
        get_string('defaultsort_desc', 'local_edzallcourse'),
        'popular',
        [
            'popular' => get_string('sort_popular', 'local_edzallcourse'),
            'new'     => get_string('sort_new', 'local_edzallcourse'),
            'az'      => get_string('sort_az', 'local_edzallcourse'),
            'start'   => get_string('sort_start', 'local_edzallcourse'),
        ]
    ));

    // Category scope.
    $settings->add(new admin_setting_configselect(
        'local_edzallcourse/scope',
        get_string('scope', 'local_edzallcourse'),
        get_string('scope_desc', 'local_edzallcourse'),
        'recursive',
        [
            'recursive' => get_string('scope_recursive', 'local_edzallcourse'),
            'direct'    => get_string('scope_direct', 'local_edzallcourse'),
        ]
    ));

    // Card field toggles.
    $settings->add(new admin_setting_configcheckbox(
        'local_edzallcourse/showteacher',
        get_string('showteacher', 'local_edzallcourse'),
        get_string('showteacher_desc', 'local_edzallcourse'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_edzallcourse/showstartdate',
        get_string('showstartdate', 'local_edzallcourse'),
        get_string('showstartdate_desc', 'local_edzallcourse'),
        1
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_edzallcourse/showsummary',
        get_string('showsummary', 'local_edzallcourse'),
        get_string('showsummary_desc', 'local_edzallcourse'),
        1
    ));

    // Take over the core course index.
    $settings->add(new admin_setting_configcheckbox(
        'local_edzallcourse/takeovercourseindex',
        get_string('takeovercourseindex', 'local_edzallcourse'),
        get_string('takeovercourseindex_desc', 'local_edzallcourse'),
        1
    ));

    // Hero overrides.
    $settings->add(new admin_setting_configtext(
        'local_edzallcourse/herotitle',
        get_string('herotitle', 'local_edzallcourse'),
        get_string('herotitle_desc', 'local_edzallcourse'),
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtextarea(
        'local_edzallcourse/herotext',
        get_string('herotext', 'local_edzallcourse'),
        get_string('herotext_desc', 'local_edzallcourse'),
        '',
        PARAM_TEXT
    ));

    $ADMIN->add('localplugins', $settings);
}
