<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Live monitor aggregator — builds the list of currently active proctoring
 * sessions for the real-time proctor dashboard (Option A snapshot wall).
 *
 * Reads existing tables only — no schema change. Designed to run on every
 * dashboard poll, so all per-session lookups are done as grouped queries
 * (no N+1).
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_edproctoring\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds active-session tiles for the live monitor wall.
 */
class live_monitor {

    /** @var int Default dashboard auto-refresh interval (seconds). */
    const DEFAULT_REFRESH = 9;

    /** @var int Minimum allowed refresh interval (seconds). */
    const MIN_REFRESH = 3;

    /** @var int Fallback capture interval if a quiz has no settings row (seconds). */
    const DEFAULT_CAPTURE_INTERVAL = 30;

    /** @var int Session is "connection lost" if no snapshot within interval x this. */
    const STALE_MULTIPLIER = 3;

    /**
     * Configured dashboard refresh interval (seconds), clamped to a sane minimum.
     *
     * @return int
     */
    public static function get_refresh_interval(): int {
        $value = (int) get_config('quizaccess_edproctoring', 'live_refresh');
        return $value >= self::MIN_REFRESH ? $value : self::DEFAULT_REFRESH;
    }

    /**
     * Build the list of active proctoring sessions for the live wall.
     *
     * @param int $quizid Restrict to one quiz (0 = all quizzes, site-wide).
     * @return array list of tile arrays, sorted suspicious-first
     */
    public static function get_active_sessions(int $quizid = 0): array {
        global $DB;

        $params = ['status' => 'active'];
        $where  = 's.status = :status';
        if ($quizid > 0) {
            $where .= ' AND s.quizid = :quizid';
            $params['quizid'] = $quizid;
        }

        $sql = "SELECT s.id, s.quizid, s.cmid, s.userid, s.attemptid, s.started_at,
                       s.total_violations, s.critical_violations, s.warning_violations,
                       u.firstname, u.lastname, u.email,
                       q.name AS quizname,
                       c.shortname AS courseshort
                  FROM {quizaccess_edproctoring_session} s
                  JOIN {user} u ON u.id = s.userid
                  JOIN {quiz} q ON q.id = s.quizid
                  JOIN {course} c ON c.id = q.course
                 WHERE $where
              ORDER BY s.started_at ASC";

        $sessions = $DB->get_records_sql($sql, $params);
        if (empty($sessions)) {
            return [];
        }

        $sessionids = array_keys($sessions);
        list($insql, $inparams) = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED, 'sid');

        $latestsnaps = self::get_latest_snaps($sessionids);
        $deductions  = self::get_deductions($insql, $inparams);
        $latestviol  = self::get_latest_violations($insql, $inparams);
        $intervals   = self::get_capture_intervals($sessions);

        $now   = time();
        $tiles = [];

        foreach ($sessions as $s) {
            $sid      = (int) $s->id;
            $interval = $intervals[(int) $s->quizid] ?? self::DEFAULT_CAPTURE_INTERVAL;

            $thumburl = '';
            $lastseen = -1;
            if (isset($latestsnaps[$sid])) {
                $snap = $latestsnaps[$sid];
                $url  = image_store::get_snapshot_url($snap->pathnamehash);
                if ($url) {
                    $thumburl = $url->out(false);
                }
                $lastseen = max(0, $now - (int) $snap->timecaptured);
            }

            $stale = ($lastseen < 0) || ($lastseen > $interval * self::STALE_MULTIPLIER);

            $deduct = $deductions[$sid] ?? 0.0;
            $trust  = max(0.0, round(100.0 - $deduct, 1));
            $band   = trust_score::get_band($trust);

            $lv = $latestviol[$sid] ?? null;

            $tiles[] = [
                'sessionid'              => $sid,
                'userid'                 => (int) $s->userid,
                'fullname'               => fullname($s),
                'quizid'                 => (int) $s->quizid,
                'cmid'                   => (int) $s->cmid,
                'quizname'               => (string) $s->quizname,
                'courseshort'            => (string) $s->courseshort,
                'attemptid'              => (int) $s->attemptid,
                'started_at'             => (int) $s->started_at,
                'elapsed'                => max(0, $now - (int) $s->started_at),
                'thumburl'               => $thumburl,
                'lastseen'               => $lastseen,
                'stale'                  => $stale ? 1 : 0,
                'total_violations'       => (int) $s->total_violations,
                'critical_violations'    => (int) $s->critical_violations,
                'warning_violations'     => (int) $s->warning_violations,
                'trust'                  => (float) $trust,
                'band'                   => $band,
                'lastviolation'          => $lv ? (string) $lv->violation_type : '',
                'lastviolation_severity' => $lv ? (string) $lv->severity : '',
            ];
        }

        usort($tiles, [self::class, 'sort_suspicious_first']);

        return $tiles;
    }

    /**
     * Sort comparator: most critical first, then lowest trust, then most violations.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected static function sort_suspicious_first(array $a, array $b): int {
        if ($a['critical_violations'] !== $b['critical_violations']) {
            return $b['critical_violations'] <=> $a['critical_violations'];
        }
        if ($a['trust'] !== $b['trust']) {
            return $a['trust'] <=> $b['trust'];
        }
        return $b['total_violations'] <=> $a['total_violations'];
    }

    /**
     * Latest snapshot row per session, in a single portable query.
     *
     * @param array $sessionids
     * @return array sessionid => snap record (id, sessionid, pathnamehash, timecaptured)
     */
    protected static function get_latest_snaps(array $sessionids): array {
        global $DB;

        if (empty($sessionids)) {
            return [];
        }
        list($insql, $inparams) = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED, 'snp');

        $sql = "SELECT sn.id, sn.sessionid, sn.pathnamehash, sn.timecaptured
                  FROM {quizaccess_edproctoring_snap} sn
                  JOIN (SELECT sessionid, MAX(timecaptured) AS mt
                          FROM {quizaccess_edproctoring_snap}
                         WHERE sessionid $insql
                      GROUP BY sessionid) m
                    ON m.sessionid = sn.sessionid AND m.mt = sn.timecaptured";

        $out = [];
        foreach ($DB->get_records_sql($sql, $inparams) as $row) {
            // Tie on identical timecaptured: last row wins, which is fine.
            $out[(int) $row->sessionid] = $row;
        }
        return $out;
    }

    /**
     * Sum of non-dismissed violation weights per session (provisional trust deduction).
     *
     * @param string $insql  IN() fragment built with prefix 'sid'
     * @param array  $inparams matching params
     * @return array sessionid => total deduction (float)
     */
    protected static function get_deductions(string $insql, array $inparams): array {
        global $DB;

        $sql = "SELECT sessionid, SUM(severity_weight) AS total
                  FROM {quizaccess_edproctoring_violation}
                 WHERE dismissed = 0 AND sessionid $insql
              GROUP BY sessionid";

        $out = [];
        foreach ($DB->get_records_sql($sql, $inparams) as $row) {
            $out[(int) $row->sessionid] = (float) $row->total;
        }
        return $out;
    }

    /**
     * Most recent violation per session (for the tile label).
     *
     * @param string $insql  IN() fragment built with prefix 'sid'
     * @param array  $inparams matching params
     * @return array sessionid => violation record
     */
    protected static function get_latest_violations(string $insql, array $inparams): array {
        global $DB;

        $sql = "SELECT v.id, v.sessionid, v.violation_type, v.severity, v.timecreated
                  FROM {quizaccess_edproctoring_violation} v
                  JOIN (SELECT sessionid, MAX(timecreated) AS mt
                          FROM {quizaccess_edproctoring_violation}
                         WHERE sessionid $insql
                      GROUP BY sessionid) m
                    ON m.sessionid = v.sessionid AND m.mt = v.timecreated";

        $out = [];
        foreach ($DB->get_records_sql($sql, $inparams) as $row) {
            $out[(int) $row->sessionid] = $row;
        }
        return $out;
    }

    /**
     * Per-quiz capture interval (seconds) for the quizzes in this session set.
     *
     * @param array $sessions session records (need ->quizid)
     * @return array quizid => capture_interval
     */
    protected static function get_capture_intervals(array $sessions): array {
        global $DB;

        $quizids = [];
        foreach ($sessions as $s) {
            $quizids[(int) $s->quizid] = true;
        }
        if (empty($quizids)) {
            return [];
        }
        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($quizids), SQL_PARAMS_NAMED, 'qz');

        $sql = "SELECT quizid, capture_interval
                  FROM {quizaccess_edproctoring}
                 WHERE quizid $insql";

        $out = [];
        foreach ($DB->get_records_sql($sql, $inparams) as $row) {
            $out[(int) $row->quizid] = (int) $row->capture_interval;
        }
        return $out;
    }
}
