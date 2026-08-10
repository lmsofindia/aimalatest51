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

namespace local_cohortmanager\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_format_value;
use context_system;
use cache;
use context;
use moodle_exception;
use required_capability_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * External API for Roles Management in Cohort Manager
 *
 * @package    local_cohortmanager
 * @category   external
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class roles extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_user_context_roles_parameters() {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'Search query string', VALUE_DEFAULT, ''),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Number of records per page', VALUE_DEFAULT, 25)
        ]);
    }

    /**
     * Get all roles that can be assigned in user context.
     *
     * @param string $query Search query string
     * @param int $page Page number
     * @param int $perpage Number of records per page
     * @return array
     */
    public static function get_user_context_roles($query = '', $page = 0, $perpage = 25) {
        global $DB;

        $params = self::validate_parameters(self::get_user_context_roles_parameters(), [
            'query' => $query,
            'page' => $page,
            'perpage' => $perpage
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/role:manage', $context);

        $roles = get_roles_for_contextlevels(CONTEXT_USER);

        if (empty($roles)) {
            return [
                'roles' => [],
                'total' => 0
            ];
        }

        list($insql, $inparams) = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED);

        $sql = "SELECT r.id, r.shortname, r.name, r.description, r.sortorder, r.archetype
                  FROM {role} r
                 WHERE r.id {$insql}";

        $params_sql = $inparams;

        if (!empty($params['query'])) {
            $sql .= " AND (" . $DB->sql_like('r.name', ':queryname', false, false) . "
                       OR " . $DB->sql_like('r.shortname', ':queryshortname', false, false) . ")";
            $params_sql['queryname'] = '%' . $params['query'] . '%';
            $params_sql['queryshortname'] = '%' . $params['query'] . '%';
        }

        $sql .= " ORDER BY r.sortorder ASC";

        $countsql = "SELECT COUNT(*) FROM ({$sql}) AS countquery";
        $total = $DB->count_records_sql($countsql, $params_sql);
        $records = $DB->get_records_sql($sql, $params_sql, $page * $perpage, $perpage);

        $roleslist = [];
        foreach ($records as $record) {
            $roleslist[] = [
                'id' => $record->id,
                'shortname' => $record->shortname,
                'name' => $record->name,
                'description' => $record->description,
                'sortorder' => $record->sortorder,
                'archetype' => $record->archetype
            ];
        }

        return [
            'roles' => $roleslist,
            'total' => $total
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function get_user_context_roles_returns() {
        return new external_single_structure([
            'roles' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Role ID'),
                    'shortname' => new external_value(PARAM_RAW, 'Role short name'),
                    'name' => new external_value(PARAM_RAW, 'Role name'),
                    'description' => new external_value(PARAM_RAW, 'Role description'),
                    'sortorder' => new external_value(PARAM_INT, 'Role sort order'),
                    'archetype' => new external_value(PARAM_RAW, 'Role archetype')
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total number of roles')
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_role_parameters() {
        return new external_function_parameters([
            'roleid' => new external_value(PARAM_INT, 'Role ID to update'),
            'description' => new external_value(PARAM_RAW, 'Role description', VALUE_DEFAULT, '')
        ]);
    }

    /**
     * Update a role's custom name and description.
     *
     * @param int $roleid Role ID
     * @param string $description Role description
     * @return array
     */
    public static function update_role($roleid, $description = '') {
        global $DB;

        $params = self::validate_parameters(self::update_role_parameters(), [
            'roleid' => $roleid,
            'description' => $description
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/role:manage', $context);

        $role = $DB->get_record('role', ['id' => $params['roleid']], '*', MUST_EXIST);

        $usercontextroles = get_roles_for_contextlevels(CONTEXT_USER);
        if (!isset($usercontextroles[$role->id])) {
            throw new moodle_exception('rolenotfound', 'local_cohortmanager');
        }

        if ($params['description'] !== '') {
            $role->description = $params['description'];
            $DB->update_record('role', $role);
        }

        cache::make('core', 'roledefs')->delete('roles');

        return [
            'success' => true,
            'message' => get_string('roleupdatedsuccessfully', 'local_cohortmanager'),
            'role' => [
                'id' => $role->id,
                'shortname' => $role->shortname,
                'name' => $role->name,
                'description' => $role->description,
                'sortorder' => $role->sortorder,
                'archetype' => $role->archetype
            ]
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function update_role_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the update was successful'),
            'message' => new external_value(PARAM_RAW, 'Success or error message'),
            'role' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Role ID'),
                'shortname' => new external_value(PARAM_RAW, 'Role short name'),
                'name' => new external_value(PARAM_RAW, 'Role name'),
                'description' => new external_value(PARAM_RAW, 'Role description'),
                'sortorder' => new external_value(PARAM_INT, 'Role sort order'),
                'archetype' => new external_value(PARAM_RAW, 'Role archetype')
            ])
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function create_role_parameters() {
        return new external_function_parameters([
            'shortname' => new external_value(PARAM_RAW, 'Role short name'),
            'name' => new external_value(PARAM_RAW, 'Role name'),
            'description' => new external_value(PARAM_RAW, 'Role description', VALUE_DEFAULT, ''),
            'archetype' => new external_value(PARAM_RAW, 'Role archetype', VALUE_DEFAULT, '')
        ]);
    }

    /**
     * Create a new role that can be assigned in user context.
     *
     * @param string $shortname Role short name
     * @param string $name Role name
     * @param string $description Role description
     * @param string $archetype Role archetype
     * @return array
     */
    public static function create_role($shortname, $name, $description = '', $archetype = '') {
        global $DB;

        $params = self::validate_parameters(self::create_role_parameters(), [
            'shortname' => $shortname,
            'name' => $name,
            'description' => $description,
            'archetype' => $archetype
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/role:manage', $context);

        if ($DB->record_exists('role', ['shortname' => $params['shortname']])) {
            throw new moodle_exception('roleexists', 'local_cohortmanager');
        }

        $roleid = create_role(
            $params['name'],
            $params['shortname'],
            $params['description'],
            $params['archetype'] ?: ''
        );

        set_role_contextlevels($roleid, [CONTEXT_USER]);

        $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);

        return [
            'success' => true,
            'message' => get_string('rolecreatedsuccessfully', 'local_cohortmanager'),
            'role' => [
                'id' => $role->id,
                'shortname' => $role->shortname,
                'name' => $role->name,
                'description' => $role->description,
                'sortorder' => $role->sortorder,
                'archetype' => $role->archetype
            ]
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function create_role_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the creation was successful'),
            'message' => new external_value(PARAM_RAW, 'Success or error message'),
            'role' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Role ID'),
                'shortname' => new external_value(PARAM_RAW, 'Role short name'),
                'name' => new external_value(PARAM_RAW, 'Role name'),
                'description' => new external_value(PARAM_RAW, 'Role description'),
                'sortorder' => new external_value(PARAM_INT, 'Role sort order'),
                'archetype' => new external_value(PARAM_RAW, 'Role archetype')
            ])
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function delete_role_parameters() {
        return new external_function_parameters([
            'roleid' => new external_value(PARAM_INT, 'Role ID to delete')
        ]);
    }

    /**
     * Delete a role.
     *
     * @param int $roleid Role ID
     * @return array
     */
    public static function delete_role($roleid) {
        global $DB;

        $params = self::validate_parameters(self::delete_role_parameters(), [
            'roleid' => $roleid
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/role:manage', $context);

        $role = $DB->get_record('role', ['id' => $params['roleid']], '*', MUST_EXIST);

        $undeletableroles = [
            get_config('core', 'notloggedinroleid'),
            get_config('core', 'guestroleid'),
            get_config('core', 'defaultuserroleid'),
        ];

        if (in_array($role->id, $undeletableroles)) {
            throw new moodle_exception('cannotdeletethisrole', 'core_role');
        }

        if (!delete_role($role->id)) {
            throw new moodle_exception('cannotdeleterolewithid', 'core_role', '', $role->id);
        }

        return [
            'success' => true,
            'message' => get_string('roledeletedsuccessfully', 'local_cohortmanager')
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function delete_role_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the deletion was successful'),
            'message' => new external_value(PARAM_RAW, 'Success or error message')
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_role_parameters() {
        return new external_function_parameters([
            'roleid' => new external_value(PARAM_INT, 'Role ID to retrieve')
        ]);
    }

    /**
     * Get a single role by ID.
     *
     * @param int $roleid Role ID
     * @return array
     */
    public static function get_role($roleid) {
        global $DB;

        $params = self::validate_parameters(self::get_role_parameters(), [
            'roleid' => $roleid
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/role:manage', $context);

        $role = $DB->get_record('role', ['id' => $params['roleid']], '*', MUST_EXIST);

        $usercontextroles = get_roles_for_contextlevels(CONTEXT_USER);
        if (!isset($usercontextroles[$role->id])) {
            throw new moodle_exception('rolenotfound', 'local_cohortmanager');
        }

        return [
            'id' => $role->id,
            'shortname' => $role->shortname,
            'name' => $role->name,
            'description' => $role->description,
            'sortorder' => $role->sortorder,
            'archetype' => $role->archetype
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function get_role_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Role ID'),
            'shortname' => new external_value(PARAM_RAW, 'Role short name'),
            'name' => new external_value(PARAM_RAW, 'Role name'),
            'description' => new external_value(PARAM_RAW, 'Role description'),
            'sortorder' => new external_value(PARAM_INT, 'Role sort order'),
            'archetype' => new external_value(PARAM_RAW, 'Role archetype')
        ]);
    }
}