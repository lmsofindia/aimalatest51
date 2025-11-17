<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_edzworkplacerpt', get_string('pluginname', 'local_edzworkplacerpt'));

    // AddeD By rashid as on 22-10-25
    // $settings->add(
    //     new admin_setting_heading(
    //         'local_edzworkplacerpt/redirect',
    //         '',
    //         html_writer::link(
    //             new moodle_url('/local/edzworkplacerpt/index.php'),
    //             get_string('gotoplugin', 'local_edzworkplacerpt')
    //         )
    //     )
    // );




    $settings->add(new admin_setting_configcheckbox(
        'local_edzworkplacerpt/enabled',
        get_string('enabled', 'local_edzworkplacerpt'),
        get_string('enabled_desc', 'local_edzworkplacerpt'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_edzworkplacerpt/use_email_mapping',
        get_string('use_email_mapping', 'local_edzworkplacerpt'),
        get_string('use_email_mapping_desc', 'local_edzworkplacerpt'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_edzworkplacerpt/manager_mapped_field',
        get_string('manager_mapped_field', 'local_edzworkplacerpt'),
        get_string('manager_mapped_field_desc', 'local_edzworkplacerpt'),
        'manageremail'
    ));

    $settings->add(new admin_setting_configtext(
        'local_edzworkplacerpt/manager_mapped_idfield',
        get_string('manager_mapped_idfield', 'local_edzworkplacerpt'),
        get_string('manager_mapped_idfield_desc', 'local_edzworkplacerpt'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_edzworkplacerpt/enable_charts',
        get_string('enable_charts', 'local_edzworkplacerpt'),
        '',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_edzworkplacerpt/enable_manager_email',
        get_string('enable_manager_email', 'local_edzworkplacerpt'),
        '',
        0
    ));

    $ADMIN->add('localplugins', $settings);
}
