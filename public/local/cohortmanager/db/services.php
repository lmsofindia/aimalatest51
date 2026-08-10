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
 * External functions and service declaration for Cohort Manager
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    local_cohortmanager
 * @category   webservice
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_cohortmanager_get_all_strings' => [
        'classname'   => 'local_cohortmanager\\external\\app',
        'methodname' => 'get_all_strings',
        'description' => 'Returns all strings for the local_cohortmanager component',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/cohort:manage',
    ],
    'local_cohortmanager_add_cohort_members' => [
        'classname' => 'core_cohort_external',
        'methodname' => 'add_cohort_members',
        'classpath' => 'cohort/externallib.php',
        'description' => 'Adds cohort members.',
        'type' => 'write',
        'capabilities' => 'moodle/cohort:assign',
        'ajax' => true
    ],
    'local_cohortmanager_create_cohorts' => [
        'classname' => 'core_cohort_external',
        'methodname' => 'create_cohorts',
        'classpath' => 'cohort/externallib.php',
        'description' => 'Creates new cohorts.',
        'type' => 'write',
        'capabilities' => 'moodle/cohort:manage',
        'ajax' => true
    ],
    'local_cohortmanager_delete_cohort_members' => [
        'classname' => 'core_cohort_external',
        'methodname' => 'delete_cohort_members',
        'classpath' => 'cohort/externallib.php',
        'description' => 'Deletes cohort members.',
        'type' => 'write',
        'capabilities' => 'moodle/cohort:assign',
        'ajax' => true
    ],
    'local_cohortmanager_delete_cohorts' => [
        'classname' => 'core_cohort_external',
        'methodname' => 'delete_cohorts',
        'classpath' => 'cohort/externallib.php',
        'description' => 'Deletes all specified cohorts.',
        'type' => 'write',
        'capabilities' => 'moodle/cohort:manage',
        'ajax' => true
    ],
    'local_cohortmanager_get_cohort_members' => [
        'classname' => 'core_cohort_external',
        'methodname' => 'get_cohort_members',
        'classpath' => 'cohort/externallib.php',
        'description' => 'Returns cohort members.',
        'type' => 'read',
        'capabilities' => 'moodle/cohort:view',
        'ajax' => true
    ],
    'local_cohortmanager_get_potential_cohort_members' => [
        'classname' => 'local_cohortmanager\\external\\members',
        'methodname' => 'get_potential_members',
        'description' => 'Get potential user to add in cohort.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/cohort:assign'
    ],
    'local_cohortmanager_search_cohorts' => [
        'classname' => 'local_cohortmanager\\external\\app',
        'methodname' => 'search_cohorts_with_total',
        'description' => 'Search for cohorts with total count.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/cohort:view'
    ],
    'local_cohortmanager_get_cohorts' => [
        'classname' => 'core_cohort_external',
        'methodname' => 'get_cohorts',
        'classpath' => 'cohort/externallib.php',
        'description' => 'Returns cohort details.',
        'type' => 'read',
        'capabilities' => 'moodle/cohort:view',
        'ajax' => true
    ],
    'local_cohortmanager_update_cohorts' => [
        'classname' => 'core_cohort_external',
        'methodname' => 'update_cohorts',
        'classpath' => 'cohort/externallib.php',
        'description' => 'Updates existing cohorts.',
        'type' => 'write',
        'capabilities' => 'moodle/cohort:manage',
        'ajax' => true
    ],
    'local_cohortmanager_get_app_config' => [
        'classname'   => 'local_cohortmanager\\external\\app',
        'methodname' => 'get_app_config',
        'description' => 'Returns application configuration settings',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/cohort:manage',
    ],
    'local_cohortmanager_get_cohort_context_info' => [
        'classname'   => 'local_cohortmanager\\external\\app',
        'methodname' => 'get_cohort_context_info',
        'description' => 'Returns cohort context information based on contextid',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/cohort:manage',
    ],
    'local_cohortmanager_get_cohort_members_table_data' => [
        'classname'   => 'local_cohortmanager\\external\\members',
        'methodname' => 'get_cohort_members_table_data',
        'description' => 'Returns cohort members table data for dynamic table',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/cohort:manage'
    ],
    'local_cohortmanager_count_cohort_members' => [
        'classname'   => 'local_cohortmanager\\external\\members',
        'methodname' => 'count_cohort_members',
        'description' => 'Count the members of a specific cohort',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/cohort:view'
    ],
    'local_cohortmanager_get_cohort_enrol_instances' => [
        'classname'   => 'local_cohortmanager\\external\\enrols',
        'methodname' => 'get_cohort_enrol_instances',
        'description' => 'Get all enrol instances of enrol=cohort that the customint1 field is equal to the cohortid',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'enrol/cohort:config'
    ],
    'local_cohortmanager_count_cohort_enrol_instances' => [
        'classname'   => 'local_cohortmanager\\external\\enrols',
        'methodname' => 'count_cohort_enrol_instances',
        'description' => 'Count the enrol instances of a specific cohort',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/cohort:view'
    ],
    'local_cohortmanager_get_potential_cohort_courses' => [
        'classname'   => 'local_cohortmanager\\external\\enrols',
        'methodname' => 'get_potential_cohort_courses',
        'description' => 'Get list of potential courses to create a cohort enrol instances.',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'enrol/cohort:config'
    ],
    'local_cohortmanager_get_cohort_course_roles' => [
        'classname'   => 'local_cohortmanager\\external\\enrols',
        'methodname' => 'get_cohort_course_roles',
        'description' => 'Get list of course roles to create the cohort enrol instances.',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'enrol/cohort:config'
    ],
    'local_cohortmanager_create_cohort_enrol_instances' => [
        'classname'   => 'local_cohortmanager\\external\\enrols',
        'methodname' => 'create_cohort_enrol_instances',
        'description' => 'Create cohort enrol instances for multiple courses.',
        'type'       => 'write',
        'ajax' => true,
        'capabilities' => 'enrol/cohort:config'
    ],
    'local_cohortmanager_get_course_groups' => [
        'classname'   => 'local_cohortmanager\\external\\enrols',
        'methodname' => 'get_course_groups',
        'description' => 'Get list of course groups.',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'enrol/cohort:config'
    ],
    'local_cohortmanager_delete_cohort_enrol_instance' => [
        'classname'   => 'local_cohortmanager\\external\\enrols',
        'methodname' => 'delete_cohort_enrol_instance',
        'description' => 'Delete a cohort enrol instance.',
        'type'       => 'write',
        'ajax' => true,
        'capabilities' => 'enrol/cohort:config'
    ],
    'local_cohortmanager_toggle_cohort_enrol_instance_status' => [
        'classname'   => 'local_cohortmanager\\external\\enrols',
        'methodname' => 'toggle_cohort_enrol_instance_status',
        'description' => 'Toggle the status (active/inactive) of a cohort enrol instance.',
        'type'       => 'write',
        'ajax' => true,
        'capabilities' => 'enrol/cohort:config'
    ],
    'local_cohortmanager_get_user_context_roles' => [
        'classname'   => 'local_cohortmanager\\external\\roles',
        'methodname' => 'get_user_context_roles',
        'description' => 'Get all roles that can be assigned in user context.',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/role:manage'
    ],
    'local_cohortmanager_get_role' => [
        'classname'   => 'local_cohortmanager\\external\\roles',
        'methodname' => 'get_role',
        'description' => 'Get a single role by ID.',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/role:manage'
    ],
    'local_cohortmanager_create_role' => [
        'classname'   => 'local_cohortmanager\\external\\roles',
        'methodname' => 'create_role',
        'description' => 'Create a new role that can be assigned in user context.',
        'type'       => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/role:manage'
    ],
    'local_cohortmanager_update_role' => [
        'classname'   => 'local_cohortmanager\\external\\roles',
        'methodname' => 'update_role',
        'description' => 'Update a role\'s custom name and description.',
        'type'       => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/role:manage'
    ],
    'local_cohortmanager_delete_role' => [
        'classname'   => 'local_cohortmanager\\external\\roles',
        'methodname' => 'delete_role',
        'description' => 'Delete a role.',
        'type'       => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/role:manage'
    ],
    'local_cohortmanager_list_cohort_role_assignments' => [
        'classname'   => 'local_cohortmanager\\external\\cohortroles',
        'methodname' => 'list_cohort_role_assignments',
        'description' => 'List cohort role assignments with enriched data.',
        'type'       => 'read',
        'ajax' => true,
        'capabilities' => 'moodle/role:manage'
    ],
    'local_cohortmanager_delete_cohort_role_assignment' => [
        'classname'   => 'local_cohortmanager\\external\\cohortroles',
        'methodname' => 'delete_cohort_role_assignment',
        'description' => 'Delete a cohort role assignment.',
        'type'       => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/role:manage'
    ]
];

$services = [
    'local_cohortmanager' => [
        'functions' => [
            'local_cohortmanager_get_all_strings',
            'local_cohortmanager_add_cohort_members',
            'local_cohortmanager_create_cohorts',
            'local_cohortmanager_delete_cohort_members',
            'local_cohortmanager_delete_cohorts',
            'local_cohortmanager_get_cohort_members',
            'local_cohortmanager_get_potential_cohort_members',
            'local_cohortmanager_search_cohorts',
            'local_cohortmanager_get_cohorts',
            'local_cohortmanager_update_cohorts',
            'local_cohortmanager_get_app_config',
            'local_cohortmanager_get_cohort_context_info',
            'local_cohortmanager_get_cohort_members_table_data',
            'local_cohortmanager_count_cohort_members',
            'local_cohortmanager_get_cohort_enrol_instances',
            'local_cohortmanager_count_cohort_enrol_instances',
            'local_cohortmanager_get_potential_cohort_courses',
            'local_cohortmanager_get_cohort_course_roles',
            'local_cohortmanager_create_cohort_enrol_instances',
            'local_cohortmanager_get_course_groups',
            'local_cohortmanager_delete_cohort_enrol_instance',
            'local_cohortmanager_toggle_cohort_enrol_instance_status',
            'local_cohortmanager_get_user_context_roles',
            'local_cohortmanager_get_role',
            'local_cohortmanager_create_role',
            'local_cohortmanager_update_role',
            'local_cohortmanager_delete_role',
            'local_cohortmanager_list_cohort_role_assignments',
            'local_cohortmanager_delete_cohort_role_assignment'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_cohortmanager',
        'name' => 'Cohort Manager Service',
        'description' => 'Service for Cohort Manager external functions',
    ],
];
