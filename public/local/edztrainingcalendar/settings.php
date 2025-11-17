<?php
// settings.php - Admin settings for local_edztrainingcalendar
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a settings page under "Plugins -> Local plugins"
    $settings = new admin_settingpage(
        'local_edztrainingcalendar_settings',
        get_string('settingsheading', 'local_edztrainingcalendar')
    );

    // Enable/Disable checkbox
    $settings->add(new admin_setting_configcheckbox(
        'local_edztrainingcalendar/enable',
        get_string('enable', 'local_edztrainingcalendar'),
        get_string('enable_desc', 'local_edztrainingcalendar'),
        1 // default enabled
    ));

    // Preferred enrolment method dropdown
    $enroloptions = [
        'manual' => get_string('enrol_manual', 'local_edztrainingcalendar'),
        'cohort' => get_string('enrol_cohort', 'local_edztrainingcalendar'),
        'self'   => get_string('enrol_self', 'local_edztrainingcalendar'),
        'guest'  => get_string('enrol_guest', 'local_edztrainingcalendar'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_edztrainingcalendar/preferred_enrol',
        get_string('preferredenrol', 'local_edztrainingcalendar'),
        get_string('preferredenrol_desc', 'local_edztrainingcalendar'),
        'manual',
        $enroloptions
    ));

    // Default role selection (optional): choose role by id
    // Show a text field to input role shortname or id if needed
    $settings->add(new admin_setting_configtext(
        'local_edztrainingcalendar/default_role',
        get_string('defaultrole', 'local_edztrainingcalendar'),
        get_string('defaultrole_desc', 'local_edztrainingcalendar'),
        'student',
        PARAM_RAW_TRIMMED
    ));

    // Add the settings page to the plugins/local category.
    $ADMIN->add('localplugins', $settings);

    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_edztrainingcalendar_report',
            get_string('reportheading', 'local_edztrainingcalendar'),
            new moodle_url('/local/edztrainingcalendar/report.php'),
            'local/edztrainingcalendar:report'
        )
    );
    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_edztrainingcalendar_upload',
            get_string('csvuploadheading', 'local_edztrainingcalendar'),
            new moodle_url('/local/edztrainingcalendar/index.php'),
            'local/edztrainingcalendar:manage'
        )
    );
}
