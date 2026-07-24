<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Upgrade steps for mod_edzsession.
 *
 * Every schema change lands in its own if ($oldversion < YYYYMMDDXX) block.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param int $oldversion the version we are upgrading from
 * @return bool
 */
function xmldb_edzsession_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Phase 2: store the provider meeting id + creation status on the instance.
    if ($oldversion < 2026072401) {
        $table = new xmldb_table('edzsession');

        $field = new xmldb_field('remotemeetingid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'accountid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('meetingstatus', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'pending', 'remotemeetingid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026072401, 'edzsession');
    }

    // Phase 4: store the base download URL on the recording pipeline row.
    if ($oldversion < 2026072402) {
        $table = new xmldb_table('edzsession_recording');
        $field = new xmldb_field('sourceurl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'assetid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026072402, 'edzsession');
    }

    return true;
}
