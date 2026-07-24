<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * Contract every recording-storage backend must implement.
 *
 * The offload pipeline talks ONLY to this interface. No core code references a
 * concrete provider (Vimeo / S3 / ...). Adding a provider = one new class that
 * implements this + one line in {@see \mod_edzsession\local\provider_manager}.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface storage_provider {

    // ---- Identity ---------------------------------------------------------

    /** @return string machine name, e.g. 'vimeo'. Must be unique + [a-z0-9_]. */
    public static function get_name(): string;

    /** @return string human label (localised). */
    public static function get_display_name(): string;

    /** @return bool true when credentials are present and correctly shaped. */
    public function is_configured(): bool;

    // ---- Capability flags (pipeline branches on THESE, never on the name) --

    /** Provider fetches the source URL itself (server-to-server pull upload). */
    public function supports_pull_upload(): bool;

    /** We stream bytes through ourselves (tus / multipart). */
    public function supports_stream_upload(): bool;

    /** Provider has a folder / project / prefix concept. */
    public function supports_folders(): bool;

    /** Provider can attach a caption/subtitle (VTT) track. */
    public function supports_captions(): bool;

    /** Provider can delete a stored asset. */
    public function supports_delete(): bool;

    /** @return storage_quota|null null when the provider has no quota concept (e.g. S3). */
    public function get_quota(): ?storage_quota;

    // ---- Destination folders (only meaningful if supports_folders) ---------

    /** @return array<string,string> folderid => label, for the mod_form picker. */
    public function list_folders(): array;

    /** Create-or-get a folder by label; @return string folder id. */
    public function ensure_folder(string $label): string;

    // ---- Offload lifecycle (return value objects, never bare bools) --------

    /** Start an upload: open a pull job or a stream session. */
    public function begin_upload(upload_request $req): upload_handle;

    /** Push one chunk of bytes (stream mode only). */
    public function push_chunk(upload_handle $handle, string $bytes): void;

    /** Finish the upload; @return stored_asset with the provider asset id. */
    public function finalize_upload(upload_handle $handle): stored_asset;

    /** Poll transcode/verify progress. */
    public function poll_processing(stored_asset $asset): processing_status;

    /** Apply privacy (domain whitelist, no-download, embed rules). */
    public function apply_privacy(stored_asset $asset, privacy_spec $spec): void;

    /** Move the asset into a destination folder. */
    public function move_to_folder(stored_asset $asset, string $folderid): void;

    /** Attach a caption track from a VTT string. */
    public function attach_caption(stored_asset $asset, string $vtt, string $lang): void;

    /** @return embed_info iframe/src + player metadata for the student view. */
    public function get_embed(stored_asset $asset): embed_info;

    /** Delete the stored asset (only call if supports_delete). */
    public function delete_asset(stored_asset $asset): void;

    // ---- Admin settings contribution --------------------------------------

    /**
     * Contribute this provider's settings to the plugin settings page.
     * Called by settings.php so a new provider self-registers its config.
     */
    public static function add_settings(\admin_settingpage $page): void;

    /** @return string config namespace prefix, e.g. 'edzstore_vimeo'. */
    public static function config_prefix(): string;
}
