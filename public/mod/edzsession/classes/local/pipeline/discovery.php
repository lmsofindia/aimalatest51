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

/**
 * Reconcile-poll discovery of recordings for ended occurrences that did not
 * arrive via a webhook. Enqueues each new video into the offload pipeline
 * (idempotent by source uuid). The webhook path shares pipeline_manager::enqueue.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discovery {

    /**
     * @param int $lookbackdays only look at occurrences ended within N days
     * @return int number of recordings enqueued
     */
    public static function run(int $lookbackdays = 14): int {
        global $DB;
        $now = time();
        $since = $now - ($lookbackdays * DAYSECS);

        // No remoteuuid requirement: we resolve the instance UUID from Zoom, so
        // recordings are discovered even without a webhook configured.
        $sql = "SELECT o.*, e.accountid, e.meetingprovider, e.storageprovider AS actstorage,
                       e.remotemeetingid AS parentmeetingid
                  FROM {edzsession_occurrence} o
                  JOIN {edzsession} e ON e.id = o.edzsessionid
                 WHERE o.starttime > :since
                   AND (o.starttime + (o.duration * 60)) < :now
                   AND e.accountid IS NOT NULL
                   AND e.remotemeetingid IS NOT NULL";
        $rows = $DB->get_records_sql($sql, ['since' => $since, 'now' => $now]);

        $enqueued = 0;
        foreach ($rows as $occ) {
            $storagename = provider_manager::storage_name_for_activity((object) [
                'storageprovider' => $occ->actstorage,
            ]);
            if ($storagename === 'none' || $storagename === '') {
                continue; // Offload disabled for this activity.
            }
            try {
                $account = account_vault::get((int) $occ->accountid);
                $provider = provider_manager::get_meeting($occ->meetingprovider);
                $meetingid = (string) ($occ->remotemeetingid ?: $occ->parentmeetingid);

                // Resolve + persist the real occurrence UUID if missing.
                $uuid = $occ->remoteuuid;
                if (empty($uuid)) {
                    $probe = new \mod_edzsession\local\meeting\remote_meeting($meetingid, '', null);
                    $uuid = $provider->resolve_occurrence_uuid($probe, (int) $occ->starttime, $account);
                    if (!empty($uuid)) {
                        $DB->set_field('edzsession_occurrence', 'remoteuuid', $uuid, ['id' => $occ->id]);
                    }
                }
                if (empty($uuid)) {
                    continue; // Instance not available yet; try again next run.
                }

                $meeting = new \mod_edzsession\local\meeting\remote_meeting($meetingid, '', $uuid);
                foreach ($provider->list_recordings($meeting, $account) as $asset) {
                    if (!$asset->is_video()) {
                        continue; // Only offload the main video; transcript = caption (P5).
                    }
                    if (pipeline_manager::enqueue((int) $occ->id, $asset, $storagename) !== null) {
                        $enqueued++;
                    }
                }
            } catch (\Throwable $e) {
                mtrace('edzsession discovery failed for occurrence ' . $occ->id . ': ' . $e->getMessage());
            }
        }
        return $enqueued;
    }
}
