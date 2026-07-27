<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * GDPR privacy provider.
 *
 * Describes what personal data this plugin stores and provides
 * export / deletion routines required by Moodle's Privacy API (MDL-58660).
 *
 * Tables that contain user data:
 *   local_edzaiaxisfront_chat_sessions — one row per chat session (user_id, timestamps)
 *   local_edzaiaxisfront_token_usage   — daily token counts per user
 *   local_edzaiaxisfront_user_limits   — admin-set per-user overrides
 *
 * External service: axis-ai — receives moodle_user_id and chat messages.
 * The axis-ai service is listed as a third-party subprocessor (metadata only).
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for local_edzaiaxisfront.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    // ── Metadata ─────────────────────────────────────────────────────────────

    /**
     * Returns metadata about data stored by this plugin.
     */
    public static function get_metadata(collection $collection): collection {

        // Chat sessions table
        $collection->add_database_table(
            'local_edzaiaxisfront_chat_sessions',
            [
                'userid'          => 'privacy:metadata:chat_sessions:userid',
                'cmid'            => 'privacy:metadata:chat_sessions:cmid',
                'courseid'        => 'privacy:metadata:chat_sessions:courseid',
                'chat_mode'       => 'privacy:metadata:chat_sessions:chat_mode',
                'message_count'   => 'privacy:metadata:chat_sessions:message_count',
                'tokens_used'     => 'privacy:metadata:chat_sessions:tokens_used',
                'timecreated'     => 'privacy:metadata:chat_sessions:timecreated',
            ],
            'privacy:metadata:chat_sessions'
        );

        // Token usage table
        $collection->add_database_table(
            'local_edzaiaxisfront_token_usage',
            [
                'userid'      => 'privacy:metadata:token_usage:userid',
                'day_date'    => 'privacy:metadata:token_usage:day_date',
                'tokens_used' => 'privacy:metadata:token_usage:tokens_used',
                'msg_count'   => 'privacy:metadata:token_usage:msg_count',
            ],
            'privacy:metadata:token_usage'
        );

        // Per-user limit overrides
        $collection->add_database_table(
            'local_edzaiaxisfront_user_limits',
            [
                'userid'                  => 'privacy:metadata:user_limits:userid',
                'chat_session_msg_limit'  => 'privacy:metadata:user_limits:chat_session_msg_limit',
                'chat_daily_msg_limit'    => 'privacy:metadata:user_limits:chat_daily_msg_limit',
                'chat_monthly_msg_limit'  => 'privacy:metadata:user_limits:chat_monthly_msg_limit',
                'token_monthly_limit'     => 'privacy:metadata:user_limits:token_monthly_limit',
                'note'                    => 'privacy:metadata:user_limits:note',
            ],
            'privacy:metadata:user_limits'
        );

        // External: axis-ai service
        $collection->add_external_location_link(
            'axis_ai_service',
            [
                'moodle_user_id' => 'privacy:metadata:axis_ai:moodle_user_id',
                'chat_messages'  => 'privacy:metadata:axis_ai:chat_messages',
                'content_items'  => 'privacy:metadata:axis_ai:content_items',
            ],
            'privacy:metadata:axis_ai'
        );

        return $collection;
    }

    // ── Context discovery ─────────────────────────────────────────────────────

    /**
     * Return all contexts that contain personal data for the given user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Chat sessions are scoped to course contexts
        $contextlist->add_from_sql(
            "SELECT DISTINCT ctx.id
               FROM {local_edzaiaxisfront_chat_sessions} cs
               JOIN {context} ctx ON ctx.instanceid = cs.courseid AND ctx.contextlevel = :courselevel
              WHERE cs.userid = :userid",
            ['courselevel' => CONTEXT_COURSE, 'userid' => $userid]
        );

        // Token usage and user limits — system context
        $sql = "SELECT id FROM {context} WHERE contextlevel = :systemlevel";
        $contextlist->add_from_sql($sql, ['systemlevel' => CONTEXT_SYSTEM]);

        return $contextlist;
    }

    /**
     * Return all users in the given context who have data stored by this plugin.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel === CONTEXT_COURSE) {
            $userlist->add_from_sql(
                'userid',
                "SELECT DISTINCT userid
                   FROM {local_edzaiaxisfront_chat_sessions}
                  WHERE courseid = :courseid",
                ['courseid' => $context->instanceid]
            );
        }

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $userlist->add_from_sql(
                'userid',
                "SELECT DISTINCT userid FROM {local_edzaiaxisfront_token_usage}",
                []
            );
            $userlist->add_from_sql(
                'userid',
                "SELECT DISTINCT userid FROM {local_edzaiaxisfront_user_limits}",
                []
            );
        }
    }

    // ── Export ────────────────────────────────────────────────────────────────

    /**
     * Export all personal data for the given approved contextlist.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {

            if ($context->contextlevel === CONTEXT_COURSE) {
                // Export chat sessions for this course
                $sessions = $DB->get_records(
                    'local_edzaiaxisfront_chat_sessions',
                    ['userid' => $userid, 'courseid' => $context->instanceid]
                );
                foreach ($sessions as $session) {
                    $session->timecreated  = transform::datetime($session->timecreated);
                    $session->timemodified = transform::datetime($session->timemodified);
                }
                if ($sessions) {
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'local_edzaiaxisfront'), 'chat_sessions'],
                        (object)['sessions' => array_values($sessions)]
                    );
                }
            }

            if ($context->contextlevel === CONTEXT_SYSTEM) {
                // Export token usage
                $usage = $DB->get_records('local_edzaiaxisfront_token_usage', ['userid' => $userid]);
                if ($usage) {
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'local_edzaiaxisfront'), 'token_usage'],
                        (object)['usage' => array_values($usage)]
                    );
                }

                // Export user limit override
                $limits = $DB->get_record('local_edzaiaxisfront_user_limits', ['userid' => $userid]);
                if ($limits) {
                    $limits->timecreated  = transform::datetime($limits->timecreated);
                    $limits->timemodified = transform::datetime($limits->timemodified);
                    writer::with_context($context)->export_data(
                        [get_string('pluginname', 'local_edzaiaxisfront'), 'user_limits'],
                        $limits
                    );
                }
            }
        }
    }

    // ── Deletion ──────────────────────────────────────────────────────────────

    /**
     * Delete all data for all users in a context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel === CONTEXT_COURSE) {
            $DB->delete_records('local_edzaiaxisfront_chat_sessions', ['courseid' => $context->instanceid]);
        }

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records('local_edzaiaxisfront_token_usage', []);
            $DB->delete_records('local_edzaiaxisfront_user_limits', []);
        }
    }

    /**
     * Delete all data for the given approved contextlist for a single user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_COURSE) {
                $DB->delete_records('local_edzaiaxisfront_chat_sessions', [
                    'userid'   => $userid,
                    'courseid' => $context->instanceid,
                ]);
            }
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                $DB->delete_records('local_edzaiaxisfront_token_usage', ['userid' => $userid]);
                $DB->delete_records('local_edzaiaxisfront_user_limits', ['userid' => $userid]);
            }
        }
    }

    /**
     * Delete multiple users' data within a single context.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        if ($context->contextlevel === CONTEXT_COURSE) {
            $params['courseid'] = $context->instanceid;
            $DB->delete_records_select(
                'local_edzaiaxisfront_chat_sessions',
                "userid {$insql} AND courseid = :courseid",
                $params
            );
        }

        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records_select('local_edzaiaxisfront_token_usage', "userid {$insql}", $params);
            $DB->delete_records_select('local_edzaiaxisfront_user_limits', "userid {$insql}", $params);
        }
    }
}
