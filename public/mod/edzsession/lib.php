<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants for mod_edzsession.
 *
 * NOTE: this file contains NO reference to Zoom or Vimeo. All provider work is
 * routed through \mod_edzsession\local\provider_manager. That is deliberate —
 * it is what keeps the meeting/storage backends swappable.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Declare which features this module supports.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed
 */
function edzsession_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_MOD_PURPOSE:
            return defined('MOD_PURPOSE_COMMUNICATION') ? MOD_PURPOSE_COMMUNICATION : null;
        default:
            return null;
    }
}

/**
 * Add a new edzsession instance.
 *
 * @param stdClass $data form data
 * @param mod_edzsession_mod_form|null $mform
 * @return int new instance id
 */
function edzsession_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();
    $data->intro = $data->intro ?? '';
    $data->introformat = $data->introformat ?? FORMAT_HTML;
    $data->schedulejson = \mod_edzsession\local\schedule::from_formdata($data)->to_json();
    $data->meetingstatus = 'pending';
    edzsession_prepare_completion_fields($data);

    // Unknown form fields (recurrence widgets) are ignored by insert_record.
    $data->id = $DB->insert_record('edzsession', $data);

    // Create the remote meeting + generate occurrences (best-effort, non-fatal).
    $record = $DB->get_record('edzsession', ['id' => $data->id], '*', MUST_EXIST);
    \mod_edzsession\local\session_manager::provision($record);

    return $data->id;
}

/**
 * Update an existing edzsession instance.
 *
 * @param stdClass $data form data (includes ->instance)
 * @param mod_edzsession_mod_form|null $mform
 * @return bool
 */
function edzsession_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->schedulejson = \mod_edzsession\local\schedule::from_formdata($data)->to_json();
    edzsession_prepare_completion_fields($data);

    $DB->update_record('edzsession', $data);

    // Propagate schedule changes to the provider + resync future occurrences.
    $record = $DB->get_record('edzsession', ['id' => $data->id], '*', MUST_EXIST);
    \mod_edzsession\local\session_manager::provision($record);

    return true;
}

/**
 * Delete an edzsession instance and its dependent rows.
 *
 * @param int $id instance id
 * @return bool
 */
function edzsession_delete_instance($id) {
    global $DB;

    if (!$edzsession = $DB->get_record('edzsession', ['id' => $id])) {
        return false;
    }

    // Best-effort: delete the remote meeting (never block local cleanup on it).
    if (!empty($edzsession->remotemeetingid) && !empty($edzsession->accountid)) {
        try {
            $account = \mod_edzsession\local\account_vault::get((int) $edzsession->accountid);
            $provider = \mod_edzsession\local\provider_manager::get_meeting($edzsession->meetingprovider);
            $meeting = new \mod_edzsession\local\meeting\remote_meeting(
                $edzsession->remotemeetingid, '');
            $provider->delete_meeting($meeting, $account);
        } catch (\Throwable $e) {
            debugging('edzsession: remote meeting delete failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    $occurrenceids = $DB->get_fieldset_select('edzsession_occurrence', 'id',
        'edzsessionid = :eid', ['eid' => $id]);
    if ($occurrenceids) {
        list($insql, $params) = $DB->get_in_or_equal($occurrenceids, SQL_PARAMS_NAMED);
        $recordingids = $DB->get_fieldset_select('edzsession_recording', 'id',
            "occurrenceid $insql", $params);
        if ($recordingids) {
            list($rsql, $rparams) = $DB->get_in_or_equal($recordingids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('edzsession_pipeline_log', "recordingid $rsql", $rparams);
        }
        $DB->delete_records_select('edzsession_recording', "occurrenceid $insql", $params);
        $DB->delete_records_select('edzsession_attendance', "occurrenceid $insql", $params);
        $DB->delete_records_select('edzsession_attendance_raw', "occurrenceid $insql", $params);
    }
    $DB->delete_records('edzsession_occurrence', ['edzsessionid' => $id]);
    $DB->delete_records('edzsession', ['id' => $id]);

    // NOTE: recordings already stored on the provider are NOT deleted here.

    return true;
}

/**
 * Normalise completion rule fields (unchecked checkboxes => 0).
 *
 * @param stdClass $data
 */
function edzsession_prepare_completion_fields($data) {
    $completion = $data->completion ?? null;
    $autocompletion = $completion == COMPLETION_TRACKING_AUTOMATIC;
    foreach (['completionattendancepercent', 'completionminutes', 'completionsessions'] as $field) {
        if (!$autocompletion || empty($data->{$field})) {
            $data->{$field} = 0;
        }
    }
}

/**
 * Provide custom completion rule data to the course_module cache.
 *
 * @param cm_info $cm
 * @return cached_cm_info|null
 */
function edzsession_get_coursemodule_info($cm) {
    global $DB;

    $edzsession = $DB->get_record('edzsession', ['id' => $cm->instance],
        'id, name, intro, introformat, completionattendancepercent, completionminutes, completionsessions');
    if (!$edzsession) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $edzsession->name;

    if ($cm->showdescription) {
        $info->content = format_module_intro('edzsession', $edzsession, $cm->id, false);
    }

    if ($cm->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionattendancepercent' => $edzsession->completionattendancepercent,
            'completionminutes' => $edzsession->completionminutes,
            'completionsessions' => $edzsession->completionsessions,
        ];
    }

    return $info;
}
