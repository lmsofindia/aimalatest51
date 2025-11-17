<?php

/**
 * settings information for local_navsearch
 *
 * @package    local_navsearch
 * @copyright  2025 Edz Lms <marketing@edzlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_navsearch', get_string('pluginname', 'local_navsearch'));

    $settings->add(new admin_setting_configcheckbox(
        'local_navsearch/enabled',
        get_string('enabled', 'local_navsearch'),
        get_string('enabled_desc', 'local_navsearch'),
        1
    ));

    $areas = [
        'course_name' => get_string('search:course_name', 'local_navsearch'),
        'course_desc' => get_string('search:course_desc', 'local_navsearch'),
        'activity_name' => get_string('search:activity_name', 'local_navsearch'),
        'activity_desc' => get_string('search:activity_desc', 'local_navsearch'),
        'course_tag' => get_string('search:course_tag', 'local_navsearch'),
        'activity_tag' => get_string('search:activity_tag', 'local_navsearch'),
        'category_name' => get_string('search:category_name', 'local_navsearch'),
        'category_desc' => get_string('search:category_desc', 'local_navsearch'),
    ];

    $settings->add(new admin_setting_configmulticheckbox(
        'local_navsearch/searchareas',
        get_string('searchareas', 'local_navsearch'),
        get_string('searchareas_desc', 'local_navsearch'),
        [],
        $areas
    ));

    $ADMIN->add('localplugins', $settings);
}
