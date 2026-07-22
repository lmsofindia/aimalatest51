<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Privacy API provider.
 *
 * @package   local_trackmytime
 * @copyright 2026 EDZLMS
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace local_trackmytime\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for local_trackmytime.
 *
 * Sprint 1 scaffold: implements metadata declaration so that the Privacy
 * API is satisfied on install. Full data export / deletion will be added
 * in a later sprint when the tables contain real data.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider {

    // -----------------------------------------------------------------------
    // Metadata.
    // -----------------------------------------------------------------------

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection The metadata collection to populate.
     * @return collection The populated collection.
     */
    public static function get_metadata(collection $collection): collection {

        // trackmytime — session-level time logs.
        $collection->add_database_table(
            'trackmytime',
            [
                'userid'    => 'privacy:metadata:trackmytime:userid',
                'courseid'  => 'privacy:metadata:trackmytime:courseid',
                'cmid'      => 'privacy:metadata:trackmytime:cmid',
                'timespent' => 'privacy:metadata:trackmytime:timespent',
            ],
            'privacy:metadata'
        );


        return $collection;
    }

    // -----------------------------------------------------------------------
    // Context enumeration.
    // -----------------------------------------------------------------------

    /**
     * Return the contexts that contain personal data for the given user.
     *
     * Sprint 1 stub — returns an empty contextlist until data write paths
     * exist.
     *
     * @param int $userid The Moodle user ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    // -----------------------------------------------------------------------
    // Data export.
    // -----------------------------------------------------------------------

    /**
     * Export personal data for the given user within the given contexts.
     *
     * Sprint 1 stub.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        // Sprint 1 stub — no data to export yet.
    }

    // -----------------------------------------------------------------------
    // Data deletion.
    // -----------------------------------------------------------------------

    /**
     * Delete all personal data for all users in the given context.
     *
     * Sprint 1 stub.
     *
     * @param \context $context The context to delete from.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        // Sprint 1 stub — no data to delete yet.
    }

    /**
     * Delete personal data for a specific user within the given contexts.
     *
     * Sprint 1 stub.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // Sprint 1 stub — no data to delete yet.
    }
}
