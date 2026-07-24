<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Start an EDZ Session AS HOST.
 *
 * Capability-gated (mod/edzsession:host). Generates a fresh host start URL at
 * click time via the meeting provider and redirects the browser straight to it,
 * so the sensitive host token never lands in page HTML or logs.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_edzsession\local\account_vault;
use mod_edzsession\local\provider_manager;
use mod_edzsession\local\meeting\remote_meeting;

$id = required_param('id', PARAM_INT);        // Course module id.
$occid = required_param('occ', PARAM_INT);     // Occurrence id.

$cm = get_coursemodule_from_id('edzsession', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$edzsession = $DB->get_record('edzsession', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_sesskey();
require_capability('mod/edzsession:host', $context);

$viewurl = new moodle_url('/mod/edzsession/view.php', ['id' => $cm->id]);
$occ = $DB->get_record('edzsession_occurrence',
    ['id' => $occid, 'edzsessionid' => $edzsession->id], '*', MUST_EXIST);

if (empty($edzsession->accountid) || empty($occ->remotemeetingid) && empty($edzsession->remotemeetingid)) {
    redirect($viewurl, get_string('host_nomeeting', 'mod_edzsession'), null,
        \core\output\notification::NOTIFY_ERROR);
}

try {
    $account = account_vault::get((int) $edzsession->accountid);
    $provider = provider_manager::get_meeting($edzsession->meetingprovider);
    $meetingid = !empty($occ->remotemeetingid) ? $occ->remotemeetingid : $edzsession->remotemeetingid;
    $meeting = new remote_meeting((string) $meetingid, (string) $occ->joinurl, $occ->remoteuuid);
    $starturl = $provider->get_host_start_url($meeting, $account);
} catch (\Throwable $e) {
    redirect($viewurl, get_string('host_startfailed', 'mod_edzsession', $e->getMessage()), null,
        \core\output\notification::NOTIFY_ERROR);
}

redirect(new moodle_url($starturl));
