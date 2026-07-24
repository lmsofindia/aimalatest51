<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Restore structure step for mod_edzsession.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the structure step to restore one edzsession activity.
 */
class restore_edzsession_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('edzsession', '/activity/edzsession');
        $paths[] = new restore_path_element('edzsession_occurrence',
            '/activity/edzsession/occurrences/occurrence');
        $paths[] = new restore_path_element('edzsession_recording',
            '/activity/edzsession/occurrences/occurrence/recordings/recording');

        if ($userinfo) {
            $paths[] = new restore_path_element('edzsession_attendance',
                '/activity/edzsession/occurrences/occurrence/attendances/attendance');
            $paths[] = new restore_path_element('edzsession_rawsegment',
                '/activity/edzsession/occurrences/occurrence/rawsegments/rawsegment');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_edzsession($data) {
        global $DB;
        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timecreated = $data->timecreated ?? time();
        $data->timemodified = time();
        // The credential account is site-scoped and may not exist on the target
        // site; drop the reference so the restored activity is re-provisioned.
        $data->accountid = null;
        $data->remotemeetingid = null;
        $data->meetingstatus = 'pending';

        $newid = $DB->insert_record('edzsession', $data);
        $this->apply_activity_instance($newid);
    }

    protected function process_edzsession_occurrence($data) {
        global $DB;
        $data = (object) $data;
        $data->edzsessionid = $this->get_new_parentid('edzsession');
        $data->timemodified = time();
        $newid = $DB->insert_record('edzsession_occurrence', $data);
        $this->set_mapping('edzsession_occurrence', $data->id ?? 0, $newid);
    }

    protected function process_edzsession_recording($data) {
        global $DB;
        $data = (object) $data;
        $data->occurrenceid = $this->get_new_parentid('edzsession_occurrence');
        $data->timemodified = time();
        $DB->insert_record('edzsession_recording', $data);
    }

    protected function process_edzsession_attendance($data) {
        global $DB;
        $data = (object) $data;
        $data->occurrenceid = $this->get_new_parentid('edzsession_occurrence');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timemodified = time();
        $DB->insert_record('edzsession_attendance', $data);
    }

    protected function process_edzsession_rawsegment($data) {
        global $DB;
        $data = (object) $data;
        $data->occurrenceid = $this->get_new_parentid('edzsession_occurrence');
        $DB->insert_record('edzsession_attendance_raw', $data);
    }

    protected function after_execute() {
        // Restore any files embedded in the intro.
        $this->add_related_files('mod_edzsession', 'intro', null);
    }
}
