<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_customenrol', get_string('pluginname', 'local_customenrol'));

    $settings->add(new admin_setting_configcheckbox(
        'local_customenrol/enabled',
        get_string('enabled', 'local_customenrol'),
        get_string('enabled_desc', 'local_customenrol'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'local_customenrol/layout',
        get_string('layout', 'local_customenrol'),
        get_string('layout_desc', 'local_customenrol'),
        1,
        [1 => get_string('layout1', 'local_customenrol'), 2 => get_string('layout2', 'local_customenrol')]
    ));

    // Optional: a fallback preview video URL if no course-level custom field is present.
    $settings->add(new admin_setting_configtext(
        'local_customenrol/defaultpreview',
        get_string('preview', 'local_customenrol'),
        'Optional default preview video URL (mp4/YouTube/Vimeo embed).',
        ''
    ));

    $ADMIN->add('localplugins', $settings);
}
