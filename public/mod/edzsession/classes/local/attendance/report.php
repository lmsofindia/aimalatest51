<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\attendance;

/**
 * Shared data access for the attendance overview, detail page and exports, so
 * the on-screen table, the CSV and the PDF all present identical numbers.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {

    /**
     * Session-level metadata for headers (title block on screen / CSV / PDF).
     *
     * @param \stdClass $cm course module
     * @param \stdClass $course
     * @param \stdClass $edzsession
     * @return \stdClass {sessionname, coursename, host, provider}
     */
    public static function session_meta(\stdClass $cm, \stdClass $course, \stdClass $edzsession): \stdClass {
        global $DB;
        $host = '';
        if (!empty($edzsession->accountid)) {
            $host = (string) $DB->get_field('edzsession_account', 'name',
                ['id' => $edzsession->accountid]);
        }
        return (object) [
            'sessionname' => format_string($edzsession->name),
            'coursename' => format_string($course->fullname),
            'host' => $host !== '' ? $host : get_string('host_unknown', 'mod_edzsession'),
            'provider' => $edzsession->meetingprovider,
        ];
    }

    /**
     * Per-occurrence summary numbers.
     *
     * @param int $occurrenceid
     * @return \stdClass {present, total, avgpercent}
     */
    public static function occurrence_summary(int $occurrenceid): \stdClass {
        global $DB;
        $sql = "SELECT COUNT(id) AS total,
                       SUM(CASE WHEN joinseconds > 0 THEN 1 ELSE 0 END) AS present,
                       COALESCE(AVG(attendedpercent), 0) AS avgpercent
                  FROM {edzsession_attendance}
                 WHERE occurrenceid = :oid";
        $r = $DB->get_record_sql($sql, ['oid' => $occurrenceid]);
        return (object) [
            'total' => (int) ($r->total ?? 0),
            'present' => (int) ($r->present ?? 0),
            'avgpercent' => round((float) ($r->avgpercent ?? 0), 1),
        ];
    }

    /** Count attendance rows for an occurrence (for pagination). */
    public static function count(int $occurrenceid): int {
        global $DB;
        return $DB->count_records('edzsession_attendance', ['occurrenceid' => $occurrenceid]);
    }

    /**
     * Attendance rows for display/export, newest-highest attendance first.
     * Resolves the Moodle user name once here.
     *
     * @param int $occurrenceid
     * @param int $limitfrom
     * @param int $limitnum 0 = all (used by export)
     * @return array list of row objects
     */
    public static function rows(int $occurrenceid, int $limitfrom = 0, int $limitnum = 0): array {
        global $DB;
        $records = $DB->get_records('edzsession_attendance', ['occurrenceid' => $occurrenceid],
            'attendedpercent DESC, matchedname ASC', '*', $limitfrom, $limitnum);

        // Batch-load the matched users to avoid a query per row.
        $userids = [];
        foreach ($records as $r) {
            if ($r->userid) {
                $userids[$r->userid] = $r->userid;
            }
        }
        $users = $userids ? $DB->get_records_list('user', 'id', $userids) : [];

        $rows = [];
        foreach ($records as $r) {
            $username = '';
            if ($r->userid && isset($users[$r->userid])) {
                $username = fullname($users[$r->userid]);
            }
            $rows[] = (object) [
                'id' => (int) $r->id,
                'userid' => (int) $r->userid,
                'participant' => (string) $r->matchedname,
                'email' => (string) $r->matchedemail,
                'username' => $username,
                'minutes' => (int) floor($r->joinseconds / 60),
                'percent' => round((float) $r->attendedpercent, 1),
                'matchstate' => (string) $r->matchstate,
            ];
        }
        return $rows;
    }
}
