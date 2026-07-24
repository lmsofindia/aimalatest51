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

/**
 * Vimeo storage provider — the first fully implemented driver.
 *
 * Strategy: prefer PULL upload (Vimeo fetches the Zoom recording URL directly =
 * zero infra on our side); fall back to tus streaming pass-through. Recordings
 * are locked to domain-whitelisted, non-downloadable embeds. Folders map to
 * Vimeo "projects".
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vimeo_provider implements storage_provider {

    /** @var string Vimeo API base. */
    const API = 'https://api.vimeo.com';

    /** @var string Personal access token. */
    private string $pat;

    public function __construct() {
        $this->pat = (string) get_config('mod_edzsession', 'edzstore_vimeo_pat');
    }

    public static function get_name(): string {
        return 'vimeo';
    }

    public static function get_display_name(): string {
        return get_string('provider_vimeo', 'mod_edzsession');
    }

    public function is_configured(): bool {
        return strlen($this->pat) > 0;
    }

    // ---- Capabilities -----------------------------------------------------

    public function supports_pull_upload(): bool {
        return true;
    }
    public function supports_stream_upload(): bool {
        return true; // tus fallback.
    }
    public function supports_folders(): bool {
        return true; // Vimeo projects.
    }
    public function supports_captions(): bool {
        return true; // text tracks.
    }
    public function supports_delete(): bool {
        return true;
    }

    public function get_quota(): ?storage_quota {
        $me = $this->api('GET', '/me', ['fields' => 'upload_quota']);
        $q = $me['upload_quota'] ?? null;
        if ($q === null) {
            return null;
        }
        // Vimeo exposes periodic + lifetime; use periodic 'free' when present.
        $free = $q['periodic']['free'] ?? ($q['space']['free'] ?? null);
        $max = $q['periodic']['max'] ?? ($q['space']['max'] ?? null);
        return new storage_quota(
            $free !== null ? (int) $free : null,
            $max !== null ? (int) $max : null,
            'week'
        );
    }

    // ---- Folders (projects) ----------------------------------------------

    public function list_folders(): array {
        $out = [];
        $res = $this->api('GET', '/me/projects', ['per_page' => 100, 'fields' => 'uri,name']);
        foreach (($res['data'] ?? []) as $proj) {
            $id = $this->id_from_uri($proj['uri'] ?? '');
            if ($id !== '') {
                $out[$id] = $proj['name'] ?? $id;
            }
        }
        return $out;
    }

    public function ensure_folder(string $label): string {
        foreach ($this->list_folders() as $id => $name) {
            if (strcasecmp($name, $label) === 0) {
                return $id;
            }
        }
        $res = $this->api('POST', '/me/projects', ['name' => $label]);
        return $this->id_from_uri($res['uri'] ?? '');
    }

    // ---- Upload lifecycle -------------------------------------------------

    public function begin_upload(upload_request $req): upload_handle {
        // Prefer pull when we have a source URL.
        if ($req->sourceurl) {
            $body = [
                'upload' => ['approach' => 'pull', 'link' => $req->sourceurl],
                'name' => $req->title,
                'privacy' => ['view' => 'disable', 'embed' => 'whitelist', 'download' => false],
            ];
            if ($req->description) {
                $body['description'] = $req->description;
            }
            $res = $this->api('POST', '/me/videos', $body);
            $assetid = $this->id_from_uri($res['uri'] ?? '');
            $h = new upload_handle(upload_handle::MODE_PULL, $assetid);
            $h->data['approach'] = 'pull';
            return $h;
        }

        // Fall back to tus streaming.
        $size = $req->sizebytes ?? 0;
        $res = $this->api('POST', '/me/videos', [
            'upload' => ['approach' => 'tus', 'size' => (string) $size],
            'name' => $req->title,
            'privacy' => ['view' => 'disable', 'embed' => 'whitelist', 'download' => false],
        ]);
        $assetid = $this->id_from_uri($res['uri'] ?? '');
        $tusurl = $res['upload']['upload_link'] ?? null;
        $h = new upload_handle(upload_handle::MODE_STREAM, $assetid, $tusurl);
        $h->data['approach'] = 'tus';
        return $h;
    }

    public function push_chunk(upload_handle $handle, string $bytes): void {
        if ($handle->mode !== upload_handle::MODE_STREAM || !$handle->uploadref) {
            throw new \coding_exception('push_chunk called on non-stream Vimeo handle');
        }
        // tus PATCH at the current offset.
        $curl = $this->raw_curl();
        $curl->setHeader('Tus-Resumable: 1.0.0');
        $curl->setHeader('Upload-Offset: ' . $handle->offset);
        $curl->setHeader('Content-Type: application/offset+octet-stream');
        $response = $curl->patch($handle->uploadref, $bytes);
        $info = $curl->get_info();
        if (($info['http_code'] ?? 0) !== 204) {
            throw new \moodle_exception('vimeouploadfailed', 'mod_edzsession', '',
                null, 'tus PATCH http ' . ($info['http_code'] ?? '?') . ': ' . $response);
        }
        $handle->offset += strlen($bytes);
    }

    public function finalize_upload(upload_handle $handle): stored_asset {
        // For both pull and tus, the video id already exists; nothing extra to
        // "commit". Processing is polled separately.
        return new stored_asset('vimeo', (string) $handle->assetid, null, [
            'approach' => $handle->data['approach'] ?? null,
            'uri' => '/videos/' . $handle->assetid,
        ]);
    }

    public function poll_processing(stored_asset $asset): processing_status {
        $res = $this->api('GET', '/videos/' . $asset->assetid,
            ['fields' => 'upload.status,transcode.status']);
        $upload = $res['upload']['status'] ?? 'in_progress';
        $transcode = $res['transcode']['status'] ?? 'in_progress';

        if ($upload === 'error' || $transcode === 'error') {
            return new processing_status(processing_status::ERROR, 0, 'Vimeo reported error');
        }
        if ($upload === 'complete' && $transcode === 'complete') {
            return new processing_status(processing_status::COMPLETE, 100);
        }
        if ($upload !== 'complete') {
            return new processing_status(processing_status::PENDING, 25);
        }
        return new processing_status(processing_status::PROCESSING, 65);
    }

    public function apply_privacy(stored_asset $asset, privacy_spec $spec): void {
        $this->api('PATCH', '/videos/' . $asset->assetid, [
            'privacy' => [
                'view' => $spec->embedonly ? 'disable' : 'unlisted',
                'embed' => empty($spec->alloweddomains) ? 'public' : 'whitelist',
                'download' => !$spec->disabledownload,
            ],
        ]);
        foreach ($spec->alloweddomains as $domain) {
            // Whitelist each embed domain.
            $this->api('PUT', '/videos/' . $asset->assetid . '/privacy/domains/' . rawurlencode($domain));
        }
    }

    public function move_to_folder(stored_asset $asset, string $folderid): void {
        if ($folderid === '') {
            return;
        }
        $this->api('PUT', '/me/projects/' . rawurlencode($folderid) . '/videos/' . $asset->assetid);
    }

    public function attach_caption(stored_asset $asset, string $vtt, string $lang): void {
        // Create a text track, then PUT the VTT payload to its upload link.
        $track = $this->api('POST', '/videos/' . $asset->assetid . '/texttracks', [
            'type' => 'captions',
            'language' => $lang,
            'name' => strtoupper($lang),
        ]);
        $link = $track['link'] ?? null;
        if ($link) {
            $curl = $this->raw_curl();
            $curl->setHeader('Content-Type: text/vtt');
            $curl->setopt(['CURLOPT_CUSTOMREQUEST' => 'PUT']);
            $curl->post($link, $vtt); // Raw VTT body via PUT.
        }
    }

    public function get_embed(stored_asset $asset): embed_info {
        $embeddomain = (string) get_config('mod_edzsession', 'edzstore_vimeo_embeddomain');
        $src = 'https://player.vimeo.com/video/' . $asset->assetid;
        return new embed_info(embed_info::KIND_IFRAME, $src, [
            'width' => 640,
            'height' => 360,
            'allow' => 'autoplay; fullscreen; picture-in-picture',
            'referrerpolicy' => $embeddomain !== '' ? 'strict-origin' : 'no-referrer-when-downgrade',
        ]);
    }

    public function delete_asset(stored_asset $asset): void {
        $this->api('DELETE', '/videos/' . $asset->assetid);
    }

    // ---- Settings ---------------------------------------------------------

    public static function add_settings(\admin_settingpage $page): void {
        $p = self::config_prefix();
        $page->add(new \admin_setting_heading("{$p}/heading",
            get_string('provider_vimeo', 'mod_edzsession'),
            get_string('provider_vimeo_desc', 'mod_edzsession')));
        $page->add(new \admin_setting_configpasswordunmask("mod_edzsession/{$p}_pat",
            get_string('vimeo_pat', 'mod_edzsession'),
            get_string('vimeo_pat_desc', 'mod_edzsession'), ''));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_defaultfolder",
            get_string('vimeo_defaultfolder', 'mod_edzsession'),
            get_string('vimeo_defaultfolder_desc', 'mod_edzsession'), ''));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_embeddomain",
            get_string('vimeo_embeddomain', 'mod_edzsession'),
            get_string('vimeo_embeddomain_desc', 'mod_edzsession'), ''));
    }

    public static function config_prefix(): string {
        return 'edzstore_vimeo';
    }

    // ---- HTTP helpers -----------------------------------------------------

    /**
     * JSON Vimeo API call with bearer auth + basic 429 backoff.
     *
     * @param string $method GET|POST|PATCH|PUT|DELETE
     * @param string $path e.g. '/me/videos'
     * @param array $params body (POST/PATCH) or query (GET)
     * @return array decoded JSON (empty array for 204)
     */
    private function api(string $method, string $path, array $params = []): array {
        $url = self::API . $path;
        $attempts = 0;
        do {
            $curl = $this->raw_curl();
            $curl->setHeader('Content-Type: application/json');
            $method = strtoupper($method);
            if ($method === 'GET') {
                $query = $params ? ('?' . http_build_query($params)) : '';
                $response = $curl->get($url . $query);
            } else if ($method === 'DELETE') {
                $response = $curl->delete($url);
            } else {
                $json = $params ? json_encode($params) : '';
                $response = $curl->{strtolower($method)}($url, $json);
            }
            $info = $curl->get_info();
            $code = (int) ($info['http_code'] ?? 0);

            if ($code === 429 && $attempts < 3) {
                $attempts++;
                continue; // Adhoc task will re-run; brief in-call retry only.
            }
            if ($code >= 400) {
                throw new \moodle_exception('vimeoapierror', 'mod_edzsession', '', null,
                    "HTTP $code on $method $path: " . substr((string) $response, 0, 500));
            }
            return $response ? (json_decode($response, true) ?: []) : [];
        } while ($attempts < 3);

        throw new \moodle_exception('vimeoapierror', 'mod_edzsession', '', null, 'rate limited');
    }

    /** @return \curl a fresh Moodle curl configured with the bearer token. */
    private function raw_curl(): \curl {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $curl->setHeader('Authorization: bearer ' . $this->pat);
        $curl->setHeader('Accept: application/vnd.vimeo.*+json;version=3.4');
        $curl->setopt(['CURLOPT_TIMEOUT' => 60, 'CURLOPT_CONNECTTIMEOUT' => 15]);
        return $curl;
    }

    /** Extract the trailing numeric id from a Vimeo resource uri. */
    private function id_from_uri(string $uri): string {
        if ($uri === '') {
            return '';
        }
        $parts = explode('/', rtrim($uri, '/'));
        return end($parts) ?: '';
    }
}
