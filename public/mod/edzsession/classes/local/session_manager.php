<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local;

use mod_edzsession\local\meeting\meeting_spec;

/**
 * Orchestrates the meeting lifecycle for an activity instance: create the
 * remote meeting (via the selected meeting provider + account) and expand the
 * schedule into occurrence rows.
 *
 * Kept out of lib.php so lib.php stays provider-neutral and thin.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_manager {

    /**
     * Create/refresh the remote meeting for an instance and (re)generate its
     * future occurrences. Never throws to the caller — failures are recorded on
     * meetingstatus so the teacher can retry, and the activity still saves.
     *
     * @param \stdClass $edzsession full DB record (must have id + schedulejson)
     */
    public static function provision(\stdClass $edzsession): void {
        global $DB;

        $schedule = schedule::from_json($edzsession->schedulejson ?? null);
        $joinurl = '';
        $status = 'pending';
        $remotemeetingid = $edzsession->remotemeetingid ?? null;

        // Only talk to the provider if an account is selected and configured.
        $account = self::resolve_account($edzsession);
        if ($account !== null) {
            try {
                $provider = provider_manager::get_meeting($edzsession->meetingprovider ?? null);
                $spec = new meeting_spec(
                    $edzsession->name,
                    (int) ($schedule->get_spec()['starttime'] ?? time()),
                    $schedule->duration_minutes(),
                    \core_date::get_user_timezone(),
                    !empty($edzsession->autorecord),
                    $schedule->provider_recurrence()
                );

                if (!empty($remotemeetingid)) {
                    $meeting = new meeting\remote_meeting($remotemeetingid, $joinurl);
                    $provider->update_meeting($meeting, $spec, $account);
                } else {
                    $meeting = $provider->create_meeting($spec, $account);
                    $remotemeetingid = $meeting->meetingid;
                    $joinurl = $meeting->joinurl;
                }
                $status = 'created';
            } catch (\Throwable $e) {
                $status = 'error';
                debugging('edzsession provision failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        // Resolve the storage destination folder (name -> provider folder id) so
        // recordings actually land in the chosen Vimeo project / S3 prefix.
        $storagefolderid = $edzsession->storagefolderid ?? null;
        if (!empty($edzsession->storagefoldername)) {
            try {
                $storage = provider_manager::storage_for_activity($edzsession);
                if ($storage->supports_folders() && $storage->is_configured()) {
                    $storagefolderid = $storage->ensure_folder($edzsession->storagefoldername);
                }
            } catch (\Throwable $e) {
                debugging('edzsession storage folder resolve failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        // Persist meeting result + resolved folder on the instance.
        $DB->update_record('edzsession', (object) [
            'id' => $edzsession->id,
            'remotemeetingid' => $remotemeetingid,
            'meetingstatus' => $status,
            'storagefolderid' => $storagefolderid,
            'timemodified' => time(),
        ]);

        // On the update path we don't get a fresh join url back; reuse the one
        // already stored on an existing occurrence so new rows aren't left blank.
        // (Filter in PHP — joinurl is a TEXT column, unsafe in a WHERE clause.)
        if ($joinurl === '') {
            $rows = $DB->get_records('edzsession_occurrence',
                ['edzsessionid' => $edzsession->id], 'id ASC', 'id, joinurl');
            foreach ($rows as $r) {
                if (!empty($r->joinurl)) {
                    $joinurl = $r->joinurl;
                    break;
                }
            }
        }

        self::sync_occurrences($edzsession->id, $schedule, $joinurl, (string) $remotemeetingid);
    }

    /**
     * Insert occurrence rows for all schedule times that don't exist yet, and
     * remove future rows that have dropped out of the schedule (only if they
     * have no attendance/recording attached — never destroy history).
     *
     * @param int $edzsessionid
     * @param schedule $schedule
     * @param string $joinurl base join url to store (may be empty)
     */
    public static function sync_occurrences(int $edzsessionid, schedule $schedule,
            string $joinurl = '', string $remotemeetingid = ''): void {
        global $DB;
        $now = time();
        $wanted = $schedule->occurrence_times();
        $duration = $schedule->duration_minutes();

        $existing = $DB->get_records('edzsession_occurrence', ['edzsessionid' => $edzsessionid], 'starttime ASC');
        $existingbytime = [];
        foreach ($existing as $row) {
            $existingbytime[(int) $row->starttime] = $row;
        }

        // Insert missing.
        foreach ($wanted as $time) {
            if (!isset($existingbytime[$time])) {
                $DB->insert_record('edzsession_occurrence', (object) [
                    'edzsessionid' => $edzsessionid,
                    'remotemeetingid' => $remotemeetingid !== '' ? $remotemeetingid : null,
                    'starttime' => $time,
                    'duration' => $duration,
                    'status' => 'scheduled',
                    'joinurl' => $joinurl,
                    'timemodified' => $now,
                ]);
            } else {
                if ($joinurl !== '' && empty($existingbytime[$time]->joinurl)) {
                    $DB->set_field('edzsession_occurrence', 'joinurl', $joinurl,
                        ['id' => $existingbytime[$time]->id]);
                }
                if ($remotemeetingid !== '' && empty($existingbytime[$time]->remotemeetingid)) {
                    $DB->set_field('edzsession_occurrence', 'remotemeetingid', $remotemeetingid,
                        ['id' => $existingbytime[$time]->id]);
                }
            }
        }

        // Prune future rows no longer in the schedule, but keep any with history.
        $wantedset = array_flip($wanted);
        foreach ($existing as $row) {
            if ((int) $row->starttime <= $now) {
                continue; // Past — never prune.
            }
            if (isset($wantedset[(int) $row->starttime])) {
                continue; // Still scheduled.
            }
            $hashistory = $DB->record_exists('edzsession_attendance_raw', ['occurrenceid' => $row->id])
                || $DB->record_exists('edzsession_recording', ['occurrenceid' => $row->id]);
            if (!$hashistory) {
                $DB->delete_records('edzsession_occurrence', ['id' => $row->id]);
            }
        }

        self::bump_cache($edzsessionid);
        self::sync_calendar_events($edzsessionid);
    }

    /**
     * Mirror the current occurrence rows into Moodle's calendar ({event} table) so
     * scheduled live classes show in the site calendar, the Upcoming events block
     * and the Dashboard Timeline block — for learners and teachers alike.
     *
     * Reconciles by start time (the natural key): create events for occurrences
     * that have none, update name/duration when they drift, and delete calendar
     * events whose occurrence has gone. One CALENDAR_EVENT_TYPE_ACTION event per
     * occurrence, so lib.php's provide_event_action() can attach a "Join" action.
     *
     * Never throws to the caller: calendar problems must not block saving the
     * activity or generating occurrences.
     *
     * @param int $edzsessionid
     */
    public static function sync_calendar_events(int $edzsessionid): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/calendar/lib.php');

        try {
            $edzsession = $DB->get_record('edzsession', ['id' => $edzsessionid],
                'id, course, name, intro, introformat');
            if (!$edzsession) {
                return;
            }

            $occurrences = $DB->get_records('edzsession_occurrence',
                ['edzsessionid' => $edzsessionid], 'starttime ASC', 'id, starttime, duration');

            // Existing calendar events for this instance, keyed by start time.
            $events = $DB->get_records('event',
                ['modulename' => 'edzsession', 'instance' => $edzsessionid], 'timestart ASC');
            $eventbytime = [];
            foreach ($events as $ev) {
                // If duplicates ever exist for one time, keep the first and drop the rest below.
                if (isset($eventbytime[(int) $ev->timestart])) {
                    self::delete_calendar_event((int) $ev->id);
                    continue;
                }
                $eventbytime[(int) $ev->timestart] = $ev;
            }

            $name = $edzsession->name;
            $desc = $edzsession->intro ?? '';
            $descformat = isset($edzsession->introformat) ? (int) $edzsession->introformat : FORMAT_HTML;

            $wanted = [];
            foreach ($occurrences as $occ) {
                $time = (int) $occ->starttime;
                $wanted[$time] = true;
                $duration = (int) $occ->duration * MINSECS;

                if (isset($eventbytime[$time])) {
                    $existing = $eventbytime[$time];
                    if ($existing->name !== $name || (int) $existing->timeduration !== $duration) {
                        $calevent = \calendar_event::load($existing->id);
                        $calevent->update((object) [
                            'name'         => $name,
                            'timestart'    => $time,
                            'timeduration' => $duration,
                        ], false);
                    }
                    continue;
                }

                // Create a new action event for this occurrence.
                $data = new \stdClass();
                $data->name         = $name;
                $data->description  = $desc;
                $data->format       = $descformat;
                $data->courseid     = (int) $edzsession->course;
                $data->groupid      = 0;
                $data->userid       = 0;
                $data->modulename   = 'edzsession';
                $data->instance     = $edzsessionid;
                $data->eventtype    = 'edzsession';
                $data->type         = CALENDAR_EVENT_TYPE_ACTION;
                $data->timestart    = $time;
                $data->timeduration = $duration;
                $data->timesort     = $time;
                $data->visible      = 1;
                \calendar_event::create($data, false);
            }

            // Delete events whose occurrence no longer exists.
            foreach ($eventbytime as $time => $ev) {
                if (empty($wanted[$time])) {
                    self::delete_calendar_event((int) $ev->id);
                }
            }
        } catch (\Throwable $e) {
            debugging('edzsession calendar sync failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /** Delete one calendar event by id (best-effort). */
    private static function delete_calendar_event(int $eventid): void {
        try {
            \calendar_event::load($eventid)->delete();
        } catch (\Throwable $e) {
            debugging('edzsession calendar event delete failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /** Resolve the meeting account for an instance, or null if unusable. */
    private static function resolve_account(\stdClass $edzsession): ?meeting\account {
        if (empty($edzsession->accountid)) {
            return null;
        }
        try {
            $account = account_vault::get((int) $edzsession->accountid);
        } catch (\Throwable $e) {
            return null;
        }
        $provider = provider_manager::get_meeting($edzsession->meetingprovider ?? null);
        return $provider->is_configured($account) ? $account : null;
    }

    private static function bump_cache(int $edzsessionid): void {
        try {
            \cache::make('mod_edzsession', 'occurrences')->delete($edzsessionid);
        } catch (\Throwable $e) {
            // Cache may not be defined during install/upgrade; ignore.
        }
    }
}
