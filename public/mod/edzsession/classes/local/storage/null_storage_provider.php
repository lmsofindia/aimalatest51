<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * Null object backing the "none" storage option.
 *
 * Lets the pipeline / UI code hold a storage_provider unconditionally without
 * null checks. When storage is 'none', recordings are simply not offloaded.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class null_storage_provider implements storage_provider {
    public static function get_name(): string {
        return 'none';
    }
    public static function get_display_name(): string {
        return get_string('provider_none', 'mod_edzsession');
    }
    public function is_configured(): bool {
        return true;
    }
    public function test_connection(): \mod_edzsession\local\connection_result {
        return \mod_edzsession\local\connection_result::na(
            get_string('test_none', 'mod_edzsession'));
    }
    public function supports_pull_upload(): bool {
        return false;
    }
    public function supports_stream_upload(): bool {
        return false;
    }
    public function supports_folders(): bool {
        return false;
    }
    public function supports_captions(): bool {
        return false;
    }
    public function supports_delete(): bool {
        return false;
    }
    public function get_quota(): ?storage_quota {
        return null;
    }
    public function list_folders(): array {
        return [];
    }
    public function ensure_folder(string $label): string {
        return '';
    }
    public function default_folder_id(): ?string {
        return null;
    }
    public function begin_upload(upload_request $req): upload_handle {
        throw new \coding_exception('null_storage_provider cannot upload');
    }
    public function push_chunk(upload_handle $handle, string $bytes): void {
    }
    public function finalize_upload(upload_handle $handle): stored_asset {
        throw new \coding_exception('null_storage_provider cannot upload');
    }
    public function poll_processing(stored_asset $asset): processing_status {
        return new processing_status(processing_status::COMPLETE, 100);
    }
    public function apply_privacy(stored_asset $asset, privacy_spec $spec): void {
    }
    public function move_to_folder(stored_asset $asset, string $folderid): void {
    }
    public function attach_caption(stored_asset $asset, string $vtt, string $lang): void {
    }
    public function get_embed(stored_asset $asset): embed_info {
        return new embed_info(embed_info::KIND_VIDEO, '');
    }
    public function delete_asset(stored_asset $asset): void {
    }
    public static function add_settings(\admin_settingpage $page): void {
    }
    public static function config_prefix(): string {
        return 'edzstore_none';
    }
}
