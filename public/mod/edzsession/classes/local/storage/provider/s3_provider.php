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
 * AWS S3 storage provider — SKELETON.
 *
 * This class exists to PROVE the abstraction: it loads, registers in the
 * provider registry, appears in the admin "Storage provider" dropdown, and
 * contributes its own settings page — all WITHOUT a single edit to the
 * pipeline, DB schema, mod_form, or view. The actual multipart upload is the
 * only thing left to fill in (marked TODO), and the exact seams are laid out
 * below so the next engineer can complete it in isolation.
 *
 * When the customer wants S3 instead of Vimeo, they select it here — the same
 * offload state machine drives it, because it only ever calls this interface.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class s3_provider implements storage_provider {

    /** @var \stdClass Cached config. */
    private $config;

    public function __construct() {
        $this->config = (object) [
            'region'    => get_config('mod_edzsession', 'edzstore_s3_region'),
            'bucket'    => get_config('mod_edzsession', 'edzstore_s3_bucket'),
            'accesskey' => get_config('mod_edzsession', 'edzstore_s3_accesskey'),
            'secretkey' => get_config('mod_edzsession', 'edzstore_s3_secretkey'),
            'endpoint'  => get_config('mod_edzsession', 'edzstore_s3_endpoint'),
            'cdnbase'   => get_config('mod_edzsession', 'edzstore_s3_cdnbase'),
        ];
    }

    public static function get_name(): string {
        return 's3';
    }

    public static function get_display_name(): string {
        return get_string('provider_s3', 'mod_edzsession');
    }

    public function is_configured(): bool {
        return !empty($this->config->bucket)
            && !empty($this->config->region)
            && !empty($this->config->accesskey)
            && !empty($this->config->secretkey);
    }

    // ---- Capabilities: S3 streams (no server-side pull), uses prefixes. ----

    public function supports_pull_upload(): bool {
        return false;
    }
    public function supports_stream_upload(): bool {
        return true;
    }
    public function supports_folders(): bool {
        return true; // key prefixes act as folders.
    }
    public function supports_captions(): bool {
        return true; // sidecar .vtt object.
    }
    public function supports_delete(): bool {
        return true;
    }
    public function get_quota(): ?storage_quota {
        return null; // S3 is effectively unbounded; no quota gate.
    }

    public function list_folders(): array {
        // A real impl would ListObjectsV2 with Delimiter='/' to enumerate prefixes.
        // For the skeleton we just echo back a configured default prefix.
        return ['recordings/' => 'recordings/'];
    }

    public function ensure_folder(string $label): string {
        // S3 has no real folders; a prefix "exists" implicitly. Normalise it.
        return rtrim($label, '/') . '/';
    }

    // ---- Upload lifecycle: the ONLY part left to implement. ---------------

    public function begin_upload(upload_request $req): upload_handle {
        $this->guard_stub();
        // TODO S3: CreateMultipartUpload -> return upload_handle with uploadId.
        //   $key = ($req->folderid ?: 'recordings/') . $this->safe_key($req->title) . '.mp4';
        //   $uploadid = $this->s3_create_multipart($key, $req->mimetype);
        //   $h = new upload_handle(upload_handle::MODE_STREAM, $key, $uploadid);
        //   $h->data['key'] = $key; $h->data['parts'] = [];
        //   return $h;
        throw new \moodle_exception('s3notimplemented', 'mod_edzsession');
    }

    public function push_chunk(upload_handle $handle, string $bytes): void {
        $this->guard_stub();
        // TODO S3: UploadPart (partNumber = count(parts)+1); collect ETag into
        //   $handle->data['parts'][] = ['PartNumber'=>$n, 'ETag'=>$etag];
        throw new \moodle_exception('s3notimplemented', 'mod_edzsession');
    }

    public function finalize_upload(upload_handle $handle): stored_asset {
        $this->guard_stub();
        // TODO S3: CompleteMultipartUpload($handle->data['key'], $handle->data['parts'])
        //   return new stored_asset('s3', $handle->data['key'], dirname($handle->data['key']).'/',
        //                           ['bucket'=>$this->config->bucket]);
        throw new \moodle_exception('s3notimplemented', 'mod_edzsession');
    }

    public function poll_processing(stored_asset $asset): processing_status {
        // S3 has no transcode step: as soon as the object exists it is "complete".
        return new processing_status(processing_status::COMPLETE, 100);
    }

    public function apply_privacy(stored_asset $asset, privacy_spec $spec): void {
        // TODO S3: object stays private; access is via signed CDN URLs (see get_embed).
        // Nothing to toggle on the object itself for the skeleton.
    }

    public function move_to_folder(stored_asset $asset, string $folderid): void {
        // TODO S3: CopyObject to new key + DeleteObject old key (S3 has no move).
    }

    public function attach_caption(stored_asset $asset, string $vtt, string $lang): void {
        // TODO S3: PutObject sidecar "<key>.<lang>.vtt".
    }

    public function get_embed(stored_asset $asset): embed_info {
        // A real impl returns a time-limited signed CDN URL. Skeleton returns the
        // (unsigned) CDN path so the shape is demonstrable.
        $base = rtrim((string) $this->config->cdnbase, '/');
        $url = $base !== '' ? $base . '/' . ltrim($asset->assetid, '/') : '';
        return new embed_info(embed_info::KIND_VIDEO, $url, ['controls' => true]);
    }

    public function delete_asset(stored_asset $asset): void {
        // TODO S3: DeleteObject($asset->assetid).
    }

    // ---- Settings: self-registered; this is what makes S3 appear in admin. -

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

    // ---- Helpers ----------------------------------------------------------

    private function guard_stub(): void {
        // Keeps behaviour explicit: uploads are not wired yet. The rest of the
        // provider (registration, settings, embed shape) is fully live.
        if (!PHPUNIT_TEST) {
            debugging('s3_provider upload path is a skeleton (see technical.md).', DEBUG_DEVELOPER);
        }
    }

    private function safe_key(string $title): string {
        return preg_replace('/[^A-Za-z0-9_\-]+/', '_', $title);
    }
}
