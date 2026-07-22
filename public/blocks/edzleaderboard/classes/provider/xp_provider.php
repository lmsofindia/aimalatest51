<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * XP points provider — reads rankings from Level Up! (block_xp).
 *
 * This is the ONE place that knows where "points" come from. To swap the data
 * source later (native completions, grades, a custom engine, …) implement the
 * same three methods on a new provider and point the block at it — nothing else
 * in the block needs to change.
 *
 * @package    block_edzleaderboard
 * @copyright  2026 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edzleaderboard\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads the Level Up! (block_xp) ladder directly from its table.
 */
class xp_provider {

    /**
     * Is the XP source usable on this site?
     *
     * @return bool True when block_xp is installed and its table exists.
     */
    public static function is_available(): bool {
        global $DB;
        if (\core_component::get_component_directory('block_xp') === null) {
            return false;
        }
        return $DB->get_manager()->table_exists('block_xp');
    }

    /**
     * Top ranked users for a given XP "course" scope (site-wide = 1).
     *
     * @param  int $courseid The block_xp courseid (site-wide XP is stored under 1).
     * @param  int $limit    How many top rows to return.
     * @return array         Zero-indexed list of user rows (userpic fields + xp, lvl),
     *                       ordered highest XP first.
     */
    public static function get_ladder(int $courseid, int $limit): array {
        global $DB;

        if ($limit < 1) {
            $limit = 1;
        }

        // userpic fields give us everything fullname() and user_picture need.
        // get_sql args: (tablealias, leadingcomma=true, fieldprefix, idalias,
        // usecustomfields=false). leadingcomma MUST be true so ->selects starts
        // with ", u.id, ..." and slots cleanly after our x.* columns.
        $userfieldsapi = \core_user\fields::for_userpic();
        $ufields = $userfieldsapi->get_sql('u', true, '', '', false)->selects;

        $sql = "SELECT x.userid, x.xp, x.lvl {$ufields}
                  FROM {block_xp} x
                  JOIN {user} u ON u.id = x.userid
                 WHERE x.courseid = :courseid
                   AND u.deleted = 0
                   AND u.suspended = 0
              ORDER BY x.xp DESC, u.lastname ASC, u.firstname ASC, u.id ASC";

        $records = $DB->get_records_sql($sql, ['courseid' => $courseid], 0, $limit);

        return array_values($records);
    }

    /**
     * A single user's standing in the ladder.
     *
     * @param  int $courseid The block_xp courseid.
     * @param  int $userid   The user to look up.
     * @return array         [rank, xp, lvl]; rank is 0 when the user has no XP row.
     */
    public static function get_user_rank(int $courseid, int $userid): array {
        global $DB;

        $row = $DB->get_record('block_xp',
            ['courseid' => $courseid, 'userid' => $userid], 'xp, lvl');
        if (!$row) {
            return [0, 0, 0];
        }

        // Rank = number of users strictly ahead on XP, plus one.
        $better = $DB->count_records_select('block_xp',
            'courseid = :courseid AND xp > :xp',
            ['courseid' => $courseid, 'xp' => $row->xp]);

        return [$better + 1, (int) $row->xp, (int) $row->lvl];
    }
}
