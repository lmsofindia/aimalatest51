<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Admin diagnostic: test every meeting account + storage provider connection
 * and show a pass/fail dashboard. Each provider self-reports via
 * test_connection(), so new providers appear here automatically.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_edzsession\local\account_vault;
use mod_edzsession\local\provider_manager;
use mod_edzsession\local\connection_result;

require_login();
$context = context_system::instance();
require_capability('mod/edzsession:manageaccounts', $context);

$run = optional_param('run', 0, PARAM_BOOL);

$url = new moodle_url('/mod/edzsession/test_connections.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('testconnections', 'mod_edzsession'));
$PAGE->set_heading(get_string('testconnections', 'mod_edzsession'));

/**
 * Render a connection_result as a coloured badge.
 *
 * @param connection_result $r
 * @return string HTML
 */
function mod_edzsession_result_badge(connection_result $r): string {
    if ($r->notapplicable) {
        return html_writer::span(get_string('test_na', 'mod_edzsession'), 'badge bg-secondary');
    }
    return $r->ok
        ? html_writer::span(get_string('test_pass', 'mod_edzsession'), 'badge bg-success')
        : html_writer::span(get_string('test_fail', 'mod_edzsession'), 'badge bg-danger');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('testconnections', 'mod_edzsession'));
echo html_writer::div(get_string('testconnections_desc', 'mod_edzsession'), 'text-muted mb-3');

// The button that actually runs the (network) tests, so the page doesn't hit
// external APIs on every casual load.
echo html_writer::div($OUTPUT->single_button(
    new moodle_url($url, ['run' => 1]),
    get_string('test_runall', 'mod_edzsession'), 'get'), 'mb-3');

if (!$run) {
    echo $OUTPUT->notification(get_string('test_runhint', 'mod_edzsession'), 'info');
    echo $OUTPUT->footer();
    exit;
}

\core\session\manager::write_close(); // Long-running external calls: release the session lock.

$table = new html_table();
$table->head = [
    get_string('test_col_target', 'mod_edzsession'),
    get_string('test_col_type', 'mod_edzsession'),
    get_string('test_col_status', 'mod_edzsession'),
    get_string('test_col_detail', 'mod_edzsession'),
];

// --- Meeting accounts (all, incl. disabled) -------------------------------
foreach (account_vault::list_rows(false) as $row) {
    try {
        $account = account_vault::get((int) $row->id);
        $provider = provider_manager::get_meeting($account->provider);
        $result = $provider->test_connection($account);
    } catch (\Throwable $e) {
        $result = connection_result::fail(get_string('test_failed', 'mod_edzsession'), $e->getMessage());
    }
    $detail = $result->detail ? s($result->detail) : '';
    $line = s($result->message) . ($detail !== '' ? html_writer::div($detail, 'small text-muted') : '');
    $table->data[] = [
        format_string($row->name) . (!$row->enabled ? ' ' .
            html_writer::span(get_string('disabled'), 'badge bg-light text-dark') : ''),
        get_string('test_type_meeting', 'mod_edzsession', s($row->provider)),
        mod_edzsession_result_badge($result),
        $line,
    ];
}

// --- Storage providers (from the registry) --------------------------------
foreach (array_keys(provider_manager::storage_providers()) as $name) {
    try {
        $provider = provider_manager::get_storage($name);
        $result = $provider->test_connection();
        $label = $provider::get_display_name();
    } catch (\Throwable $e) {
        $result = connection_result::fail(get_string('test_failed', 'mod_edzsession'), $e->getMessage());
        $label = $name;
    }
    $isdefault = (get_config('mod_edzsession', 'defaultstorageprovider') === $name);
    $detail = $result->detail ? s($result->detail) : '';
    $line = s($result->message) . ($detail !== '' ? html_writer::div($detail, 'small text-muted') : '');
    $table->data[] = [
        s($label) . ($isdefault ? ' ' .
            html_writer::span(get_string('test_default', 'mod_edzsession'), 'badge bg-info') : ''),
        get_string('test_type_storage', 'mod_edzsession'),
        mod_edzsession_result_badge($result),
        $line,
    ];
}

if (empty($table->data)) {
    echo $OUTPUT->notification(get_string('test_nothing', 'mod_edzsession'), 'info');
} else {
    echo html_writer::table($table);
}

echo html_writer::div(html_writer::link(
    new moodle_url('/mod/edzsession/manage_accounts.php'),
    get_string('backtoaccounts', 'mod_edzsession')), 'mt-3');

echo $OUTPUT->footer();
