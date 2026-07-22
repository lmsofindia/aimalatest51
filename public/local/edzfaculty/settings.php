<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Admin settings.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_edzfaculty', get_string('pluginname', 'local_edzfaculty'));
    $ADMIN->add('localplugins', $settings);

    // Terminology (academic for AIMA, corporate for GAIL).
    $settings->add(new admin_setting_configselect(
        'local_edzfaculty/terminology',
        get_string('terminology', 'local_edzfaculty'),
        get_string('terminology_desc', 'local_edzfaculty'),
        'academic',
        [
            'academic'  => get_string('term_academic', 'local_edzfaculty'),
            'corporate' => get_string('term_corporate', 'local_edzfaculty'),
        ]
    ));

    // Who gets the admin "All Faculty" overview (and cross-teacher access).
    $settings->add(new admin_setting_configselect(
        'local_edzfaculty/overviewaccess',
        get_string('overviewaccess', 'local_edzfaculty'),
        get_string('overviewaccess_desc', 'local_edzfaculty'),
        'manager',
        [
            'admin'   => get_string('oa_admin', 'local_edzfaculty'),
            'manager' => get_string('oa_manager', 'local_edzfaculty'),
        ]
    ));

    // At-risk thresholds.
    $settings->add(new admin_setting_heading('local_edzfaculty/atriskhdr',
        get_string('atriskhdr', 'local_edzfaculty'), ''));
    $settings->add(new admin_setting_configtext('local_edzfaculty/minattendance',
        get_string('minattendance', 'local_edzfaculty'),
        get_string('minattendance_desc', 'local_edzfaculty'), '60', PARAM_INT));
    $settings->add(new admin_setting_configtext('local_edzfaculty/maxloginstale',
        get_string('maxloginstale', 'local_edzfaculty'),
        get_string('maxloginstale_desc', 'local_edzfaculty'), '10', PARAM_INT));
    $settings->add(new admin_setting_configtext('local_edzfaculty/minscore',
        get_string('minscore', 'local_edzfaculty'),
        get_string('minscore_desc', 'local_edzfaculty'), '40', PARAM_INT));
    $settings->add(new admin_setting_configtext('local_edzfaculty/scoredrop',
        get_string('scoredrop', 'local_edzfaculty'),
        get_string('scoredrop_desc', 'local_edzfaculty'), '15', PARAM_INT));
    $settings->add(new admin_setting_configtext('local_edzfaculty/maxmisseddeadlines',
        get_string('maxmisseddeadlines', 'local_edzfaculty'),
        get_string('maxmisseddeadlines_desc', 'local_edzfaculty'), '2', PARAM_INT));

    // Attendance source.
    $settings->add(new admin_setting_heading('local_edzfaculty/attendhdr',
        get_string('attendhdr', 'local_edzfaculty'), ''));
    $settings->add(new admin_setting_configselect(
        'local_edzfaculty/attendancesource',
        get_string('attendancesource', 'local_edzfaculty'),
        get_string('attendancesource_desc', 'local_edzfaculty'),
        'auto',
        [
            'auto'       => get_string('attendsrc_auto', 'local_edzfaculty'),
            'attendance' => get_string('attendsrc_attendance', 'local_edzfaculty'),
            'zoom'       => get_string('attendsrc_zoom', 'local_edzfaculty'),
            'off'        => get_string('attendsrc_off', 'local_edzfaculty'),
        ]
    ));
    $settings->add(new admin_setting_configtext('local_edzfaculty/zoomminminutes',
        get_string('zoomminminutes', 'local_edzfaculty'),
        get_string('zoomminminutes_desc', 'local_edzfaculty'), '5', PARAM_INT));

    // Cache / engagement window.
    $settings->add(new admin_setting_heading('local_edzfaculty/cachehdr',
        get_string('cachehdr', 'local_edzfaculty'), ''));
    $settings->add(new admin_setting_configtext('local_edzfaculty/refreshthrottle',
        get_string('refreshthrottle', 'local_edzfaculty'),
        get_string('refreshthrottle_desc', 'local_edzfaculty'), '30', PARAM_INT));
    $settings->add(new admin_setting_configtext('local_edzfaculty/engagementdays',
        get_string('engagementdays', 'local_edzfaculty'),
        get_string('engagementdays_desc', 'local_edzfaculty'), '30', PARAM_INT));

    // AI features master toggle (Phase 2 wires the models).
    $settings->add(new admin_setting_configcheckbox('local_edzfaculty/showai',
        get_string('showai', 'local_edzfaculty'),
        get_string('showai_desc', 'local_edzfaculty'), 1));
}
