<?php

/**
 * Admin settings page.
 * When saved, immediately syncs to axis-ai via create/update tenant API.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // ── Admin category ──────────────────────────────────────────────────────
    $ADMIN->add('localplugins', new admin_category(
        'local_edzaiaxisfront_cat',
        get_string('pluginname', 'local_edzaiaxisfront')
    ));

    $settings = new admin_settingpage(
        'local_edzaiaxisfront',
        get_string('settings_nav', 'local_edzaiaxisfront')
    );

    $ADMIN->add('local_edzaiaxisfront_cat', $settings);

    // ── Admin report page (external page link) ──────────────────────────────
    $ADMIN->add('local_edzaiaxisfront_cat', new admin_externalpage(
        'local_edzaiaxisfront_reports',
        get_string('reports_title', 'local_edzaiaxisfront'),
        new moodle_url('/local/edzaiaxisfront/pages/admin_reports.php'),
        'local/edzaiaxisfront:viewreports'
    ));

    // ── User overrides page (external page link) ────────────────────────────
    $ADMIN->add('local_edzaiaxisfront_cat', new admin_externalpage(
        'local_edzaiaxisfront_user_overrides',
        get_string('user_overrides_title', 'local_edzaiaxisfront'),
        new moodle_url('/local/edzaiaxisfront/pages/user_overrides.php'),
        'local/edzaiaxisfront:manageplugin'
    ));

    // ── Knowledge Base admin page ───────────────────────────────────────────
    $ADMIN->add('local_edzaiaxisfront_cat', new admin_externalpage(
        'local_edzaiaxisfront_kb_admin',
        get_string('kb_admin_title', 'local_edzaiaxisfront'),
        new moodle_url('/local/edzaiaxisfront/pages/kb_admin.php'),
        'local/edzaiaxisfront:manageplugin'
    ));

    // ── Connection ────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_edzaiaxisfront/settings_heading',
        get_string('settings_heading', 'local_edzaiaxisfront'),
        get_string('settings_heading_desc', 'local_edzaiaxisfront')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_edzaiaxisfront/plugin_enabled',
        get_string('plugin_enabled', 'local_edzaiaxisfront'),
        get_string('plugin_enabled_desc', 'local_edzaiaxisfront'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_edzaiaxisfront/api_base_url',
        get_string('api_base_url', 'local_edzaiaxisfront'),
        get_string('api_base_url_desc', 'local_edzaiaxisfront'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_edzaiaxisfront/api_key',
        get_string('api_key', 'local_edzaiaxisfront'),
        get_string('api_key_desc', 'local_edzaiaxisfront'),
        ''
    ));

    // Read-only: populated automatically after first successful connection
    $settings->add(new admin_setting_configtext(
        'local_edzaiaxisfront/tenant_id',
        get_string('tenant_id', 'local_edzaiaxisfront'),
        get_string('tenant_id_desc', 'local_edzaiaxisfront'),
        '',
        PARAM_ALPHANUMEXT
    ));

    // 🔥 Moodle Webservice Token (THIS is what you need)
    $settings->add(new admin_setting_configtext(
        'local_edzaiaxisfront/apitoken',
        'Moodle Webservice Token',
        'Token used for accessing pluginfile via webservice/pluginfile.php',
        '',
        PARAM_TEXT
    ));

    // add vimeotoken setting
    $settings->add(new admin_setting_configtext(
        'local_edzaiaxisfront/vimeotoken',
        get_string('vimeotoken', 'local_edzaiaxisfront'),
        get_string('vimeotoken_desc', 'local_edzaiaxisfront'),
        '',
        PARAM_TEXT
    ));

    // ── Features ──────────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_edzaiaxisfront/features_heading',
        get_string('features_heading', 'local_edzaiaxisfront'),
        get_string('features_heading_desc', 'local_edzaiaxisfront')
    ));

    $featurekeys = ['summary', 'glossary', 'flashcards', 'quiz', 'faq', 'infographic', 'transcript', 'chatbot', 'kb_chat'];
    foreach ($featurekeys as $key) {
        $settings->add(new admin_setting_configcheckbox(
            "local_edzaiaxisfront/feature_{$key}",
            get_string("feature_{$key}", 'local_edzaiaxisfront'),
            '',
            1
        ));
    }

    // ── Rate limits ───────────────────────────────────────────────────────────
    $settings->add(new admin_setting_heading(
        'local_edzaiaxisfront/limits_heading',
        get_string('limits_heading', 'local_edzaiaxisfront'),
        get_string('limits_heading_desc', 'local_edzaiaxisfront')
    ));

    $limits = [
        'chat_session_msg_limit'  => 50,
        'chat_daily_msg_limit'    => 200,
        'chat_monthly_msg_limit'  => 2000,
        'token_monthly_limit'     => 5000000,
    ];
    foreach ($limits as $key => $default) {
        $settings->add(new admin_setting_configtext(
            "local_edzaiaxisfront/{$key}",
            get_string($key, 'local_edzaiaxisfront'),
            get_string("{$key}_desc", 'local_edzaiaxisfront'),
            $default,
            PARAM_INT
        ));
    }
}
