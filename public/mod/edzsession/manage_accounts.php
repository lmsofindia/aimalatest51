<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Admin page: manage the multi-account meeting credential vault.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_edzsession\local\account_vault;
use mod_edzsession\form\account_form;

$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

require_login();
$context = context_system::instance();
require_capability('mod/edzsession:manageaccounts', $context);

$baseurl = new moodle_url('/mod/edzsession/manage_accounts.php');
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manageaccounts', 'mod_edzsession'));
$PAGE->set_heading(get_string('manageaccounts', 'mod_edzsession'));

// ---- Delete (confirmed) --------------------------------------------------
if ($action === 'delete' && $id) {
    require_sesskey();
    if (optional_param('confirm', 0, PARAM_BOOL)) {
        account_vault::delete($id);
        redirect($baseurl, get_string('account_deleted', 'mod_edzsession'));
    }
    echo $OUTPUT->header();
    $row = $DB->get_record('edzsession_account', ['id' => $id], '*', MUST_EXIST);
    $confirmurl = new moodle_url($baseurl, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
    echo $OUTPUT->confirm(
        get_string('account_confirmdelete', 'mod_edzsession', format_string($row->name)),
        $confirmurl, $baseurl);
    echo $OUTPUT->footer();
    exit;
}

// ---- Add / edit ----------------------------------------------------------
if ($action === 'add' || $action === 'edit') {
    $customdata = ['id' => ($action === 'edit') ? $id : 0];
    $mform = new account_form($baseurl->out(false, ['action' => $action, 'id' => $id]), $customdata);

    if ($action === 'edit' && $id) {
        $row = $DB->get_record('edzsession_account', ['id' => $id], '*', MUST_EXIST);
        // Never prefill secrets into the browser; only non-secret fields.
        $mform->set_data([
            'id' => $row->id,
            'name' => $row->name,
            'provider' => $row->provider,
            'accountid' => $row->accountid,
            'clientid' => $row->clientid,
            'enabled' => $row->enabled,
        ]);
    }

    if ($mform->is_cancelled()) {
        redirect($baseurl);
    } else if ($data = $mform->get_data()) {
        account_vault::save($data);
        redirect($baseurl, get_string('account_saved', 'mod_edzsession'));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading($action === 'add'
        ? get_string('account_add', 'mod_edzsession')
        : get_string('account_edit', 'mod_edzsession'));
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// ---- List ----------------------------------------------------------------
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageaccounts', 'mod_edzsession'));

echo html_writer::div(
    $OUTPUT->single_button(new moodle_url($baseurl, ['action' => 'add']),
        get_string('account_add', 'mod_edzsession'), 'get'),
    'mb-3');

$rows = account_vault::list_rows(false);
if (empty($rows)) {
    echo $OUTPUT->notification(get_string('account_none', 'mod_edzsession'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('account_name', 'mod_edzsession'),
        get_string('meetingprovider', 'mod_edzsession'),
        get_string('account_accountid', 'mod_edzsession'),
        get_string('account_enabled', 'mod_edzsession'),
        get_string('actions'),
    ];
    foreach ($rows as $row) {
        $editurl = new moodle_url($baseurl, ['action' => 'edit', 'id' => $row->id]);
        $delurl = new moodle_url($baseurl, ['action' => 'delete', 'id' => $row->id, 'sesskey' => sesskey()]);
        $actions = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')))
            . $OUTPUT->action_icon($delurl, new pix_icon('t/delete', get_string('delete')));
        $table->data[] = [
            format_string($row->name),
            s($row->provider),
            s($row->accountid),
            $row->enabled ? get_string('yes') : get_string('no'),
            $actions,
        ];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
