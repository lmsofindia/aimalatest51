<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\meeting\provider;

use mod_edzsession\local\meeting\meeting_provider;
use mod_edzsession\local\meeting\account;
use mod_edzsession\local\meeting\meeting_spec;
use mod_edzsession\local\meeting\remote_meeting;
use mod_edzsession\local\meeting\recording_asset;
use mod_edzsession\local\meeting\download_ref;
use mod_edzsession\local\meeting\participant_record;

/**
 * Zoom meeting provider (Server-to-Server OAuth, per-account).
 *
 * Multi-account is the whole reason we did not fork mod_zoom: the account is
 * passed into every call, and the OAuth token is cached per account id. This
 * driver is functional for the core calls; deeper edge handling lands in P2.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class zoom_provider implements meeting_provider {

    const API = 'https://api.zoom.us/v2';
    const OAUTH = 'https://zoom.us/oauth/token';

    public static function get_name(): string {
        return 'zoom';
    }

    public static function get_display_name(): string {
        return get_string('provider_zoom', 'mod_edzsession');
    }

    public function is_configured(account $account): bool {
        return $account->accountid !== '' && $account->clientid !== '' && $account->clientsecret !== '';
    }

    // ---- Meeting lifecycle ------------------------------------------------

    public function create_meeting(meeting_spec $spec, account $account): remote_meeting {
        $body = [
            'topic' => $spec->topic,
            'type' => empty($spec->recurrence) ? 2 : 8, // 2=scheduled, 8=recurring fixed time.
            'start_time' => gmdate('Y-m-d\TH:i:s\Z', $spec->starttime),
            'duration' => $spec->duration,
            'timezone' => $spec->timezone ?? 'UTC',
            'settings' => [
                'auto_recording' => $spec->autorecord ? 'cloud' : 'none',
                'join_before_host' => false,
                'waiting_room' => true,
            ],
        ];
        if (!empty($spec->recurrence)) {
            $body['recurrence'] = $this->map_recurrence($spec->recurrence);
        }
        $res = $this->api($account, 'POST', '/users/me/meetings', $body);
        return new remote_meeting(
            (string) ($res['id'] ?? ''),
            (string) ($res['join_url'] ?? ''),
            $res['uuid'] ?? null,
            $res['start_url'] ?? null,
            $res
        );
    }

    public function update_meeting(remote_meeting $meeting, meeting_spec $spec, account $account): void {
        $this->api($account, 'PATCH', '/meetings/' . $meeting->meetingid, [
            'topic' => $spec->topic,
            'start_time' => gmdate('Y-m-d\TH:i:s\Z', $spec->starttime),
            'duration' => $spec->duration,
        ]);
    }

    public function delete_meeting(remote_meeting $meeting, account $account): void {
        $this->api($account, 'DELETE', '/meetings/' . $meeting->meetingid);
    }

    public function get_join_url(remote_meeting $meeting, \stdClass $user): string {
        // Zoom join URL is the same for all; per-user registration URLs are P2.
        return $meeting->joinurl;
    }

    // ---- Attendance -------------------------------------------------------

    public function fetch_participants(remote_meeting $meeting, account $account): array {
        // Reports API keys on the occurrence UUID and needs DOUBLE url-encoding
        // when the UUID contains a '/' or starts with '/'.
        $uuid = $this->double_encode_uuid($meeting->uuid ?? $meeting->meetingid);
        $records = [];
        $next = '';
        do {
            $params = ['page_size' => 300];
            if ($next !== '') {
                $params['next_page_token'] = $next;
            }
            $res = $this->api($account, 'GET', "/report/meetings/{$uuid}/participants", $params);
            foreach (($res['participants'] ?? []) as $p) {
                $records[] = new participant_record(
                    $p['name'] ?? '',
                    $p['user_email'] ?? null,
                    strtotime($p['join_time'] ?? 'now'),
                    strtotime($p['leave_time'] ?? 'now'),
                    $p['registrant_id'] ?? null
                );
            }
            $next = $res['next_page_token'] ?? '';
        } while ($next !== '');
        return $records;
    }

    public function supports_webhooks(): bool {
        return true;
    }

    public function verify_webhook(string $payload, array $headers, account $account): bool {
        // Zoom sends x-zm-signature: v0=<hmac> and x-zm-request-timestamp.
        $ts = $headers['x-zm-request-timestamp'] ?? '';
        $sig = $headers['x-zm-signature'] ?? '';
        $secret = $account->verificationtoken ?? '';
        if ($ts === '' || $sig === '' || $secret === '') {
            return false;
        }
        $message = "v0:{$ts}:{$payload}";
        $expected = 'v0=' . hash_hmac('sha256', $message, $secret);
        return hash_equals($expected, $sig);
    }

    // ---- Recordings -------------------------------------------------------

    public function list_recordings(remote_meeting $meeting, account $account): array {
        $uuid = $this->double_encode_uuid($meeting->uuid ?? $meeting->meetingid);
        $res = $this->api($account, 'GET', "/meetings/{$uuid}/recordings");
        $out = [];
        foreach (($res['recording_files'] ?? []) as $f) {
            $out[] = new recording_asset(
                $f['id'] ?? ($f['recording_start'] . '_' . ($f['file_type'] ?? '')),
                $f['file_type'] ?? '',
                $f['download_url'] ?? '',
                (int) ($f['file_size'] ?? 0),
                strtotime($f['recording_start'] ?? 'now'),
                $f['recording_type'] ?? null
            );
        }
        return $out;
    }

    public function get_download(recording_asset $recording, account $account): download_ref {
        // Zoom download_url needs the OAuth access token appended for S2S apps.
        $token = $this->get_token($account);
        $sep = strpos($recording->downloadurl, '?') === false ? '?' : '&';
        return new download_ref(
            $recording->downloadurl . $sep . 'access_token=' . rawurlencode($token['access_token']),
            time() + (int) ($token['expires_in'] ?? 3600)
        );
    }

    public function delete_recording(recording_asset $recording, account $account): void {
        // Deletion by meeting is P2 (need the meeting uuid + recording id path).
        // Left explicit so we never silently no-op a destructive call.
        throw new \moodle_exception('notimplementedyet', 'mod_edzsession', '', 'zoom delete_recording (P2)');
    }

    public static function config_prefix(): string {
        return 'edzmeet_zoom';
    }

    // ---- Internals --------------------------------------------------------

    /** @var array<int,array> Per-account token cache within a request. */
    private array $tokencache = [];

    private function get_token(account $account): array {
        if (isset($this->tokencache[$account->id])) {
            return $this->tokencache[$account->id];
        }
        // Try the Moodle cache first (survives across requests until expiry).
        $cache = \cache::make('mod_edzsession', 'tokens');
        $cached = $cache->get('zoom_' . $account->id);
        if ($cached && ($cached['fetched'] + $cached['expires_in'] - 60) > time()) {
            $this->tokencache[$account->id] = $cached;
            return $cached;
        }

        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->setHeader('Authorization: Basic ' .
            base64_encode($account->clientid . ':' . $account->clientsecret));
        $curl->setHeader('Content-Type: application/x-www-form-urlencoded');
        $response = $curl->post(self::OAUTH, http_build_query([
            'grant_type' => 'account_credentials',
            'account_id' => $account->accountid,
        ]));
        $data = json_decode($response, true) ?: [];
        if (empty($data['access_token'])) {
            throw new \moodle_exception('zoomauthfailed', 'mod_edzsession', '', null,
                substr((string) $response, 0, 300));
        }
        $data['fetched'] = time();
        $cache->set('zoom_' . $account->id, $data);
        $this->tokencache[$account->id] = $data;
        return $data;
    }

    private function api(account $account, string $method, string $path, array $params = []): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $token = $this->get_token($account);
        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $token['access_token']);
        $curl->setHeader('Content-Type: application/json');
        $url = self::API . $path;
        $method = strtoupper($method);

        if ($method === 'GET') {
            $query = $params ? ('?' . http_build_query($params)) : '';
            $response = $curl->get($url . $query);
        } else if ($method === 'DELETE') {
            $response = $curl->delete($url);
        } else {
            $response = $curl->{strtolower($method)}($url, $params ? json_encode($params) : '');
        }
        $code = (int) ($curl->get_info()['http_code'] ?? 0);
        if ($code >= 400) {
            throw new \moodle_exception('zoomapierror', 'mod_edzsession', '', null,
                "HTTP $code on $method $path: " . substr((string) $response, 0, 400));
        }
        return $response ? (json_decode($response, true) ?: []) : [];
    }

    /** Zoom UUIDs starting with '/' or containing '//' must be double-encoded. */
    private function double_encode_uuid(string $uuid): string {
        if (strpos($uuid, '/') !== false || strpos($uuid, '//') !== false) {
            return rawurlencode(rawurlencode($uuid));
        }
        return rawurlencode($uuid);
    }

    private function map_recurrence(array $r): array {
        // Minimal mapping; full weekday/interval mapping is P2.
        $out = ['type' => $r['type'] ?? 2]; // 2 = weekly.
        if (isset($r['interval'])) {
            $out['repeat_interval'] = (int) $r['interval'];
        }
        if (isset($r['weekdays'])) {
            $out['weekly_days'] = $r['weekdays'];
        }
        if (isset($r['count'])) {
            $out['end_times'] = (int) $r['count'];
        }
        if (isset($r['until'])) {
            $out['end_date_time'] = gmdate('Y-m-d\TH:i:s\Z', (int) $r['until']);
        }
        return $out;
    }
}
