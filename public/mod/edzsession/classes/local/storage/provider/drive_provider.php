<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage\provider;

use mod_edzsession\local\storage\storage_provider;
use mod_edzsession\local\storage\upload_request;
use mod_edzsession\local\storage\upload_handle;
use mod_edzsession\local\storage\stored_asset;
use mod_edzsession\local\storage\processing_status;
use mod_edzsession\local\storage\privacy_spec;
use mod_edzsession\local\storage\embed_info;
use mod_edzsession\local\storage\storage_quota;
use mod_edzsession\local\connection_result;

/**
 * Google Drive storage provider (Shared Drive + service account).
 *
 * Dependency-free: builds and RS256-signs the OAuth JWT with PHP's openssl, then
 * uses Drive API v3 resumable upload. Because a service account has no usable
 * personal Drive storage, uploads target a Google Workspace **Shared Drive**
 * (its ID is a required setting). No transcoding — files are ready on upload;
 * playback uses Drive's /preview embed.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class drive_provider implements storage_provider {

    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const API = 'https://www.googleapis.com/drive/v3';
    const UPLOAD = 'https://www.googleapis.com/upload/drive/v3';
    const SCOPE = 'https://www.googleapis.com/auth/drive';

    /** @var \stdClass config. */
    private $config;
    /** @var array|null cached parsed service account. */
    private $sa = null;

    public function __construct() {
        $this->config = (object) [
            'serviceaccount' => (string) get_config('mod_edzsession', 'edzstore_drive_serviceaccount'),
            'shareddrive'    => trim((string) get_config('mod_edzsession', 'edzstore_drive_shareddrive')),
            'defaultfolder'  => trim((string) get_config('mod_edzsession', 'edzstore_drive_defaultfolder')),
            'domain'         => trim((string) get_config('mod_edzsession', 'edzstore_drive_domain')),
        ];
    }

    public static function get_name(): string {
        return 'drive';
    }
    public static function get_display_name(): string {
        return get_string('provider_drive', 'mod_edzsession');
    }

    public function is_configured(): bool {
        return $this->service_account() !== null && $this->config->shareddrive !== '';
    }

    // ---- Capabilities -----------------------------------------------------

    public function supports_pull_upload(): bool {
        return false;
    }
    public function supports_stream_upload(): bool {
        return true;
    }
    public function supports_folders(): bool {
        return true;
    }
    public function supports_captions(): bool {
        return false;
    }
    public function supports_delete(): bool {
        return true;
    }
    public function get_quota(): ?storage_quota {
        return null;
    }

    public function list_folders(): array {
        $params = [
            'q' => "mimeType='application/vnd.google-apps.folder' and trashed=false",
            'corpora' => 'drive',
            'driveId' => $this->config->shareddrive,
            'includeItemsFromAllDrives' => 'true',
            'supportsAllDrives' => 'true',
            'fields' => 'files(id,name)',
            'pageSize' => '200',
        ];
        $res = $this->api('GET', '/files', $params);
        $out = [];
        foreach (($res['files'] ?? []) as $f) {
            $out[$f['id']] = $f['name'];
        }
        return $out;
    }

    public function ensure_folder(string $label): string {
        $label = trim($label);
        if ($label === '') {
            return '';
        }
        foreach ($this->list_folders() as $id => $name) {
            if (strcasecmp($name, $label) === 0) {
                return $id;
            }
        }
        $res = $this->api('POST', '/files', ['supportsAllDrives' => 'true', 'fields' => 'id'], [
            'name' => $label,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$this->config->shareddrive],
        ]);
        return $res['id'] ?? '';
    }

    public function default_folder_id(): ?string {
        if ($this->config->defaultfolder === '') {
            return null;
        }
        try {
            $id = $this->ensure_folder($this->config->defaultfolder);
            return $id !== '' ? $id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ---- Upload lifecycle (resumable) -------------------------------------

    public function begin_upload(upload_request $req): upload_handle {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        // Resumable upload needs the total size up front (Content-Range), and the
        // pipeline's stream path always provides it; guard so a missing size can't
        // silently truncate the file to the first chunk.
        if ((int) ($req->sizebytes ?? 0) <= 0) {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null,
                'resumable upload requires a known file size');
        }
        $parent = $req->folderid ?: $this->config->shareddrive;
        $meta = json_encode([
            'name' => $req->title . '.mp4',
            'parents' => [$parent],
        ]);

        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $this->get_token());
        $curl->setHeader('Content-Type: application/json; charset=UTF-8');
        $curl->setHeader('X-Upload-Content-Type: ' . ($req->mimetype ?: 'video/mp4'));
        $url = self::UPLOAD . '/files?uploadType=resumable&supportsAllDrives=true';
        $curl->post($url, $meta);
        $info = $curl->get_info();
        if ((int) ($info['http_code'] ?? 0) >= 400) {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null,
                'resumable init failed: ' . ($info['http_code'] ?? '?'));
        }
        $resp = $curl->getResponse() ?: [];
        $resp = array_change_key_case($resp, CASE_LOWER);
        $location = $resp['location'] ?? '';
        if ($location === '') {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null, 'no upload session URL');
        }
        $handle = new upload_handle(upload_handle::MODE_STREAM, null, $location);
        $handle->data['total'] = (int) ($req->sizebytes ?? 0);
        return $handle;
    }

    public function push_chunk(upload_handle $handle, string $bytes): void {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $len = strlen($bytes);
        $start = $handle->offset;
        $end = $start + $len - 1;
        $total = (int) ($handle->data['total'] ?: ($end + 1));

        $curl = new \curl();
        $curl->setHeader('Content-Range: bytes ' . $start . '-' . $end . '/' . $total);
        $curl->setHeader('Content-Type: application/octet-stream');
        $curl->setopt(['CURLOPT_CUSTOMREQUEST' => 'PUT']);
        $response = $curl->post($handle->uploadref, $bytes);
        $code = (int) ($curl->get_info()['http_code'] ?? 0);

        // 308 = resume incomplete (more chunks); 200/201 = complete.
        if ($code === 200 || $code === 201) {
            $data = json_decode($response, true) ?: [];
            $handle->assetid = $data['id'] ?? $handle->assetid;
        } else if ($code !== 308) {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null,
                "chunk PUT http $code: " . substr((string) $response, 0, 300));
        }
        $handle->offset += $len;
    }

    public function finalize_upload(upload_handle $handle): stored_asset {
        if (empty($handle->assetid)) {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null, 'no file id after upload');
        }
        return new stored_asset('drive', (string) $handle->assetid);
    }

    public function poll_processing(stored_asset $asset): processing_status {
        return new processing_status(processing_status::COMPLETE, 100);
    }

    public function apply_privacy(stored_asset $asset, privacy_spec $spec): void {
        // Grant read access so the /preview embed works. Prefer a domain grant
        // when a Workspace domain is configured; else anyone-with-link reader.
        if ($this->config->domain !== '') {
            $perm = ['type' => 'domain', 'role' => 'reader', 'domain' => $this->config->domain];
        } else {
            $perm = ['type' => 'anyone', 'role' => 'reader'];
        }
        $this->api('POST', '/files/' . $asset->assetid . '/permissions',
            ['supportsAllDrives' => 'true'], $perm);
    }

    public function move_to_folder(stored_asset $asset, string $folderid): void {
        if ($folderid === '') {
            return;
        }
        $cur = $this->api('GET', '/files/' . $asset->assetid,
            ['supportsAllDrives' => 'true', 'fields' => 'parents']);
        $removeparents = implode(',', $cur['parents'] ?? []);
        $this->api('PATCH', '/files/' . $asset->assetid, [
            'supportsAllDrives' => 'true',
            'addParents' => $folderid,
            'removeParents' => $removeparents,
        ], null); // addParents/removeParents are query params; no JSON body.
    }

    public function attach_caption(stored_asset $asset, string $vtt, string $lang): void {
        // Drive has no native caption track concept for arbitrary files.
    }

    public function get_embed(stored_asset $asset): embed_info {
        return new embed_info(embed_info::KIND_IFRAME,
            'https://drive.google.com/file/d/' . $asset->assetid . '/preview',
            ['width' => 640, 'height' => 360, 'allow' => 'autoplay']);
    }

    public function delete_asset(stored_asset $asset): void {
        $this->api('DELETE', '/files/' . $asset->assetid, ['supportsAllDrives' => 'true']);
    }

    // ---- Settings + test --------------------------------------------------

    public static function add_settings(\admin_settingpage $page): void {
        $p = self::config_prefix();
        $page->add(new \admin_setting_heading("{$p}/heading",
            get_string('provider_drive', 'mod_edzsession'),
            get_string('provider_drive_desc', 'mod_edzsession')));
        $page->add(new \admin_setting_configtextarea("mod_edzsession/{$p}_serviceaccount",
            get_string('drive_serviceaccount', 'mod_edzsession'),
            get_string('drive_serviceaccount_desc', 'mod_edzsession'), '', PARAM_RAW, 60, 8));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_shareddrive",
            get_string('drive_shareddrive', 'mod_edzsession'),
            get_string('drive_shareddrive_desc', 'mod_edzsession'), ''));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_defaultfolder",
            get_string('drive_defaultfolder', 'mod_edzsession'),
            get_string('drive_defaultfolder_desc', 'mod_edzsession'), ''));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_domain",
            get_string('drive_domain', 'mod_edzsession'),
            get_string('drive_domain_desc', 'mod_edzsession'), ''));
    }

    public static function config_prefix(): string {
        return 'edzstore_drive';
    }

    public function test_connection(): connection_result {
        if (!$this->is_configured()) {
            return connection_result::na(get_string('test_notconfigured', 'mod_edzsession'));
        }
        try {
            $this->get_token(); // Proves the service account + JWT signing works.
            $res = $this->api('GET', '/drives/' . $this->config->shareddrive,
                ['fields' => 'id,name']);
            $name = $res['name'] ?? $this->config->shareddrive;
            return connection_result::ok(get_string('test_ok_as', 'mod_edzsession', $name));
        } catch (\Throwable $e) {
            return connection_result::fail(get_string('test_failed', 'mod_edzsession'), $e->getMessage());
        }
    }

    // ---- Auth + HTTP ------------------------------------------------------

    /** @return array|null parsed service account, or null if invalid. */
    private function service_account(): ?array {
        if ($this->sa !== null) {
            return $this->sa ?: null;
        }
        $json = trim($this->config->serviceaccount);
        if ($json === '') {
            $this->sa = [];
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            $this->sa = [];
            return null;
        }
        $this->sa = $data;
        return $data;
    }

    /** Obtain (and cache) an OAuth access token via a signed JWT. */
    private function get_token(): string {
        global $CFG;
        $sa = $this->service_account();
        if ($sa === null) {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null, 'service account not configured');
        }
        $cache = \cache::make('mod_edzsession', 'tokens');
        $ckey = 'drive_' . md5($sa['client_email']);
        $cached = $cache->get($ckey);
        if ($cached && ($cached['fetched'] + $cached['expires_in'] - 60) > time()) {
            return $cached['access_token'];
        }

        $now = time();
        $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->b64url(json_encode([
            'iss' => $sa['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $signinginput = $header . '.' . $claim;
        $signature = '';
        if (!openssl_sign($signinginput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null, 'JWT signing failed');
        }
        $jwt = $signinginput . '.' . $this->b64url($signature);

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->setHeader('Content-Type: application/x-www-form-urlencoded');
        $response = $curl->post(self::TOKEN_URL, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]));
        $data = json_decode($response, true) ?: [];
        if (empty($data['access_token'])) {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null,
                'token exchange failed: ' . substr((string) $response, 0, 300));
        }
        $data['fetched'] = $now;
        $cache->set($ckey, $data);
        return $data['access_token'];
    }

    /**
     * JSON Drive API call.
     *
     * @param string $method
     * @param string $path e.g. '/files'
     * @param array $query
     * @param array|null $body JSON body for POST/PATCH (null = none)
     * @return array decoded JSON
     */
    private function api(string $method, string $path, array $query = [], ?array $body = null): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $url = self::API . $path . ($query ? ('?' . http_build_query($query)) : '');
        $curl = new \curl();
        $curl->setHeader('Authorization: Bearer ' . $this->get_token());
        $curl->setHeader('Content-Type: application/json');
        $method = strtoupper($method);

        if ($method === 'GET') {
            $response = $curl->get($url);
        } else if ($method === 'DELETE') {
            $response = $curl->delete($url);
        } else if ($method === 'PATCH') {
            $curl->setopt(['CURLOPT_CUSTOMREQUEST' => 'PATCH']);
            $response = $curl->post($url, $body !== null ? json_encode($body) : '');
        } else { // POST.
            $response = $curl->post($url, $body !== null ? json_encode($body) : '');
        }
        $code = (int) ($curl->get_info()['http_code'] ?? 0);
        if ($code >= 400) {
            throw new \moodle_exception('driveapierror', 'mod_edzsession', '', null,
                "HTTP $code on $method $path: " . substr((string) $response, 0, 400));
        }
        return $response ? (json_decode($response, true) ?: []) : [];
    }

    private function b64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
