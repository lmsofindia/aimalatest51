<?php

/**
 * Per-user token limit overrides page.
 *
 * Admins can:
 *  - See all users who have a custom override
 *  - Search any user and set per-user limits (session / daily / monthly / token monthly)
 *  - Use -1 / blank to mean "use site default"
 *  - Add a note explaining why the override was set
 *  - Remove an override to revert to site defaults
 *  - Changes sync to axis-ai immediately via the external web service
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_edzaiaxisfront_user_overrides');

require_capability('local/edzaiaxisfront:manageplugin', context_system::instance());

$PAGE->set_url(new moodle_url('/local/edzaiaxisfront/pages/user_overrides.php'));
$PAGE->set_title(get_string('user_overrides_title', 'local_edzaiaxisfront'));
$PAGE->set_heading(get_string('user_overrides_title', 'local_edzaiaxisfront'));

// Site defaults for display in the form placeholders
$site_defaults = [
    'chat_session_msg_limit' => (int) get_config('local_edzaiaxisfront', 'chat_session_msg_limit') ?: 50,
    'chat_daily_msg_limit'   => (int) get_config('local_edzaiaxisfront', 'chat_daily_msg_limit')   ?: 200,
    'chat_monthly_msg_limit' => (int) get_config('local_edzaiaxisfront', 'chat_monthly_msg_limit') ?: 2000,
    'token_monthly_limit'    => (int) get_config('local_edzaiaxisfront', 'token_monthly_limit')    ?: 5000000,
];

$PAGE->requires->js_call_amd('local_edzaiaxisfront/user_overrides', 'init', [[
    'site_defaults' => $site_defaults,
    'sesskey'       => sesskey(),
    'reports_url'   => (new moodle_url('/local/edzaiaxisfront/pages/admin_reports.php'))->out(false),
]]);

$templatectx = [
    'site_defaults'        => $site_defaults,
    'sesskey'              => sesskey(),
    'reports_url'          => (new moodle_url('/local/edzaiaxisfront/pages/admin_reports.php'))->out(false),
    'autocomplete_url'     => (new moodle_url('/user/selector/search.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_edzaiaxisfront/user_overrides', $templatectx);
echo $OUTPUT->footer();
