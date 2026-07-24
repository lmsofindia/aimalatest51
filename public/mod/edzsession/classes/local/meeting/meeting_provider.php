<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\meeting;

/**
 * Contract every meeting platform must implement (Zoom now; BBB/Teams later).
 *
 * Mirrors the storage_provider pattern so the two extension points feel the
 * same. Core never references "Zoom" — only this interface.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface meeting_provider {

    public static function get_name(): string;              // 'zoom'
    public static function get_display_name(): string;
    public function is_configured(account $account): bool;

    // ---- Meeting lifecycle ------------------------------------------------

    public function create_meeting(meeting_spec $spec, account $account): remote_meeting;
    public function update_meeting(remote_meeting $meeting, meeting_spec $spec, account $account): void;
    public function delete_meeting(remote_meeting $meeting, account $account): void;
    public function get_join_url(remote_meeting $meeting, \stdClass $user): string;

    // ---- Attendance -------------------------------------------------------

    /** @return participant_record[] deduped is the engine's job; return raw segments. */
    public function fetch_participants(remote_meeting $meeting, account $account): array;
    public function supports_webhooks(): bool;
    public function verify_webhook(string $payload, array $headers, account $account): bool;

    // ---- Recordings (bridge into the storage pipeline) --------------------

    /** @return recording_asset[] */
    public function list_recordings(remote_meeting $meeting, account $account): array;
    public function get_download(recording_asset $recording, account $account): download_ref;
    public function delete_recording(recording_asset $recording, account $account): void;

    // ---- Admin ------------------------------------------------------------

    public static function config_prefix(): string;
}
