<?php
/**
 * Privacy provider for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

namespace mod_edztrackvideo\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\helper;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'edztrackvideo_progress',
            [
                'userid' => 'privacy:metadata:edztrackvideo_progress:userid',
                'edztrackvideoid' => 'privacy:metadata:edztrackvideo_progress:edztrackvideoid',
                'maxwatched' => 'privacy:metadata:edztrackvideo_progress:maxwatched',
                'last_position' => 'privacy:metadata:edztrackvideo_progress:last_position',
                'duration' => 'privacy:metadata:edztrackvideo_progress:duration',
                'completed' => 'privacy:metadata:edztrackvideo_progress:completed',
                'last_viewed_on' => 'privacy:metadata:edztrackvideo_progress:last_viewed_on',
            ],
            'privacy:metadata:edztrackvideo_progress'
        );
        return $collection;
    }

    /**
     * Contexts containing user data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {edztrackvideo_progress} p
                  JOIN {edztrackvideo} e ON e.id = p.edztrackvideoid
                  JOIN {course_modules} cm ON cm.instance = e.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :modlevel
                 WHERE p.userid = :userid";
        $params = [
            'modname' => 'edztrackvideo',
            'modlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Users within a given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT p.userid
                  FROM {edztrackvideo_progress} p
                  JOIN {edztrackvideo} e ON e.id = p.edztrackvideoid
                  JOIN {course_modules} cm ON cm.instance = e.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";
        $params = [
            'modname' => 'edztrackvideo',
            'cmid' => $context->instanceid,
        ];
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export user data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('edztrackvideo', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $rec = $DB->get_record('edztrackvideo_progress', [
                'edztrackvideoid' => $cm->instance,
                'userid' => $user->id,
            ]);
            if (!$rec) {
                continue;
            }

            $data = (object)[
                'maxwatched' => $rec->maxwatched,
                'last_position' => $rec->last_position,
                'duration' => $rec->duration,
                'completed' => $rec->completed,
                'last_viewed_on' => $rec->last_viewed_on
                    ? \core_privacy\local\request\transform::datetime($rec->last_viewed_on) : null,
            ];

            $contextdata = helper::get_context_data($context, $user);
            $contextdata = (object)array_merge((array)$contextdata, ['progress' => $data]);
            writer::with_context($context)->export_data([], $contextdata);
        }
    }

    /**
     * Delete all user data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('edztrackvideo', $context->instanceid);
        if (!$cm) {
            return;
        }
        $DB->delete_records('edztrackvideo_progress', ['edztrackvideoid' => $cm->instance]);
    }

    /**
     * Delete data for a user across approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('edztrackvideo', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('edztrackvideo_progress', [
                'edztrackvideoid' => $cm->instance,
                'userid' => $user->id,
            ]);
        }
    }

    /**
     * Delete data for multiple users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('edztrackvideo', $context->instanceid);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['edztrackvideoid' => $cm->instance], $inparams);
        $DB->delete_records_select('edztrackvideo_progress',
            "edztrackvideoid = :edztrackvideoid AND userid $insql", $params);
    }
}
