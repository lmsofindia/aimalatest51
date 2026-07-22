<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Upgrade steps. Every schema change lives in an if ($oldversion < N) block.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Perform plugin upgrades.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool
 */
function xmldb_local_edzfaculty_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Template for future schema changes:
    //
    // if ($oldversion < 2026080100) {
    //     $table = new xmldb_table('local_edzfaculty_cache');
    //     $field = new xmldb_field('newcol', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'timemodified');
    //     if (!$dbman->field_exists($table, $field)) {
    //         $dbman->add_field($table, $field);
    //     }
    //     upgrade_plugin_savepoint(true, 2026080100, 'local', 'edzfaculty');
    // }

    return true;
}
