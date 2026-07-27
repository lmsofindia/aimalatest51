<?php
/**
 * Admin Knowledge Base management page.
 *
 * Allows admins and managers to:
 *  - Upload documents (PDF, text) to the support KB
 *  - Ingest URLs into the support KB
 *  - List all KB items with status
 *  - Toggle KB items active/inactive
 *  - Delete KB items
 *
 * No quiz/flashcard settings here — KB is for Support Chat only.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('local/edzaiaxisfront:manageplugin', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edzaiaxisfront/pages/kb_admin.php'));
$PAGE->set_title(get_string('kb_admin_title', 'local_edzaiaxisfront'));
$PAGE->set_heading(get_string('kb_admin_title', 'local_edzaiaxisfront'));
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

$PAGE->requires->js_call_amd(
    'local_edzaiaxisfront/kb_admin',
    'init',
    [[
        'wwwroot'  => (new moodle_url('/'))->out(false),
        'sesskey'  => sesskey(),
    ]]
);

echo $OUTPUT->render_from_template('local_edzaiaxisfront/kb_admin', [
    'wwwroot' => (new moodle_url('/'))->out(false),
]);

echo $OUTPUT->footer();
