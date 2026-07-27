<?php

/**
 * Plugin hook callbacks.
 *
 * Key hooks:
 *  - after_config()          : on every page, inject student panel CSS/JS for module views
 *  - extend_navigation()     : add "AI Manager" link to course admin menu
 *  - pluginfile()            : serve files stored by the plugin
 *
 * Settings sync hook:
 *  admin_settings_updated()  : called when admin saves the settings page.
 *                              Calls axis-ai to create/update the tenant record.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the course navigation to add "AI Manager" link for teachers.
 */
function local_edzaiaxisfront_extend_navigation_course($navigation, $course, $context)
{
    if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
        return;
    }
    if (!has_capability('local/edzaiaxisfront:managecourse', $context)) {
        return;
    }
    $url = new moodle_url('/local/edzaiaxisfront/pages/dashboard.php', ['courseid' => $course->id]);
    $navigation->add(
        get_string('ai_manager_nav', 'local_edzaiaxisfront'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'edzaiaxisfront_manager',
        new pix_icon('i/settings', '')
    );
}

/**
 * Inject the student AI panel into module view pages.
 * Runs on every page load. Checks if: plugin enabled, user is enrolled,
 * current page is a module view, and the module has AI content ready.
 */
function local_edzaiaxisfront_after_config()
{
    global $PAGE, $USER, $DB;

    if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
        return;
    }
    // Only inject on module view pages

    // We have to show this in page-mod-url-view , page-mod-resource-view ,page-mod-page-view etc. — not just page-mod-view — because different modules use different pagetypes for their view pages.
    if (
        ($PAGE->pagetype !== 'mod-resource-view' && $PAGE->pagetype !== 'mod-page-view'
            && $PAGE->pagetype !== 'mod-url-view')
        && strpos($PAGE->pagetype, 'mod-') !== 0
    ) {
        return;
    }

    $cmid = $PAGE->cm ? $PAGE->cm->id : null;

    if (!$cmid) {
        return;
    }

    // Check if this CM has AI content configured and visible
    $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid]);
    if (!$config || $config->status !== 'ready') {
        return;
    }

    // Check student capability
    $context = context_module::instance($cmid);
    if (!has_capability('local/edzaiaxisfront:viewstudent', $context)) {
        return;
    }

    // NOTE: student_panel AMD is injected via output_callbacks.php::before_footer
    // (Moodle 4.4+) or would need to be here for 4.1-4.3.
    // The before_footer hook runs at the correct time (DOM ready), so we rely on it.
    // Do NOT inject here to avoid double-init.
}

/**
 * Called when admin saves plugin settings.
 * Pushes the current rate limits to the tenant on the axisstand server.
 *
 * axisstand model: the tenant is provisioned in the axisstand console and the
 * plugin is handed a pre-issued `axisai_...` API key. The plugin therefore does
 * NOT self-provision a tenant here — the old create/update-tenant flow would 403
 * against a tenant-scoped key, or (worse) create a duplicate tenant. Instead we
 * push rate limits to the `/me` route, which axisstand resolves to the correct
 * tenant from the API key alone (no tenant UUID needed).
 *
 * Feature flags are NOT synced to the server: they are enforced Moodle-side
 * (see get_site_features() — the student surface is the AND of the local flags),
 * so the server does not need them for gating.
 */
function local_edzaiaxisfront_admin_settings_updated()
{
    $apiurl = get_config('local_edzaiaxisfront', 'api_base_url');
    $apikey = get_config('local_edzaiaxisfront', 'api_key');

    if (empty($apiurl) || empty($apikey)) {
        return;  // Not yet configured
    }

    try {
        $client = new \local_edzaiaxisfront\api\axis_client($apiurl, $apikey);

        // Resolved from the API key on the server — no tenant UUID required.
        $resp = $client->sync_rate_limits([
            'chat_session_msg_limit' => (int) get_config('local_edzaiaxisfront', 'chat_session_msg_limit'),
            'chat_daily_msg_limit'   => (int) get_config('local_edzaiaxisfront', 'chat_daily_msg_limit'),
            'chat_monthly_msg_limit' => (int) get_config('local_edzaiaxisfront', 'chat_monthly_msg_limit'),
            'token_monthly_limit'    => (int) get_config('local_edzaiaxisfront', 'token_monthly_limit'),
        ]);

        // The /me/settings response echoes back the tenant UUID that axisstand resolved
        // from our API key. Store it in the read-only "Tenant ID" setting — this is the
        // "auto-populated after first successful connection" behaviour, now driven by the
        // key instead of self-provisioning. Empty here = the connection/key is not working.
        if (!empty($resp['tenant_id'])) {
            set_config('tenant_id', $resp['tenant_id'], 'local_edzaiaxisfront');
        }
    } catch (\Exception $e) {
        // Log but don't throw — settings still save in Moodle even if sync fails.
        debugging('Axis AI rate-limit sync failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Inject the global floating chatbot on every page for logged-in users.
 *
 * COMPATIBILITY NOTE — Moodle 4.1 through 5.x:
 *   Moodle 4.4+ uses the PSR-14 Hooks API (db/hooks.php →
 *   classes/hook/output_callbacks.php::before_footer). When the hook class is
 *   present, Moodle 4.4+ ignores this lib.php function automatically.
 *   This function is kept for Moodle 4.1–4.3 backward compatibility only.
 *   If you are running Moodle 4.4+, this function is a no-op.
 *
 * Conditions:
 *  - Plugin enabled
 *  - User is logged in and not a guest
 *  - Not an admin-only page (site admin, upgrade, install)
 *  - At least one chatbot mode enabled at site level
 */
function local_edzaiaxisfront_before_footer()
{
    global $PAGE, $USER, $OUTPUT;

    if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
        return '';
    }
    if (!isloggedin() || isguestuser()) {
        return '';
    }
    // Skip on admin/setup pages to avoid interfering with installs
    if (in_array($PAGE->pagelayout, ['maintenance', 'embedded', 'print', 'redirect'])) {
        return '';
    }
    if (strpos($PAGE->pagetype, 'admin-') === 0) {
        return '';
    }

    $study_enabled    = (bool) get_config('local_edzaiaxisfront', 'feature_chatbot');
    $support_enabled  = (bool) get_config('local_edzaiaxisfront', 'feature_kb_chat');
    $analysis_enabled = true; // always available — all Moodle-DB data

    if (!$study_enabled && !$support_enabled && !$analysis_enabled) {
        return '';
    }

    // Use moodle_url for CSS (correct in both flat layout and Moodle 5.1 /public structure).
    $PAGE->requires->css(new moodle_url('/local/edzaiaxisfront/styles.css'));
    $PAGE->requires->js_call_amd(
        'local_edzaiaxisfront/chatbot',
        'init',
        [[
            'userid'           => (int) $USER->id,
            'firstname'        => $USER->firstname,
            'sesskey'          => sesskey(),
            'wwwroot'          => (new moodle_url('/'))->out(false),
            'study_enabled'    => $study_enabled,
            'support_enabled'  => $support_enabled,
            'analysis_enabled' => $analysis_enabled,
        ]]
    );

    // Return the chatbot shell HTML (minimal — AMD fills the content)
    return $OUTPUT->render_from_template('local_edzaiaxisfront/chatbot_panel', [
        'userid'    => (int) $USER->id,
        'firstname' => $USER->firstname,
        'sesskey'   => sesskey(),
    ]);
}

/**
 * Serve plugin files (e.g. uploaded KB documents before ingestion).
 */
function local_edzaiaxisfront_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = [])
{
    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return false;
    }
    if ($filearea !== 'kb_uploads') {
        return false;
    }
    if (!has_capability('local/edzaiaxisfront:manageplugin', $context)) {
        return false;
    }

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = $fs->get_file($context->id, 'local_edzaiaxisfront', $filearea, 0, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
