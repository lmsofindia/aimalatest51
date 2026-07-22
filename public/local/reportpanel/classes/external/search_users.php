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

namespace local_reportpanel\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * AJAX: search site users by name or email for the consolidated report picker.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_users extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search text (min 2 chars)'),
        ]);
    }

    /**
     * Execute.
     *
     * @param string $query
     * @return array
     */
    public static function execute(string $query): array {
        global $DB, $CFG;
        $params = self::validate_parameters(self::execute_parameters(), ['query' => $query]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/reportpanel:viewall', $context);

        $query = trim($params['query']);
        if (\core_text::strlen($query) < 2) {
            return ['users' => []];
        }

        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $like1 = $DB->sql_like($fullname, ':q1', false);
        $like2 = $DB->sql_like('u.email', ':q2', false);
        $sql = "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                       u.middlename, u.alternatename, u.email
                  FROM {user} u
                 WHERE u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1
                   AND u.id <> :guestid
                   AND ($like1 OR $like2)
              ORDER BY u.lastname, u.firstname";
        $users = $DB->get_records_sql($sql, [
            'q1' => '%' . $DB->sql_like_escape($query) . '%',
            'q2' => '%' . $DB->sql_like_escape($query) . '%',
            'guestid' => (int)$CFG->siteguest,
        ], 0, 20);

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => (int)$user->id,
                'fullname' => fullname($user),
                'email' => $user->email,
            ];
        }
        return ['users' => $result];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'users' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User id'),
                'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                'email' => new external_value(PARAM_TEXT, 'Email'),
            ])),
        ]);
    }
}
