<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Incoming meeting-provider webhook endpoint (e.g. Zoom recording.completed).
 *
 * Verifies the signature against the matching credential account before acting
 * on anything — the payload is untrusted external content until verified.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require(__DIR__ . '/../../config.php');

use mod_edzsession\local\account_vault;
use mod_edzsession\local\provider_manager;
use mod_edzsession\local\pipeline\pipeline_manager;
use mod_edzsession\local\meeting\recording_asset;

$raw = file_get_contents('php://input');
$headers = array_change_key_case(mod_edzsession_request_headers(), CASE_LOWER);
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo 'bad request';
    exit;
}

$event = $data['event'] ?? '';
$payload = $data['payload'] ?? [];

// ---- Zoom endpoint URL validation handshake ------------------------------
if ($event === 'endpoint.url_validation') {
    $plaintoken = $payload['plainToken'] ?? '';
    // Sign with the first enabled account's secret token (per-account webhook
    // URLs are a later refinement; the handshake needs a single secret).
    $secret = '';
    foreach (account_vault::list_rows(true) as $row) {
        if (!empty($row->verificationtokenenc)) {
            $acct = account_vault::get((int) $row->id);
            $secret = (string) $acct->verificationtoken;
            break;
        }
    }
    header('Content-Type: application/json');
    echo json_encode([
        'plainToken' => $plaintoken,
        'encryptedToken' => hash_hmac('sha256', $plaintoken, $secret),
    ]);
    exit;
}

// ---- Identify + verify the account --------------------------------------
$accountextid = $payload['account_id'] ?? ($payload['object']['account_id'] ?? '');
$account = null;
foreach (account_vault::list_rows(true) as $row) {
    if ((string) $row->accountid === (string) $accountextid) {
        $account = account_vault::get((int) $row->id);
        break;
    }
}

// Fall back: if we could not match by id, we cannot verify — reject.
if ($account === null) {
    http_response_code(202); // Acknowledge but ignore unknown senders.
    echo 'unknown account';
    exit;
}

$provider = provider_manager::get_meeting($account->provider);
if (!$provider->verify_webhook($raw, $headers, $account)) {
    http_response_code(401);
    echo 'invalid signature';
    exit;
}

// ---- Handle recording.completed -----------------------------------------
if ($event === 'recording.completed') {
    $object = $payload['object'] ?? [];
    $meetingid = (string) ($object['id'] ?? '');
    $uuid = (string) ($object['uuid'] ?? '');
    $starttime = isset($object['start_time']) ? strtotime($object['start_time']) : 0;

    $edzsession = $DB->get_record('edzsession', ['remotemeetingid' => $meetingid]);
    if ($edzsession) {
        $occurrence = mod_edzsession_webhook_match_occurrence($edzsession->id, $uuid, $starttime);
        if ($occurrence) {
            if (empty($occurrence->remoteuuid) && $uuid !== '') {
                $DB->set_field('edzsession_occurrence', 'remoteuuid', $uuid, ['id' => $occurrence->id]);
            }
            $storagename = provider_manager::storage_name_for_activity($edzsession);
            foreach (($object['recording_files'] ?? []) as $f) {
                $ftype = strtoupper($f['file_type'] ?? '');
                if ($ftype !== 'MP4') {
                    continue;
                }
                $asset = new recording_asset(
                    $f['id'] ?? ($uuid . '_' . ($f['recording_start'] ?? '')),
                    $ftype,
                    $f['download_url'] ?? '',
                    (int) ($f['file_size'] ?? 0),
                    strtotime($f['recording_start'] ?? 'now')
                );
                pipeline_manager::enqueue((int) $occurrence->id, $asset, $storagename);
            }
        }
    }
}

http_response_code(200);
echo 'ok';

/**
 * Return request headers, with a fallback for SAPIs lacking getallheaders().
 *
 * @return array
 */
function mod_edzsession_request_headers(): array {
    if (function_exists('getallheaders')) {
        $h = getallheaders();
        if (is_array($h)) {
            return $h;
        }
    }
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$name] = $value;
        }
    }
    return $headers;
}

/**
 * Match a webhook occurrence to a stored occurrence row: by uuid first, else the
 * nearest un-linked scheduled occurrence by start time.
 *
 * @param int $edzsessionid
 * @param string $uuid
 * @param int $starttime
 * @return \stdClass|false
 */
function mod_edzsession_webhook_match_occurrence(int $edzsessionid, string $uuid, int $starttime) {
    global $DB;
    if ($uuid !== '') {
        $byuuid = $DB->get_record('edzsession_occurrence',
            ['edzsessionid' => $edzsessionid, 'remoteuuid' => $uuid]);
        if ($byuuid) {
            return $byuuid;
        }
    }
    if ($starttime <= 0) {
        return false;
    }
    // Nearest scheduled occurrence within a 6-hour window with no uuid yet.
    $window = 6 * HOURSECS;
    $sql = "SELECT * FROM {edzsession_occurrence}
             WHERE edzsessionid = :eid
               AND (remoteuuid IS NULL OR remoteuuid = '')
               AND ABS(starttime - :st) < :win
          ORDER BY ABS(starttime - :st2) ASC";
    $rows = $DB->get_records_sql($sql,
        ['eid' => $edzsessionid, 'st' => $starttime, 'win' => $window, 'st2' => $starttime], 0, 1);
    return $rows ? reset($rows) : false;
}
