<?php
/**
 * Plugin upgrade steps.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_edzaiaxisfront_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    // All schema is defined in install.xml for the initial release (version 2026032900).

    if ($oldversion < 2026040303) {
        $table = new xmldb_table('local_edzaiaxisfront_token_usage');

        // 1. Rename tokens_used → tokens_day (was misnamed in original install.xml)
        $field_old = new xmldb_field('tokens_used');
        if ($dbman->field_exists($table, $field_old)) {
            $field_renamed = new xmldb_field(
                'tokens_used', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'month_year'
            );
            $dbman->rename_field($table, $field_renamed, 'tokens_day');
        }

        // 2. Add tokens_month column (was missing from original install.xml)
        $field_month = new xmldb_field(
            'tokens_month', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'tokens_day'
        );
        if (!$dbman->field_exists($table, $field_month)) {
            $dbman->add_field($table, $field_month);
            // Back-fill: set tokens_month = tokens_day (best approximation for existing rows)
            $DB->execute(
                'UPDATE {local_edzaiaxisfront_token_usage} SET tokens_month = tokens_day WHERE tokens_month = 0'
            );
        }

        upgrade_plugin_savepoint(true, 2026040303, 'local', 'edzaiaxisfront');
    }

    if ($oldversion < 2026040309) {
        // ── 1. Add thread_name column to chat_sessions ────────────────────────
        $sess_table = new xmldb_table('local_edzaiaxisfront_chat_sessions');
        $fname_field = new xmldb_field('thread_name', XMLDB_TYPE_CHAR, '255', null, false, null, null, 'chat_mode');
        if (!$dbman->field_exists($sess_table, $fname_field)) {
            $dbman->add_field($sess_table, $fname_field);
        }

        // ── 2. Create chat_messages table ─────────────────────────────────────
        $msg_table = new xmldb_table('local_edzaiaxisfront_chat_messages');

        if (!$dbman->table_exists($msg_table)) {
            $msg_table->add_field('id',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $msg_table->add_field('sessionid',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $msg_table->add_field('userid',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $msg_table->add_field('role',        XMLDB_TYPE_CHAR,    '16', null, XMLDB_NOTNULL, null, 'user');
            $msg_table->add_field('message',     XMLDB_TYPE_TEXT,    null, null, XMLDB_NOTNULL);
            $msg_table->add_field('tokens_used', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $msg_table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $msg_table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $msg_table->add_index('idx_session', XMLDB_INDEX_NOTUNIQUE, ['sessionid']);
            $msg_table->add_index('idx_user_time', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);
            $dbman->create_table($msg_table);
        }

        upgrade_plugin_savepoint(true, 2026040309, 'local', 'edzaiaxisfront');
    }

    if ($oldversion < 2026040314) {
        // Add content column to kb_items for storing plain-text paste content.
        // This allows admins to view and re-edit text KB items after ingestion.
        $kb_table   = new xmldb_table('local_edzaiaxisfront_kb_items');
        $content_field = new xmldb_field('content', XMLDB_TYPE_TEXT, null, null, false, null, null, 'doc_type');
        if (!$dbman->field_exists($kb_table, $content_field)) {
            $dbman->add_field($kb_table, $content_field);
        }

        upgrade_plugin_savepoint(true, 2026040314, 'local', 'edzaiaxisfront');
    }

    if ($oldversion < 2026072104) {
        // Chat schema was previously created ONLY in the 2026040309 upgrade step and
        // never backported to install.xml, so sites that installed the plugin fresh
        // are missing thread_name + chat_messages. Add them idempotently here.
        $sess_table  = new xmldb_table('local_edzaiaxisfront_chat_sessions');
        $fname_field = new xmldb_field('thread_name', XMLDB_TYPE_CHAR, '255', null, false, null, null, 'chat_mode');
        if (!$dbman->field_exists($sess_table, $fname_field)) {
            $dbman->add_field($sess_table, $fname_field);
        }

        $msg_table = new xmldb_table('local_edzaiaxisfront_chat_messages');
        if (!$dbman->table_exists($msg_table)) {
            $msg_table->add_field('id',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $msg_table->add_field('sessionid',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $msg_table->add_field('userid',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $msg_table->add_field('role',        XMLDB_TYPE_CHAR,    '16', null, XMLDB_NOTNULL, null, 'user');
            $msg_table->add_field('message',     XMLDB_TYPE_TEXT,    null, null, XMLDB_NOTNULL);
            $msg_table->add_field('tokens_used', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $msg_table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $msg_table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $msg_table->add_index('idx_session', XMLDB_INDEX_NOTUNIQUE, ['sessionid']);
            $msg_table->add_index('idx_user_time', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);
            $dbman->create_table($msg_table);
        }

        upgrade_plugin_savepoint(true, 2026072104, 'local', 'edzaiaxisfront');
    }

    return true;
}
