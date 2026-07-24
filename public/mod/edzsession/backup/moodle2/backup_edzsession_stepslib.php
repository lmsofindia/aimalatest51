<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Backup structure step for mod_edzsession.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete edzsession structure for backup, with file and id annotations.
 */
class backup_edzsession_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        // Root element.
        $edzsession = new backup_nested_element('edzsession', ['id'], [
            'name', 'intro', 'introformat', 'meetingprovider', 'accountid',
            'remotemeetingid', 'meetingstatus', 'storageprovider', 'storagefolderid',
            'storagefoldername', 'schedulejson', 'autorecord',
            'completionattendancepercent', 'completionminutes', 'completionsessions',
            'timecreated', 'timemodified',
        ]);

        $occurrences = new backup_nested_element('occurrences');
        $occurrence = new backup_nested_element('occurrence', ['id'], [
            'remotemeetingid', 'remoteuuid', 'starttime', 'duration', 'actualduration',
            'status', 'joinurl', 'timemodified',
        ]);

        $recordings = new backup_nested_element('recordings');
        $recording = new backup_nested_element('recording', ['id'], [
            'sourceuuid', 'storageprovider', 'state', 'assetid', 'sourceurl',
            'embedjson', 'sizebytes', 'attempts', 'lasterror', 'timecreated', 'timemodified',
        ]);

        $attendances = new backup_nested_element('attendances');
        $attendance = new backup_nested_element('attendance', ['id'], [
            'userid', 'matchedname', 'matchedemail', 'joinseconds',
            'attendedpercent', 'matchstate', 'timemodified',
        ]);

        $rawsegments = new backup_nested_element('rawsegments');
        $rawsegment = new backup_nested_element('rawsegment', ['id'], [
            'name', 'email', 'registrantid', 'jointime', 'leavetime',
        ]);

        // Build the tree.
        $edzsession->add_child($occurrences);
        $occurrences->add_child($occurrence);

        $occurrence->add_child($recordings);
        $recordings->add_child($recording);

        $occurrence->add_child($attendances);
        $attendances->add_child($attendance);

        $occurrence->add_child($rawsegments);
        $rawsegments->add_child($rawsegment);

        // Data sources.
        $edzsession->set_source_table('edzsession', ['id' => backup::VAR_ACTIVITYID]);
        $occurrence->set_source_table('edzsession_occurrence', ['edzsessionid' => backup::VAR_PARENTID]);
        $recording->set_source_table('edzsession_recording', ['occurrenceid' => backup::VAR_PARENTID]);

        if ($userinfo) {
            $attendance->set_source_table('edzsession_attendance', ['occurrenceid' => backup::VAR_PARENTID]);
            $rawsegment->set_source_table('edzsession_attendance_raw', ['occurrenceid' => backup::VAR_PARENTID]);
            $attendance->annotate_ids('user', 'userid');
        }

        // File annotations (intro).
        $edzsession->annotate_files('mod_edzsession', 'intro', null);

        return $this->prepare_activity_structure($edzsession);
    }
}
