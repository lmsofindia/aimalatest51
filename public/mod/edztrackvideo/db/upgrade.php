<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for mod_edztrackvideo.
 *
 * v1 baseline (2026062700) has no prior versions. Add an
 * if ($oldversion < YYYYMMDDXX) { ... upgrade_mod_savepoint(...); } block
 * for every future schema change.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_edztrackvideo_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026062701) {
        // Add the serverpath column for the "Server file" source.
        $table = new xmldb_table('edztrackvideo');
        $field = new xmldb_field('serverpath', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'videoid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026062701, 'edztrackvideo');
    }

    return true;
}
