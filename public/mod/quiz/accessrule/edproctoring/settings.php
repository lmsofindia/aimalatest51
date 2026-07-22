<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Admin settings for quizaccess_edproctoring.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'quizaccess_edproctoring',
        get_string('pluginname', 'quizaccess_edproctoring')
    );

    // -----------------------------------------------------------------------
    // Global plugin enable / disable.
    // When disabled, the plugin will not activate for any quiz regardless of
    // per-quiz settings.
    // -----------------------------------------------------------------------
    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_edproctoring/plugin_enabled',
        get_string('plugin_enabled', 'quizaccess_edproctoring'),
        get_string('plugin_enabled_desc', 'quizaccess_edproctoring'),
        0   // Off by default — admin must explicitly enable.
    ));

    // Default capture interval (seconds between snapshots).
    $settings->add(new admin_setting_configtext(
        'quizaccess_edproctoring/default_capture_interval',
        get_string('defaultcaptureinterval', 'quizaccess_edproctoring'),
        get_string('defaultcaptureinterval_desc', 'quizaccess_edproctoring'),
        30,
        PARAM_INT
    ));

    // Image retention period (days before snapshots are auto-deleted).
    $settings->add(new admin_setting_configtext(
        'quizaccess_edproctoring/retention_days',
        get_string('retentiondays', 'quizaccess_edproctoring'),
        get_string('retentiondays_desc', 'quizaccess_edproctoring'),
        90,
        PARAM_INT
    ));

    // Face recognition service URL (Phase 2 — leave blank to disable).
    $settings->add(new admin_setting_configtext(
        'quizaccess_edproctoring/face_service_url',
        get_string('faceserviceurl', 'quizaccess_edproctoring'),
        get_string('faceserviceurl_desc', 'quizaccess_edproctoring'),
        '',
        PARAM_URL
    ));

    // Face match threshold — PARAM_RAW so "0.60" saves without validation error.
    $settings->add(new admin_setting_configtext(
        'quizaccess_edproctoring/face_match_threshold',
        get_string('facematchthreshold', 'quizaccess_edproctoring'),
        get_string('facematchthreshold_desc', 'quizaccess_edproctoring'),
        '0.60',
        PARAM_RAW
    ));

    // Trust score threshold below which teacher notification fires.
    $settings->add(new admin_setting_configtext(
        'quizaccess_edproctoring/notify_threshold',
        get_string('notifythreshold', 'quizaccess_edproctoring'),
        get_string('notifythreshold_desc', 'quizaccess_edproctoring'),
        60,
        PARAM_INT
    ));

    // Block mobile devices by default for new quizzes.
    $settings->add(new admin_setting_configcheckbox(
        'quizaccess_edproctoring/block_mobile_default',
        get_string('blockmobiledefault', 'quizaccess_edproctoring'),
        get_string('blockmobiledefault_desc', 'quizaccess_edproctoring'),
        1
    ));

    // Institution privacy policy URL shown on student consent screen.
    $settings->add(new admin_setting_configtext(
        'quizaccess_edproctoring/privacy_url',
        get_string('privacyurl', 'quizaccess_edproctoring'),
        get_string('privacyurl_desc', 'quizaccess_edproctoring'),
        '',
        PARAM_URL
    ));

    // Live monitor dashboard auto-refresh interval (seconds).
    $settings->add(new admin_setting_configtext(
        'quizaccess_edproctoring/live_refresh',
        get_string('liverefresh', 'quizaccess_edproctoring'),
        get_string('liverefresh_desc', 'quizaccess_edproctoring'),
        9,
        PARAM_INT
    ));

    // -----------------------------------------------------------------------
    // Trust score deductions — admins configure how many points each
    // violation type subtracts from the 100-point trust score.
    // -----------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'quizaccess_edproctoring/weights_heading',
        get_string('weightsheading', 'quizaccess_edproctoring'),
        get_string('weightsheading_desc', 'quizaccess_edproctoring')
    ));

    foreach (\quizaccess_edproctoring\helper\violation_classifier::TAXONOMY as $vtype => $vinfo) {
        [$vseverity, $vdefault] = $vinfo;
        $settings->add(new admin_setting_configtext(
            'quizaccess_edproctoring/weight_' . strtolower($vtype),
            get_string('violation_' . $vtype, 'quizaccess_edproctoring')
                . ' — ' . ucfirst($vseverity),
            get_string('weightsetting_desc', 'quizaccess_edproctoring', $vdefault),
            $vdefault,
            PARAM_FLOAT
        ));
    }

    // Add to admin tree. Use 'quizaccess' if it exists (created by quiz module),
    // fall back to 'quiz' or 'modsettings' to avoid "parent does not exist" fatal.
    if ($ADMIN->locate('quizaccess')) {
        $ADMIN->add('quizaccess', $settings);
    } else if ($ADMIN->locate('quiz')) {
        $ADMIN->add('quiz', $settings);
    } else {
        $ADMIN->add('modsettings', $settings);
    }
}


// Site-wide live proctoring monitor — listed under Reports for users holding
// quizaccess/edproctoring:viewallreports (capability-gated, so non-admin
// managers see it too). Kept outside the $hassiteconfig block on purpose.
if (isset($ADMIN) && $ADMIN->locate('reports')) {
    $ADMIN->add('reports', new admin_externalpage(
        'quizaccessedproctoringlive',
        get_string('livemonitor_all', 'quizaccess_edproctoring'),
        new moodle_url('/mod/quiz/accessrule/edproctoring/report/live.php'),
        'quizaccess/edproctoring:viewallreports'
    ));
}
