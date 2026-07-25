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
 * Amazon S3 (and S3-compatible) storage provider.
 *
 * Dependency-free: implements AWS Signature Version 4 signing and multipart
 * upload directly over Moodle's curl. No transcoding step — an object is ready
 * the moment it is uploaded. Durable playback uses a configured CDN base URL
 * (recommended, e.g. CloudFront) or, failing that, a time-limited presigned URL.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class s3_provider implements storage_provider {

    /** @var \stdClass Cached config. */
    private $config;

    public function __construct() {
        $this->config = (object) [
            'region'    => trim((string) get_config('mod_edzsession', 'edzstore_s3_region')),
            'bucket'    => trim((string) get_config('mod_edzsession', 'edzstore_s3_bucket')),
            'accesskey' => trim((string) get_config('mod_edzsession', 'edzstore_s3_accesskey')),
            'secretkey' => trim((string) get_config('mod_edzsession', 'edzstore_s3_secretkey')),
            'endpoint'  => trim((string) get_config('mod_edzsession', 'edzstore_s3_endpoint')),
            'cdnbase'   => trim((string) get_config('mod_edzsession', 'edzstore_s3_cdnbase')),
        ];
    }

    public static function get_name(): string {
        return 's3';
    }
    public static function get_display_name(): string {
        return get_string('provider_s3', 'mod_edzsession');
    }
    public function is_configured(): bool {
        return $this->config->bucket !== '' && $this->config->region !== ''
            && $this->config->accesskey !== '' && $this->config->secretkey !== '';
    }

    // ---- Capabilities -----------------------------------------------------

    public function supports_pull_upload(): bool {
        return false;
    }
    public function supports_stream_upload(): bool {
        return true;
    }
    public function supports_folders(): bool {
        return true; // Key prefixes act as folders.
    }
    public function supports_captions(): bool {
        return true; // Sidecar .vtt object.
    }
    public function supports_delete(): bool {
        return true;
    }
    public function get_quota(): ?storage_quota {
        return null; // S3 is effectively unbounded.
    }

    public function list_folders(): array {
        // List common prefixes at the bucket root (Delimiter='/').
        try {
            $res = $this->request('GET', '', ['list-type' => '2', 'delimiter' => '/']);
            $out = [];
            if (preg_match_all('#<Prefix>([^<]+)</Prefix>#', $res['body'], $m)) {
                foreach ($m[1] as $prefix) {
                    $label = rtrim($prefix, '/');
                    if ($label !== '') {
                        $out[$prefix] = $label;
                    }
                }
            }
            return $out ?: ['recordings/' => 'recordings'];
        } catch (\Throwable $e) {
            return ['recordings/' => 'recordings'];
        }
    }

    public function ensure_folder(string $label): string {
        // S3 prefixes are implicit; just normalise to "<label>/".
        $label = trim($label, '/');
        return $label === '' ? '' : $label . '/';
    }

    public function default_folder_id(): ?string {
        return 'recordings/';
    }

    // ---- Upload lifecycle (multipart) -------------------------------------

    public function begin_upload(upload_request $req): upload_handle {
        $prefix = $req->folderid ? rtrim($req->folderid, '/') . '/' : '';
        $key = $prefix . $this->safe_key($req->title) . '-' . $req->recordingid . '.mp4';

        $res = $this->request('POST', $key, ['uploads' => ''], '',
            ['content-type' => $req->mimetype ?: 'video/mp4']);
        if (!preg_match('#<UploadId>([^<]+)</UploadId>#', $res['body'], $m)) {
            throw new \moodle_exception('s3apierror', 'mod_edzsession', '', null,
                'no UploadId: ' . substr($res['body'], 0, 300));
        }
        $handle = new upload_handle(upload_handle::MODE_STREAM, $key, $m[1]);
        $handle->data['key'] = $key;
        $handle->data['uploadid'] = $m[1];
        $handle->data['parts'] = [];
        return $handle;
    }

    public function push_chunk(upload_handle $handle, string $bytes): void {
        $partnumber = count($handle->data['parts']) + 1;
        $res = $this->request('PUT', $handle->data['key'], [
            'partNumber' => (string) $partnumber,
            'uploadId' => $handle->data['uploadid'],
        ], $bytes);
        $etag = $res['headers']['etag'] ?? '';
        if ($etag === '') {
            throw new \moodle_exception('s3apierror', 'mod_edzsession', '', null, 'no ETag on part ' . $partnumber);
        }
        $handle->data['parts'][] = ['PartNumber' => $partnumber, 'ETag' => $etag];
        $handle->offset += strlen($bytes);
    }

    public function finalize_upload(upload_handle $handle): stored_asset {
        $xml = '<CompleteMultipartUpload>';
        foreach ($handle->data['parts'] as $p) {
            $xml .= '<Part><PartNumber>' . $p['PartNumber'] . '</PartNumber>'
                . '<ETag>' . $p['ETag'] . '</ETag></Part>';
        }
        $xml .= '</CompleteMultipartUpload>';
        $res = $this->request('POST', $handle->data['key'], ['uploadId' => $handle->data['uploadid']], $xml,
            ['content-type' => 'application/xml']);
        // AWS may return HTTP 200 with an <Error> body on a failed complete.
        if (strpos($res['body'], '<Error') !== false) {
            throw new \moodle_exception('s3apierror', 'mod_edzsession', '', null,
                'CompleteMultipartUpload failed: ' . substr($res['body'], 0, 400));
        }
        return new stored_asset('s3', $handle->data['key'], dirname($handle->data['key']) . '/',
            ['bucket' => $this->config->bucket]);
    }

    public function poll_processing(stored_asset $asset): processing_status {
        return new processing_status(processing_status::COMPLETE, 100);
    }

    public function apply_privacy(stored_asset $asset, privacy_spec $spec): void {
        // Objects stay private; access is via signed CDN / presigned URLs. Nothing
        // to toggle on the object here.
    }

    public function move_to_folder(stored_asset $asset, string $folderid): void {
        $prefix = rtrim($folderid, '/') . '/';
        $newkey = $prefix . basename($asset->assetid);
        if ($newkey === $asset->assetid) {
            return;
        }
        // S3 has no move: copy then delete the original.
        $this->request('PUT', $newkey, [], '', [
            'x-amz-copy-source' => '/' . $this->config->bucket . '/' . $this->encode_key($asset->assetid),
        ]);
        $this->request('DELETE', $asset->assetid);
        $asset->assetid = $newkey;
    }

    public function attach_caption(stored_asset $asset, string $vtt, string $lang): void {
        $this->request('PUT', $asset->assetid . '.' . $lang . '.vtt', [], $vtt,
            ['content-type' => 'text/vtt']);
    }

    public function get_embed(stored_asset $asset): embed_info {
        if ($this->config->cdnbase !== '') {
            $url = rtrim($this->config->cdnbase, '/') . '/' . ltrim($asset->assetid, '/');
        } else {
            // Fallback: 7-day presigned URL (max for SigV4). CDN is recommended
            // for durable embeds — see technical.md.
            $url = $this->presign_get($asset->assetid, 7 * DAYSECS);
        }
        return new embed_info(embed_info::KIND_VIDEO, $url, ['controls' => true]);
    }

    public function delete_asset(stored_asset $asset): void {
        $this->request('DELETE', $asset->assetid);
    }

    // ---- Settings + test --------------------------------------------------

    public static function add_settings(\admin_settingpage $page): void {
        $p = self::config_prefix();
        $page->add(new \admin_setting_heading("{$p}/heading",
            get_string('provider_s3', 'mod_edzsession'),
            get_string('provider_s3_desc', 'mod_edzsession')));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_region",
            get_string('s3_region', 'mod_edzsession'), '', 'us-east-1'));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_bucket",
            get_string('s3_bucket', 'mod_edzsession'), '', ''));
        $page->add(new \admin_setting_configpasswordunmask("mod_edzsession/{$p}_accesskey",
            get_string('s3_accesskey', 'mod_edzsession'), '', ''));
        $page->add(new \admin_setting_configpasswordunmask("mod_edzsession/{$p}_secretkey",
            get_string('s3_secretkey', 'mod_edzsession'), '', ''));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_endpoint",
            get_string('s3_endpoint', 'mod_edzsession'),
            get_string('s3_endpoint_desc', 'mod_edzsession'), ''));
        $page->add(new \admin_setting_configtext("mod_edzsession/{$p}_cdnbase",
            get_string('s3_cdnbase', 'mod_edzsession'),
            get_string('s3_cdnbase_desc', 'mod_edzsession'), ''));
    }

    public static function config_prefix(): string {
        return 'edzstore_s3';
    }

    public function test_connection(): connection_result {
        if (!$this->is_configured()) {
            return connection_result::na(get_string('test_notconfigured', 'mod_edzsession'));
        }
        try {
            // List with max-keys=0 to verify credentials + region + access.
            $this->request('GET', '', ['list-type' => '2', 'max-keys' => '0']);
            return connection_result::ok(get_string('test_ok_as', 'mod_edzsession',
                $this->config->bucket . ' @ ' . $this->config->region));
        } catch (\Throwable $e) {
            return connection_result::fail(get_string('test_failed', 'mod_edzsession'), $e->getMessage());
        }
    }

    // ---- HTTP + SigV4 -----------------------------------------------------

    /**
     * Perform a signed S3 request.
     *
     * @param string $method
     * @param string $key object key (no leading slash), '' for bucket-level
     * @param array $query query params
     * @param string $payload request body
     * @param array $extraheaders lowercase header => value
     * @return array ['body'=>string,'headers'=>array,'code'=>int]
     */
    private function request(string $method, string $key, array $query = [],
            string $payload = '', array $extraheaders = []): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        [$host, $uri] = $this->host_and_uri($key);
        $amzdate = gmdate('Ymd\THis\Z');
        $datestamp = gmdate('Ymd');
        $payloadhash = hash('sha256', $payload);

        $headers = array_change_key_case($extraheaders, CASE_LOWER);
        $headers['host'] = $host;
        $headers['x-amz-content-sha256'] = $payloadhash;
        $headers['x-amz-date'] = $amzdate;

        ksort($headers);
        $canonicalheaders = '';
        $signedheaderslist = [];
        foreach ($headers as $k => $v) {
            $canonicalheaders .= $k . ':' . trim($v) . "\n";
            $signedheaderslist[] = $k;
        }
        $signedheaders = implode(';', $signedheaderslist);

        $canonicalquery = $this->canonical_query($query);
        $canonicalrequest = $method . "\n" . $uri . "\n" . $canonicalquery . "\n"
            . $canonicalheaders . "\n" . $signedheaders . "\n" . $payloadhash;

        $scope = $datestamp . '/' . $this->config->region . '/s3/aws4_request';
        $stringtosign = "AWS4-HMAC-SHA256\n" . $amzdate . "\n" . $scope . "\n"
            . hash('sha256', $canonicalrequest);
        $signingkey = $this->signing_key($datestamp);
        $signature = hash_hmac('sha256', $stringtosign, $signingkey);

        $authorization = 'AWS4-HMAC-SHA256 Credential=' . $this->config->accesskey . '/' . $scope
            . ', SignedHeaders=' . $signedheaders . ', Signature=' . $signature;

        $scheme = 'https://';
        $url = $scheme . $host . $uri . ($canonicalquery !== '' ? '?' . $canonicalquery : '');

        $curl = new \curl();
        foreach ($headers as $k => $v) {
            if ($k === 'host') {
                continue;
            }
            $curl->setHeader($k . ': ' . $v);
        }
        $curl->setHeader('Authorization: ' . $authorization);
        $curl->setopt(['CURLOPT_TIMEOUT' => 120, 'CURLOPT_CONNECTTIMEOUT' => 20, 'CURLOPT_HEADER' => false]);

        $method = strtoupper($method);
        if ($method === 'GET' || $method === 'HEAD') {
            $response = $curl->get($url);
        } else if ($method === 'DELETE') {
            $response = $curl->delete($url);
        } else if ($method === 'PUT') {
            // Raw-body PUT: Moodle curl::put() is for file uploads, so use a
            // custom-request POST which sends the raw payload as the body.
            $curl->setopt(['CURLOPT_CUSTOMREQUEST' => 'PUT']);
            $response = $curl->post($url, $payload);
        } else { // POST.
            $response = $curl->post($url, $payload);
        }
        $info = $curl->get_info();
        $code = (int) ($info['http_code'] ?? 0);
        if ($code >= 400) {
            throw new \moodle_exception('s3apierror', 'mod_edzsession', '', null,
                "HTTP $code on $method /$key: " . substr((string) $response, 0, 400));
        }
        return [
            'body' => (string) $response,
            'headers' => array_change_key_case($curl->getResponse() ?: [], CASE_LOWER),
            'code' => $code,
        ];
    }

    /** Resolve [host, uri] for virtual-hosted (AWS) or path-style (endpoint). */
    private function host_and_uri(string $key): array {
        $enckey = $this->encode_key($key);
        if ($this->config->endpoint !== '') {
            $host = preg_replace('#^https?://#', '', $this->config->endpoint);
            $host = rtrim($host, '/');
            $uri = '/' . $this->config->bucket . ($enckey !== '' ? '/' . $enckey : '');
        } else {
            $host = $this->config->bucket . '.s3.' . $this->config->region . '.amazonaws.com';
            $uri = '/' . ($enckey !== '' ? $enckey : '');
        }
        return [$host, $uri];
    }

    /** Percent-encode a key per SigV4 (encode each segment, keep slashes). */
    private function encode_key(string $key): string {
        $parts = array_map('rawurlencode', explode('/', $key));
        return implode('/', $parts);
    }

    private function canonical_query(array $query): string {
        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[rawurlencode($k)] = rawurlencode((string) $v);
        }
        ksort($pairs, SORT_STRING); // AWS requires byte-ordered keys.
        $out = [];
        foreach ($pairs as $k => $v) {
            $out[] = $k . '=' . $v;
        }
        return implode('&', $out);
    }

    private function signing_key(string $datestamp): string {
        $kdate = hash_hmac('sha256', $datestamp, 'AWS4' . $this->config->secretkey, true);
        $kregion = hash_hmac('sha256', $this->config->region, $kdate, true);
        $kservice = hash_hmac('sha256', 's3', $kregion, true);
        return hash_hmac('sha256', 'aws4_request', $kservice, true);
    }

    /** Build a presigned GET URL (query-parameter SigV4). */
    private function presign_get(string $key, int $expires): string {
        [$host, $uri] = $this->host_and_uri($key);
        $amzdate = gmdate('Ymd\THis\Z');
        $datestamp = gmdate('Ymd');
        $scope = $datestamp . '/' . $this->config->region . '/s3/aws4_request';

        $query = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $this->config->accesskey . '/' . $scope,
            'X-Amz-Date' => $amzdate,
            'X-Amz-Expires' => (string) $expires,
            'X-Amz-SignedHeaders' => 'host',
        ];
        $canonicalquery = $this->canonical_query($query);
        $canonicalrequest = "GET\n" . $uri . "\n" . $canonicalquery . "\n"
            . 'host:' . $host . "\n\nhost\nUNSIGNED-PAYLOAD";
        $stringtosign = "AWS4-HMAC-SHA256\n" . $amzdate . "\n" . $scope . "\n"
            . hash('sha256', $canonicalrequest);
        $signature = hash_hmac('sha256', $stringtosign, $this->signing_key($datestamp));

        return 'https://' . $host . $uri . '?' . $canonicalquery . '&X-Amz-Signature=' . $signature;
    }

    private function safe_key(string $title): string {
        return preg_replace('/[^A-Za-z0-9_\-]+/', '_', $title);
    }
}
