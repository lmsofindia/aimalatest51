<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Uninstall hook — drops derived cache tables. Core data is never touched.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom uninstall procedure.
 *
 * @return bool
 */
function xmldb_local_edzfaculty_uninstall() {
    // Tables are removed automatically from install.xml, but we clear the
    // user preference used for the manual-refresh throttle explicitly.
    global $DB;
    $DB->delete_records_select('user_preferences', $DB->sql_like('name', ':name'),
        ['name' => 'local_edzfaculty_%']);
    return true;
}
