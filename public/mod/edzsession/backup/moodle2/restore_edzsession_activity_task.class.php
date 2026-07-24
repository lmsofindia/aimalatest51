<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Restore task for mod_edzsession.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/edzsession/backup/moodle2/restore_edzsession_stepslib.php');

/**
 * Restore task that provides the settings and steps to restore mod_edzsession.
 */
class restore_edzsession_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_edzsession_activity_structure_step('edzsession_structure', 'edzsession.xml'));
    }

    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('edzsession', ['intro'], 'edzsession');
        return $contents;
    }

    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('EDZSESSIONVIEWBYID', '/mod/edzsession/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('EDZSESSIONINDEX', '/mod/edzsession/index.php?id=$1', 'course');
        return $rules;
    }
}
