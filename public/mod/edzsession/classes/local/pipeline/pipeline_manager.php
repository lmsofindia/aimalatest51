<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\pipeline;

use mod_edzsession\local\account_vault;
use mod_edzsession\local\provider_manager;
use mod_edzsession\local\storage\upload_request;
use mod_edzsession\local\storage\stored_asset;
use mod_edzsession\local\storage\privacy_spec;
use mod_edzsession\local\meeting\recording_asset;

/**
 * Drives the offload state machine. Every step is idempotent and re-entrant:
 * calling advance() repeatedly moves a recording forward one step and is safe
 * to run from both the scheduled sweep and an adhoc per-recording task.
 *
 * The manager only ever calls the storage_provider / meeting_provider
 * interfaces — it has no idea whether it is offloading to Vimeo or S3.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pipeline_manager {

    /** Advance all non-terminal recordings by one step. Returns count advanced. */
    public static function advance_all(int $limit = 50): int {
        global $DB;
        // state is a CHAR column: compare directly (no sql_compare_text).
        list($insql, $params) = $DB->get_in_or_equal(states::TERMINAL, SQL_PARAMS_NAMED, 'st', false);
        $rows = $DB->get_records_select('edzsession_recording',
            "state $insql", $params, 'timemodified ASC', '*', 0, $limit);
        $n = 0;
        foreach ($rows as $row) {
            if (self::advance($row)) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Advance one recording by a single step.
     *
     * @param \stdClass $rec edzsession_recording row
     * @return bool true if the state changed
     */
    public static function advance(\stdClass $rec): bool {
        global $DB;
        if (states::is_terminal($rec->state)) {
            return false;
        }

        // Per-recording lock so the scheduled sweep and the adhoc task can never
        // process the same row at once (which would double-upload).
        $lockfactory = \core\lock\lock_config::get_lock_factory('mod_edzsession_pipeline');
        $lock = $lockfactory->get_lock('rec_' . $rec->id, 0);
        if (!$lock) {
            return false; // Someone else holds it; they'll advance it.
        }

        try {
            // Re-read under the lock: state may have moved since selection.
            $fresh = $DB->get_record('edzsession_recording', ['id' => $rec->id]);
            if (!$fresh || states::is_terminal($fresh->state)) {
                return false;
            }
            foreach ((array) $fresh as $k => $v) {
                $rec->$k = $v;
            }

            $before = $rec->state;
            try {
                $ctx = self::context_for($rec);
                $next = self::run_step($rec, $ctx);
                if ($next !== null && $next !== $rec->state) {
                    self::transition($rec, $next, 'ok');
                }
                if ((int) $rec->attempts !== 0) {
                    $DB->set_field('edzsession_recording', 'attempts', 0, ['id' => $rec->id]);
                }
            } catch (\Throwable $e) {
                self::handle_failure($rec, $e);
            }
            return $rec->state !== $before;
        } finally {
            $lock->release();
        }
    }

    /** Execute the step for the current state; return the next state (or same). */
    private static function run_step(\stdClass $rec, \stdClass $ctx): ?string {
        $storage = $ctx->storage;

        switch ($rec->state) {
            case states::DISCOVERED:
                // Main video was selected at discovery time; nothing else to do.
                return states::SELECTED;

            case states::SELECTED:
                $quota = $storage->get_quota();
                if ($quota !== null && !$quota->can_fit((int) $rec->sizebytes)) {
                    throw new \moodle_exception('quotaexceeded', 'mod_edzsession');
                }
                return states::QUOTA_CHECKED;

            case states::QUOTA_CHECKED:
                $asset = self::do_upload($rec, $ctx);
                self::store_asset($rec, $asset);
                return states::UPLOADING;

            case states::UPLOADING:
                // Poll the provider's own processing pipeline.
                $asset = new stored_asset($rec->storageprovider, (string) $rec->assetid);
                $status = $storage->poll_processing($asset);
                if ($status->is_error()) {
                    throw new \moodle_exception('providerprocessingerror', 'mod_edzsession', '',
                        $status->message);
                }
                return $status->is_complete() ? states::PROCESSING : states::UPLOADING;

            case states::PROCESSING:
                return states::VERIFIED;

            case states::VERIFIED:
                self::do_finalize($rec, $ctx);
                // If the source is to be kept, FINALIZED is terminal; otherwise
                // move to PENDING_DELETE so deletion actually gets a chance to run.
                $policy = get_config('mod_edzsession', 'sourcedeletionpolicy') ?: 'never';
                return ($policy === 'never') ? states::FINALIZED : states::PENDING_DELETE;

            case states::PENDING_DELETE:
                return self::maybe_delete_source($rec, $ctx);
        }
        return null;
    }

    /** Perform the upload (pull-preferred, stream fallback). */
    private static function do_upload(\stdClass $rec, \stdClass $ctx): stored_asset {
        $storage = $ctx->storage;
        $occ = $ctx->occurrence;
        $title = $ctx->edzsession->name . ' - ' . userdate($occ->starttime, '%Y-%m-%d');

        $folderid = null;
        if ($storage->supports_folders() && !empty($ctx->edzsession->storagefolderid)) {
            $folderid = $ctx->edzsession->storagefolderid;
        }

        // Resolve a fresh, tokenised source URL from the meeting provider using
        // the stored base download URL (tokens expire, so we re-token each try).
        $sourceurl = (string) ($rec->sourceurl ?? '');
        if ($ctx->meeting && $ctx->account && $sourceurl !== '') {
            $asset = new recording_asset((string) $rec->sourceuuid, 'MP4',
                $sourceurl, (int) $rec->sizebytes);
            $download = $ctx->meeting->get_download($asset, $ctx->account);
            $sourceurl = $download->url;
        }

        $req = new upload_request($title, (int) $rec->id,
            $sourceurl !== '' ? $sourceurl : null, (int) $rec->sizebytes, $folderid);

        if ($storage->supports_pull_upload() && $req->sourceurl) {
            $handle = $storage->begin_upload($req);
            return $storage->finalize_upload($handle);
        }

        if ($storage->supports_stream_upload() && $req->sourceurl) {
            return self::stream_through($storage, $req);
        }

        throw new \moodle_exception('noviableuploadmethod', 'mod_edzsession');
    }

    /** Download the source and push it through in chunks (fallback path). */
    private static function stream_through($storage, upload_request $req): stored_asset {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $tmp = make_request_directory() . '/rec.mp4';
        $fp = fopen($tmp, 'w');
        $curl = new \curl();
        $curl->download_one($req->sourceurl, null, ['filepath' => $tmp, 'timeout' => 0]);
        if (is_resource($fp)) {
            fclose($fp);
        }
        if (!file_exists($tmp) || filesize($tmp) === 0) {
            throw new \moodle_exception('sourcedownloadfailed', 'mod_edzsession');
        }

        $req->sizebytes = filesize($tmp);
        $handle = $storage->begin_upload($req);
        $in = fopen($tmp, 'r');
        $chunksize = 5 * 1024 * 1024; // 5MB.
        while (!feof($in)) {
            $bytes = fread($in, $chunksize);
            if ($bytes === '' || $bytes === false) {
                break;
            }
            $storage->push_chunk($handle, $bytes);
        }
        fclose($in);
        @unlink($tmp);
        return $storage->finalize_upload($handle);
    }

    /** Apply privacy, move to folder, then store the embed info. */
    private static function do_finalize(\stdClass $rec, \stdClass $ctx): void {
        global $DB, $CFG;
        $storage = $ctx->storage;
        $asset = new stored_asset($rec->storageprovider, (string) $rec->assetid);

        $domains = [];
        $embeddomain = (string) get_config('mod_edzsession', 'edzstore_vimeo_embeddomain');
        if ($embeddomain !== '') {
            $domains[] = $embeddomain;
        } else {
            $domains[] = parse_url($CFG->wwwroot ?? '', PHP_URL_HOST) ?: '';
        }
        $spec = new privacy_spec(true, array_filter($domains), true);
        $storage->apply_privacy($asset, $spec);

        if ($storage->supports_folders() && !empty($ctx->edzsession->storagefolderid)) {
            $storage->move_to_folder($asset, $ctx->edzsession->storagefolderid);
        }

        $embed = $storage->get_embed($asset);
        $DB->set_field('edzsession_recording', 'embedjson', json_encode([
            'kind' => $embed->kind, 'url' => $embed->url, 'attrs' => $embed->attrs,
        ]), ['id' => $rec->id]);
    }

    /** Delete the source recording if policy allows and the grace has elapsed. */
    private static function maybe_delete_source(\stdClass $rec, \stdClass $ctx): string {
        $policy = get_config('mod_edzsession', 'sourcedeletionpolicy') ?: 'never';
        if ($policy === 'never' || !$ctx->meeting || !$ctx->account) {
            return states::FINALIZED; // Terminal: keep the source.
        }
        if ($policy === 'after_verified_grace') {
            $grace = (int) (get_config('mod_edzsession', 'gracehours') ?: 48) * HOURSECS;
            if ((time() - (int) $rec->timemodified) < $grace) {
                return states::PENDING_DELETE; // Wait; re-evaluated on later ticks.
            }
        }
        try {
            $asset = new recording_asset((string) $rec->sourceuuid, 'MP4', '');
            $ctx->meeting->delete_recording($asset, $ctx->account);
            return states::SOURCE_DELETED;
        } catch (\Throwable $e) {
            // Deletion unsupported/failed: never lose the source, park at finalized.
            self::log($rec->id, states::PENDING_DELETE, states::FINALIZED,
                'source deletion skipped: ' . $e->getMessage());
            return states::FINALIZED;
        }
    }

    // ---- Support ----------------------------------------------------------

    /** Build the objects a step needs (storage, meeting, account, occurrence). */
    private static function context_for(\stdClass $rec): \stdClass {
        global $DB;
        $occ = $DB->get_record('edzsession_occurrence', ['id' => $rec->occurrenceid], '*', MUST_EXIST);
        $edzsession = $DB->get_record('edzsession', ['id' => $occ->edzsessionid], '*', MUST_EXIST);

        $ctx = new \stdClass();
        $ctx->occurrence = $occ;
        $ctx->edzsession = $edzsession;
        $ctx->storage = provider_manager::get_storage($rec->storageprovider);
        $ctx->meeting = null;
        $ctx->account = null;
        if (!empty($edzsession->accountid)) {
            try {
                $ctx->account = account_vault::get((int) $edzsession->accountid);
                $ctx->meeting = provider_manager::get_meeting($edzsession->meetingprovider);
            } catch (\Throwable $e) {
                $ctx->account = null;
            }
        }
        return $ctx;
    }

    private static function store_asset(\stdClass $rec, stored_asset $asset): void {
        global $DB;
        $DB->set_field('edzsession_recording', 'assetid', $asset->assetid, ['id' => $rec->id]);
        $rec->assetid = $asset->assetid;
    }

    private static function transition(\stdClass $rec, string $to, string $message): void {
        global $DB;
        $from = $rec->state;
        $DB->update_record('edzsession_recording', (object) [
            'id' => $rec->id, 'state' => $to, 'timemodified' => time(),
        ]);
        $rec->state = $to;
        self::log($rec->id, $from, $to, $message);
    }

    private static function handle_failure(\stdClass $rec, \Throwable $e): void {
        global $DB;
        $attempts = (int) $rec->attempts + 1;
        $update = (object) [
            'id' => $rec->id,
            'attempts' => $attempts,
            'lasterror' => substr($e->getMessage(), 0, 1000),
            'timemodified' => time(),
        ];
        if ($attempts >= states::MAX_ATTEMPTS) {
            $update->state = states::FAILED;
            self::log($rec->id, $rec->state, states::FAILED, 'max attempts: ' . $e->getMessage());
            $rec->state = states::FAILED;
        } else {
            self::log($rec->id, $rec->state, $rec->state,
                "attempt {$attempts} failed: " . $e->getMessage());
        }
        $DB->update_record('edzsession_recording', $update);
    }

    private static function log(int $recordingid, ?string $from, ?string $to, string $message): void {
        global $DB;
        $DB->insert_record('edzsession_pipeline_log', (object) [
            'recordingid' => $recordingid,
            'fromstate' => $from,
            'tostate' => $to,
            'message' => substr($message, 0, 1000),
            'timecreated' => time(),
        ]);
    }

    /**
     * Insert a recording row for offload (idempotent by sourceuuid) and queue
     * an adhoc step. Called by discovery + webhook.
     *
     * @param int $occurrenceid
     * @param recording_asset $asset
     * @param string $storageprovider provider machine name
     * @return int|null recording id, or null if it already existed / storage is none
     */
    public static function enqueue(int $occurrenceid, recording_asset $asset, string $storageprovider): ?int {
        global $DB;
        if ($storageprovider === '' || $storageprovider === 'none') {
            return null;
        }
        if ($DB->record_exists('edzsession_recording', ['sourceuuid' => $asset->uuid])) {
            return null; // Idempotency: already tracked.
        }
        $now = time();
        try {
            $id = $DB->insert_record('edzsession_recording', (object) [
                'occurrenceid' => $occurrenceid,
                'sourceuuid' => $asset->uuid,
                'storageprovider' => $storageprovider,
                'state' => states::DISCOVERED,
                'sizebytes' => $asset->sizebytes,
                'sourceurl' => $asset->downloadurl,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        } catch (\dml_write_exception $e) {
            // Concurrent delivery won the unique(sourceuuid) race — already tracked.
            return null;
        }

        $adhoc = new \mod_edzsession\task\run_recording_step();
        $adhoc->set_custom_data(['recordingid' => $id]);
        \core\task\manager::queue_adhoc_task($adhoc, true);
        return (int) $id;
    }
}
