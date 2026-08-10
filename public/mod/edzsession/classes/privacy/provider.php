<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider for mod_edzsession.
 *
 * Stores per-user attendance (userid, matched name/email, join seconds,
 * attended %) — so this is a full provider, not a null provider.
 *
 * P1 declares the metadata + implements the discovery/delete hooks against the
 * attendance tables. Full export payload formatting is finalised in P3.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('edzsession_attendance', [
            'userid' => 'privacy:metadata:attendance:userid',
            'matchedname' => 'privacy:metadata:attendance:matchedname',
            'matchedemail' => 'privacy:metadata:attendance:matchedemail',
            'joinseconds' => 'privacy:metadata:attendance:joinseconds',
            'attendedpercent' => 'privacy:metadata:attendance:attendedpercent',
        ], 'privacy:metadata:attendance');

        // Raw participant segments captured from the meeting report before
        // reconciliation (name/email/registrant id). Not keyed by Moodle user,
        // so purged at the activity-context level.
        $collection->add_database_table('edzsession_attendance_raw', [
            'name' => 'privacy:metadata:attendanceraw:name',
            'email' => 'privacy:metadata:attendanceraw:email',
            'registrantid' => 'privacy:metadata:attendanceraw:registrantid',
        ], 'privacy:metadata:attendanceraw');

        // Data sent to the external meeting provider to obtain attendance.
        $collection->add_external_location_link('meetingprovider', [
            'email' => 'privacy:metadata:meetingprovider:email',
            'name' => 'privacy:metadata:meetingprovider:name',
        ], 'privacy:metadata:meetingprovider');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :modlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {edzsession} e ON e.id = cm.instance
                  JOIN {edzsession_occurrence} o ON o.edzsessionid = e.id
                  JOIN {edzsession_attendance} a ON a.occurrenceid = o.id
                 WHERE a.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'modlevel' => CONTEXT_MODULE,
            'modname' => 'edzsession',
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = "SELECT a.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {edzsession} e ON e.id = cm.instance
                  JOIN {edzsession_occurrence} o ON o.edzsessionid = e.id
                  JOIN {edzsession_attendance} a ON a.occurrenceid = o.id
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, [
            'modname' => 'edzsession',
            'cmid' => $context->instanceid,
        ]);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('edzsession', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $sql = "SELECT a.id, a.matchedname, a.matchedemail, a.joinseconds,
                           a.attendedpercent, a.matchstate, o.starttime
                      FROM {edzsession_attendance} a
                      JOIN {edzsession_occurrence} o ON o.id = a.occurrenceid
                     WHERE o.edzsessionid = :eid AND a.userid = :uid
                  ORDER BY o.starttime ASC";
            $rows = $DB->get_records_sql($sql, ['eid' => $cm->instance, 'uid' => $userid]);
            if (!$rows) {
                continue;
            }
            $data = [];
            foreach ($rows as $r) {
                $data[] = (object) [
                    'session_time' => \core_privacy\local\request\transform::datetime($r->starttime),
                    'reported_name' => $r->matchedname,
                    'reported_email' => $r->matchedemail,
                    'minutes_attended' => (int) floor($r->joinseconds / 60),
                    'attended_percent' => $r->attendedpercent,
                    'match_state' => $r->matchstate,
                ];
            }
            \core_privacy\local\request\writer::with_context($context)->export_data(
                [get_string('privacy:attendancepath', 'mod_edzsession')],
                (object) ['attendance' => $data]);
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('edzsession', $context->instanceid);
        if (!$cm) {
            return;
        }
        $occurrenceids = $DB->get_fieldset_select('edzsession_occurrence', 'id',
            'edzsessionid = :eid', ['eid' => $cm->instance]);
        if ($occurrenceids) {
            list($insql, $params) = $DB->get_in_or_equal($occurrenceids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('edzsession_attendance', "occurrenceid $insql", $params);
            $DB->delete_records_select('edzsession_attendance_raw', "occurrenceid $insql", $params);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('edzsession', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $occurrenceids = $DB->get_fieldset_select('edzsession_occurrence', 'id',
                'edzsessionid = :eid', ['eid' => $cm->instance]);
            if ($occurrenceids) {
                list($insql, $params) = $DB->get_in_or_equal($occurrenceids, SQL_PARAMS_NAMED);
                $params['userid'] = $userid;
                $DB->delete_records_select('edzsession_attendance',
                    "occurrenceid $insql AND userid = :userid", $params);
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('edzsession', $context->instanceid);
        if (!$cm) {
            return;
        }
        $occurrenceids = $DB->get_fieldset_select('edzsession_occurrence', 'id',
            'edzsessionid = :eid', ['eid' => $cm->instance]);
        if (!$occurrenceids) {
            return;
        }
        list($occsql, $params) = $DB->get_in_or_equal($occurrenceids, SQL_PARAMS_NAMED);
        list($usersql, $userparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge($params, $userparams);
        $DB->delete_records_select('edzsession_attendance',
            "occurrenceid $occsql AND userid $usersql", $params);
    }
}
