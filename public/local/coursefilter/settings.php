<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_coursefilter', get_string('pluginname', 'local_coursefilter'));

    // Enable/disable plugin.
    $settings->add(new admin_setting_configcheckbox(
        'local_coursefilter/enable',
        get_string('enable', 'local_coursefilter'),
        get_string('enable_desc', 'local_coursefilter'),
        1
    ));

    // Dynamically list all course custom fields.
    // We store admin choices per field shortname: local_coursefilter/field_<shortname>.
    try {
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $fields  = $handler ? $handler->get_fields() : [];
    } catch (Exception $e) {
        $fields = [];
    }

    if (!empty($fields)) {
        foreach ($fields as $field) {
            $shortname = $field->get('shortname');
            $label = $field->get_formatted_name();
            $settings->add(new admin_setting_configcheckbox(
                "local_coursefilter/field_{$shortname}",
                get_string('usefield', 'local_coursefilter', $label),
                get_string('usefield_desc', 'local_coursefilter', $label),
                0
            ));
        }
    } else {
        $settings->add(new admin_setting_heading(
            'local_coursefilter/nofields',
            get_string('nocoursecustomfields', 'local_coursefilter'),
            get_string('nocoursecustomfields_desc', 'local_coursefilter')
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
