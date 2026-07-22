<?php
// This file is part of Moodle - http://moodle.org/

/**
 * quizaccess_edproctoring upgrade steps.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion previous installed version
 * @return bool
 */
function xmldb_quizaccess_edproctoring_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Phase 2 upgrade stubs go here.
    // Example:
    // if ($oldversion < 2025060100) {
    //     // Add face_match_score column to snap table.
    //     $table = new xmldb_table('quizaccess_edproctoring_snap');
    //     $field = new xmldb_field('face_match_score', XMLDB_TYPE_NUMBER, '5,2', null, null, null, null, 'brightness_score');
    //     if (!$dbman->field_exists($table, $field)) {
    //         $dbman->add_field($table, $field);
    //     }
    //     upgrade_plugin_savepoint(true, 2025060100, 'quizaccess', 'edproctoring');
    // }

    return true;
}
