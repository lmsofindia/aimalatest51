<?php
namespace block_corpwelcome\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('block_corpwelcome_streak',
            'privacy:metadata', 'block_corpwelcome');
        $collection->add_user_preference('block_corpwelcome_streak_time',
            'privacy:metadata', 'block_corpwelcome');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    public static function get_users_in_context(userlist $userlist): void {}

    public static function export_user_data(approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $ctx) {
            writer::with_context($ctx)->export_user_preference('block_corpwelcome',
                'streak', get_user_preferences('block_corpwelcome_streak', '', $contextlist->get_user()->id));
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {}

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        unset_user_preference('block_corpwelcome_streak', $contextlist->get_user()->id);
        unset_user_preference('block_corpwelcome_streak_time', $contextlist->get_user()->id);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        foreach ($userlist->get_userids() as $uid) {
            unset_user_preference('block_corpwelcome_streak', $uid);
            unset_user_preference('block_corpwelcome_streak_time', $uid);
        }
    }
}
