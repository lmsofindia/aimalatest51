<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use quizaccess_edproctoring\helper\image_store;

/**
 * GDPR privacy provider for quizaccess_edproctoring.
 *
 * Declares: sessions, snapshots, violations, base images.
 * Supports: export, delete per user, delete per context.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin_provider,
    \core_privacy\local\request\core_userlist_provider {

    // -------------------------------------------------------------------------
    // Metadata
    // -------------------------------------------------------------------------

    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table('quizaccess_edproctoring_session', [
            'userid'       => 'privacy:metadata:sessions:userid',
            'consent_time' => 'privacy:metadata:sessions:userid',
            'trust_score'  => 'privacy:metadata:sessions:userid',
        ], 'privacy:metadata:sessions');

        $collection->add_database_table('quizaccess_edproctoring_snap', [
            'userid'        => 'privacy:metadata:snaps:userid',
            'timecaptured'  => 'privacy:metadata:snaps:userid',
            'pathnamehash'  => 'privacy:metadata:snaps:userid',
        ], 'privacy:metadata:snaps');

        $collection->add_database_table('quizaccess_edproctoring_violation', [
            'userid'         => 'privacy:metadata:violations:userid',
            'violation_type' => 'privacy:metadata:violations:userid',
            'severity'       => 'privacy:metadata:violations:userid',
        ], 'privacy:metadata:violations');

        $collection->add_database_table('quizaccess_edproctoring_baseimg', [
            'userid'     => 'privacy:metadata:baseimages:userid',
            'image_type' => 'privacy:metadata:baseimages:userid',
        ], 'privacy:metadata:baseimages');

        return $collection;
    }

    // -------------------------------------------------------------------------
    // Contexts for user
    // -------------------------------------------------------------------------

    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        // Module contexts from sessions.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {quizaccess_edproctoring_session} s ON s.cmid = ctx.instanceid
                 WHERE ctx.contextlevel = :ctxmod
                   AND s.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'ctxmod' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    // -------------------------------------------------------------------------
    // User list in context
    // -------------------------------------------------------------------------

    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $sql = 'SELECT userid FROM {quizaccess_edproctoring_session} WHERE cmid = ?';
        $userlist->add_from_sql('userid', $sql, [$context->instanceid]);
    }

    // -------------------------------------------------------------------------
    // Export user data
    // -------------------------------------------------------------------------

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $sessions = $DB->get_records('quizaccess_edproctoring_session',
                ['userid' => $userid, 'cmid' => $context->instanceid]);

            foreach ($sessions as $session) {
                $data = [
                    'session'    => $session,
                    'violations' => $DB->get_records('quizaccess_edproctoring_violation',
                        ['userid' => $userid, 'sessionid' => $session->id]),
                    'snapshots'  => $DB->get_records('quizaccess_edproctoring_snap',
                        ['userid' => $userid, 'sessionid' => $session->id],
                        '', 'id, capture_type, timecaptured, face_detected, is_violation'),
                ];

                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'quizaccess_edproctoring'), 'session_' . $session->id],
                    (object) $data
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // Delete data for all users in context
    // -------------------------------------------------------------------------

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $sessions = $DB->get_records('quizaccess_edproctoring_session',
            ['cmid' => $context->instanceid], '', 'id, userid');

        foreach ($sessions as $session) {
            image_store::delete_session_files($context->id, $session->id);
        }

        $sessionids = array_keys($sessions);
        if (!empty($sessionids)) {
            list($in, $params) = $DB->get_in_or_equal($sessionids);
            $DB->delete_records_select('quizaccess_edproctoring_snap',      "sessionid $in", $params);
            $DB->delete_records_select('quizaccess_edproctoring_violation',  "sessionid $in", $params);
            $DB->delete_records_select('quizaccess_edproctoring_session',    "id $in",        $params);
        }
    }

    // -------------------------------------------------------------------------
    // Delete data for one user
    // -------------------------------------------------------------------------

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        // Delete all files first.
        image_store::delete_user_files($userid);

        // Delete DB records.
        $sessions = $DB->get_records('quizaccess_edproctoring_session', ['userid' => $userid], '', 'id');
        if (!empty($sessions)) {
            $sessionids = array_keys($sessions);
            list($in, $params) = $DB->get_in_or_equal($sessionids);
            $DB->delete_records_select('quizaccess_edproctoring_snap',     "sessionid $in", $params);
            $DB->delete_records_select('quizaccess_edproctoring_violation', "sessionid $in", $params);
        }
        $DB->delete_records('quizaccess_edproctoring_session',  ['userid' => $userid]);
        $DB->delete_records('quizaccess_edproctoring_baseimg',  ['userid' => $userid]);
    }

    // -------------------------------------------------------------------------
    // Delete data for userlist
    // -------------------------------------------------------------------------

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        foreach ($userids as $userid) {
            image_store::delete_user_files($userid);
            $DB->delete_records('quizaccess_edproctoring_session',  ['userid' => $userid]);
            $DB->delete_records('quizaccess_edproctoring_baseimg',  ['userid' => $userid]);
        }

        list($in, $params) = $DB->get_in_or_equal($userids);
        $DB->delete_records_select('quizaccess_edproctoring_snap',      "userid $in", $params);
        $DB->delete_records_select('quizaccess_edproctoring_violation',  "userid $in", $params);
    }
}
