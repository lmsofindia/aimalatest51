<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider — the plugin stores derived per-student analytics in its cache tables.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_edzfaculty_cache', [
            'userid'    => 'privacy:metadata:cache:userid',
            'courseid'  => 'privacy:metadata:cache:courseid',
            'riskscore' => 'privacy:metadata:cache:riskscore',
            'avgscore'  => 'privacy:metadata:cache:avgscore',
        ], 'privacy:metadata:cache');
        return $collection;
    }

    /**
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {local_edzfaculty_cache} c
                  JOIN {context} ctx ON ctx.instanceid = c.courseid AND ctx.contextlevel = :level
                 WHERE c.userid = :userid";
        $contextlist->add_from_sql($sql, ['level' => CONTEXT_COURSE, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $userlist->add_from_sql('userid',
            "SELECT userid FROM {local_edzfaculty_cache} WHERE courseid = :courseid",
            ['courseid' => $context->instanceid]);
    }

    /**
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $record = $DB->get_record('local_edzfaculty_cache',
                ['courseid' => $context->instanceid, 'userid' => $userid]);
            if ($record) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_edzfaculty')], $record);
            }
        }
    }

    /**
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context instanceof \context_course) {
            $DB->delete_records('local_edzfaculty_cache', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_course) {
                $DB->delete_records('local_edzfaculty_cache',
                    ['courseid' => $context->instanceid, 'userid' => $userid]);
            }
        }
    }

    /**
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['courseid'] = $context->instanceid;
        $DB->delete_records_select('local_edzfaculty_cache',
            "courseid = :courseid AND userid $insql", $params);
    }
}
