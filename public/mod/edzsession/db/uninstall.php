<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Uninstall hook for mod_edzsession.
 *
 * Note: assets already offloaded to a storage provider (Vimeo, S3, ...) are
 * intentionally NOT deleted here — uninstalling the plugin must not destroy a
 * customer's recordings. DB tables are dropped by Moodle from install.xml.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_edzsession_uninstall() {
    return true;
}
