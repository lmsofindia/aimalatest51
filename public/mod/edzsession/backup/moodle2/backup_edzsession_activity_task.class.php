<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Backup task for mod_edzsession.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/edzsession/backup/moodle2/backup_edzsession_stepslib.php');

/**
 * Backup task that provides the settings and steps to back up mod_edzsession.
 */
class backup_edzsession_activity_task extends backup_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new backup_edzsession_activity_structure_step('edzsession_structure', 'edzsession.xml'));
    }

    /**
     * Encode absolute links to this module for portability.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot . '/mod/edzsession', '#');

        // Link to the view of one activity.
        $content = preg_replace(
            "#($base/view.php\?id=)([0-9]+)#",
            '$@EDZSESSIONVIEWBYID*$2@$', $content);

        // Link to the index of the course.
        $content = preg_replace(
            "#($base/index.php\?id=)([0-9]+)#",
            '$@EDZSESSIONINDEX*$2@$', $content);

        return $content;
    }
}
