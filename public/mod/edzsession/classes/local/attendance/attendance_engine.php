<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\attendance;

use mod_edzsession\local\account_vault;
use mod_edzsession\local\provider_manager;

/**
 * Pulls participant reports, dedupes segments, reconciles to Moodle users, and
 * writes attendance. Teacher manual overrides (matchstate='manual') are always
 * preserved across re-polls — automatic and unmatched rows are rebuilt.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance_engine {

    /**
     * Poll every occurrence that has ended recently and not yet been polled.
     * Called by the scheduled task.
     *
     * @param int $lookbackdays only consider occurrences ended within N days
     * @return int number of occurrences processed
     */
    public static function poll_finished_occurrences(int $lookbackdays = 7): int {
        global $DB;
        $now = time();
        $since = $now - ($lookbackdays * DAYSECS);

        // Ended = starttime + scheduled duration < now; not already reconciled.
        // We no longer require a stored remoteuuid — poll_occurrence resolves it
        // from Zoom directly, so attendance works without webhooks.
        $sql = "SELECT o.*, e.accountid, e.meetingprovider, e.remotemeetingid AS parentmeetingid
                  FROM {edzsession_occurrence} o
                  JOIN {edzsession} e ON e.id = o.edzsessionid
                 WHERE o.starttime > :since
                   AND (o.starttime + (o.duration * 60)) < :now
                   AND o.status <> :done
                   AND e.accountid IS NOT NULL
                   AND e.remotemeetingid IS NOT NULL";
        $rows = $DB->get_records_sql($sql, ['since' => $since, 'now' => $now, 'done' => 'attendance_done']);

        $processed = 0;
        foreach ($rows as $occ) {
            try {
                self::poll_occurrence($occ);
                $processed++;
            } catch (\Throwable $e) {
                mtrace('edzsession attendance poll failed for occurrence ' . $occ->id . ': ' . $e->getMessage());
            }
        }
        return $processed;
    }

    /**
     * Fetch + store raw participants for one occurrence, then reconcile.
     *
     * @param \stdClass $occ occurrence row (with accountid + meetingprovider)
     */
    public static function poll_occurrence(\stdClass $occ): void {
        global $DB;

        $account = account_vault::get((int) $occ->accountid);
        $provider = provider_manager::get_meeting($occ->meetingprovider);

        // Resolve the meeting id (occurrence row may predate the column being set).
        $meetingid = (string) ($occ->remotemeetingid ?? '');
        if ($meetingid === '') {
            $meetingid = (string) ($occ->parentmeetingid
                ?? $DB->get_field('edzsession', 'remotemeetingid', ['id' => $occ->edzsessionid]));
        }

        // Resolve the real per-occurrence UUID if we don't already have one
        // (this is what makes attendance work without a webhook).
        $uuid = $occ->remoteuuid;
        if (empty($uuid)) {
            $probe = new \mod_edzsession\local\meeting\remote_meeting($meetingid, '', null);
            $uuid = $provider->resolve_occurrence_uuid($probe, (int) $occ->starttime, $account);
            if (!empty($uuid)) {
                $DB->set_field('edzsession_occurrence', 'remoteuuid', $uuid, ['id' => $occ->id]);
                $occ->remoteuuid = $uuid;
            }
        }
        if (empty($uuid)) {
            throw new \moodle_exception('nomeetinginstance', 'mod_edzsession');
        }

        $meeting = new \mod_edzsession\local\meeting\remote_meeting($meetingid, '', $uuid);
        $participants = $provider->fetch_participants($meeting, $account);

        // Replace raw segments for this occurrence (idempotent re-poll).
        $DB->delete_records('edzsession_attendance_raw', ['occurrenceid' => $occ->id]);
        $maxleave = 0;
        $minjoin = PHP_INT_MAX;
        foreach ($participants as $p) {
            $DB->insert_record('edzsession_attendance_raw', (object) [
                'occurrenceid' => $occ->id,
                'name' => $p->name,
                'email' => $p->email,
                'registrantid' => $p->registrantid,
                'jointime' => $p->jointime,
                'leavetime' => $p->leavetime,
            ]);
            $maxleave = max($maxleave, $p->leavetime);
            $minjoin = min($minjoin, $p->jointime);
        }

        // Record actual duration (seconds) from the participant span.
        if ($maxleave > 0 && $minjoin < PHP_INT_MAX) {
            $DB->set_field('edzsession_occurrence', 'actualduration', max(0, $maxleave - $minjoin),
                ['id' => $occ->id]);
            $occ->actualduration = max(0, $maxleave - $minjoin);
        }

        self::reconcile_occurrence($occ);

        $DB->set_field('edzsession_occurrence', 'status', 'attendance_done', ['id' => $occ->id]);
        self::recompute_completion((int) $occ->edzsessionid);
    }

    /**
     * Reconcile raw segments into per-user attendance rows.
     *
     * @param \stdClass $occ occurrence row
     */
    public static function reconcile_occurrence(\stdClass $occ): void {
        global $DB;

        $basisconfig = get_config('mod_edzsession', 'attendancebasis') ?: 'meeting_duration';
        $strategy = get_config('mod_edzsession', 'matchstrategy') ?: 'email';

        // Basis seconds.
        $scheduledsecs = max(1, (int) $occ->duration * 60);
        $actualsecs = (int) ($occ->actualduration ?? 0);
        $basissecs = ($basisconfig === 'meeting_duration' && $actualsecs > 0) ? $actualsecs : $scheduledsecs;

        // Enrolled users of the course (for matching).
        $cm = get_coursemodule_from_instance('edzsession', $occ->edzsessionid);
        $emailmap = [];
        $namemap = [];
        if ($cm) {
            $context = \context_module::instance($cm->id);
            foreach (get_enrolled_users($context) as $u) {
                $emailmap[strtolower(trim($u->email))] = $u->id;
                $namemap[strtolower(trim(fullname($u)))] = $u->id;
            }
        }

        // Group raw segments by participant identity, then sum the union of their
        // time intervals (merging overlaps) so a rejoin / two devices / breakout
        // transition can't double-count toward attended time.
        $raw = $DB->get_records('edzsession_attendance_raw', ['occurrenceid' => $occ->id]);
        $groups = [];
        $intervals = [];
        foreach ($raw as $r) {
            $key = self::group_key($r);
            if (!isset($groups[$key])) {
                $groups[$key] = ['name' => $r->name, 'email' => $r->email,
                    'registrantid' => $r->registrantid, 'seconds' => 0];
                $intervals[$key] = [];
            }
            $intervals[$key][] = [(int) $r->jointime, (int) $r->leavetime];
        }
        foreach ($groups as $key => $unused) {
            $groups[$key]['seconds'] = self::merge_seconds($intervals[$key]);
        }

        // Manual overrides to preserve: keep existing manual rows, keyed by email
        // AND by name so a manual row without an email is still not duplicated.
        $manual = $DB->get_records('edzsession_attendance',
            ['occurrenceid' => $occ->id, 'matchstate' => 'manual']);
        $manualkeys = [];
        foreach ($manual as $m) {
            if (!empty($m->matchedemail)) {
                $manualkeys['e:' . strtolower(trim((string) $m->matchedemail))] = true;
            }
            if (!empty($m->matchedname)) {
                $manualkeys['n:' . strtolower(trim((string) $m->matchedname))] = true;
            }
        }

        // Rebuild auto + unmatched rows (matchstate is a CHAR column: compare directly).
        $DB->delete_records_select('edzsession_attendance',
            'occurrenceid = :oid AND matchstate <> :manual',
            ['oid' => $occ->id, 'manual' => 'manual']);

        $now = time();
        foreach ($groups as $g) {
            $email = strtolower(trim((string) $g['email']));
            $name = strtolower(trim((string) $g['name']));
            if (($email !== '' && isset($manualkeys['e:' . $email]))
                    || ($name !== '' && isset($manualkeys['n:' . $name]))) {
                continue; // A teacher already mapped this participant; leave it.
            }

            $userid = self::match_user($g, $strategy, $emailmap, $namemap);
            $percent = round(min(100, ($g['seconds'] / $basissecs) * 100), 2);

            $DB->insert_record('edzsession_attendance', (object) [
                'occurrenceid' => $occ->id,
                'userid' => $userid ?: 0,
                'matchedname' => $g['name'],
                'matchedemail' => $g['email'],
                'joinseconds' => $g['seconds'],
                'attendedpercent' => $percent,
                'matchstate' => $userid ? 'auto' : 'unmatched',
                'timemodified' => $now,
            ]);
        }
    }

    /** Assign an unmatched attendance row to a user (teacher reconcile action). */
    public static function assign_user(int $attendanceid, int $userid): void {
        global $DB;
        $row = $DB->get_record('edzsession_attendance', ['id' => $attendanceid], '*', MUST_EXIST);
        $row->userid = $userid;
        $row->matchstate = 'manual';
        $row->timemodified = time();
        $DB->update_record('edzsession_attendance', $row);

        $occ = $DB->get_record('edzsession_occurrence', ['id' => $row->occurrenceid], 'edzsessionid');
        if ($occ) {
            self::recompute_completion((int) $occ->edzsessionid);
        }
    }

    /** Recompute completion for every enrolled user of an activity. */
    public static function recompute_completion(int $edzsessionid): void {
        global $DB;
        $cm = get_coursemodule_from_instance('edzsession', $edzsessionid);
        if (!$cm) {
            return;
        }
        $course = $DB->get_record('course', ['id' => $cm->course]);
        $completion = new \completion_info($course);
        if (!$completion->is_enabled($cm)) {
            return;
        }
        $context = \context_module::instance($cm->id);
        foreach (get_enrolled_users($context) as $u) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $u->id);
        }
    }

    // ---- Matching helpers -------------------------------------------------

    /**
     * Total seconds covered by a set of [start,end] intervals, merging overlaps.
     *
     * @param array $intervals list of [start, end]
     * @return int
     */
    private static function merge_seconds(array $intervals): int {
        if (empty($intervals)) {
            return 0;
        }
        usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);
        $total = 0;
        [$curstart, $curend] = $intervals[0];
        foreach (array_slice($intervals, 1) as [$s, $e]) {
            if ($s <= $curend) {
                $curend = max($curend, $e); // Overlap — extend the current span.
            } else {
                $total += max(0, $curend - $curstart);
                $curstart = $s;
                $curend = $e;
            }
        }
        $total += max(0, $curend - $curstart);
        return $total;
    }

    private static function group_key(\stdClass $r): string {
        if (!empty($r->email)) {
            return 'e:' . strtolower(trim($r->email));
        }
        if (!empty($r->registrantid)) {
            return 'r:' . $r->registrantid;
        }
        return 'n:' . strtolower(trim((string) $r->name));
    }

    private static function match_user(array $g, string $strategy, array $emailmap, array $namemap): ?int {
        // Preferred strategy first, then safe fallbacks (email is most reliable).
        $order = array_unique([$strategy, 'email', 'name']);
        foreach ($order as $s) {
            if ($s === 'email' && !empty($g['email'])) {
                $k = strtolower(trim($g['email']));
                if (isset($emailmap[$k])) {
                    return $emailmap[$k];
                }
            } else if ($s === 'name' && !empty($g['name'])) {
                $k = strtolower(trim($g['name']));
                if (isset($namemap[$k])) {
                    return $namemap[$k];
                }
            }
            // 'registrantid' has no local map yet (P4 registration) -> skip.
        }
        return null;
    }
}
