<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * Add/edit form for mod_edzsession.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

use mod_edzsession\local\provider_manager;

/**
 * mod_edzsession settings form.
 */
class mod_edzsession_mod_form extends moodleform_mod {

    public function definition() {
        global $DB;
        $mform = $this->_form;

        // ---- General ----
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('sessionname', 'mod_edzsession'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // ---- Meeting ----
        $mform->addElement('header', 'meetingheader', get_string('meetingsettings', 'mod_edzsession'));

        // Meeting provider (usually just Zoom now, but the field is provider-driven).
        $mform->addElement('select', 'meetingprovider',
            get_string('meetingprovider', 'mod_edzsession'),
            provider_manager::meeting_provider_menu());
        $mform->setDefault('meetingprovider', get_config('mod_edzsession', 'defaultmeetingprovider') ?: 'zoom');

        // Which credential account hosts this session (the multi-account feature).
        $accounts = $DB->get_records_menu('edzsession_account', ['enabled' => 1], 'name ASC', 'id, name');
        if (empty($accounts)) {
            $mform->addElement('static', 'noaccounts', get_string('meetingaccount', 'mod_edzsession'),
                get_string('noaccounts', 'mod_edzsession'));
        } else {
            $mform->addElement('select', 'accountid',
                get_string('meetingaccount', 'mod_edzsession'), $accounts);
        }

        // Schedule (P2 builds full recurrence UI; P1 captures start + duration).
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'mod_edzsession'));
        $mform->addElement('duration', 'duration', get_string('duration', 'mod_edzsession'),
            ['defaultunit' => 60, 'units' => [60, 3600]]);
        $mform->setDefault('duration', 3600);

        $mform->addElement('advcheckbox', 'autorecord', get_string('autorecord', 'mod_edzsession'));
        $mform->setDefault('autorecord', 1);

        // ---- Recurrence ----
        $mform->addElement('select', 'recurrencetype', get_string('recurrencetype', 'mod_edzsession'), [
            'single' => get_string('recur_single', 'mod_edzsession'),
            'weekly' => get_string('recur_weekly', 'mod_edzsession'),
        ]);
        $mform->setDefault('recurrencetype', 'single');

        $mform->addElement('text', 'recurinterval', get_string('recur_interval', 'mod_edzsession'), ['size' => 3]);
        $mform->setType('recurinterval', PARAM_INT);
        $mform->setDefault('recurinterval', 1);
        $mform->hideIf('recurinterval', 'recurrencetype', 'neq', 'weekly');

        // Weekday checkboxes. Numbering: Sun=1 .. Sat=7 (matches schedule engine).
        $days = [
            1 => get_string('sunday', 'calendar'),
            2 => get_string('monday', 'calendar'),
            3 => get_string('tuesday', 'calendar'),
            4 => get_string('wednesday', 'calendar'),
            5 => get_string('thursday', 'calendar'),
            6 => get_string('friday', 'calendar'),
            7 => get_string('saturday', 'calendar'),
        ];
        $dayels = [];
        foreach ($days as $num => $label) {
            $dayels[] = $mform->createElement('advcheckbox', "recurweekdays[$num]", '', $label);
        }
        $mform->addGroup($dayels, 'recurweekdaysgroup', get_string('recur_weekdays', 'mod_edzsession'), ' ', false);
        $mform->hideIf('recurweekdaysgroup', 'recurrencetype', 'neq', 'weekly');

        $mform->addElement('select', 'recurendmode', get_string('recur_endmode', 'mod_edzsession'), [
            'count' => get_string('recur_endcount', 'mod_edzsession'),
            'until' => get_string('recur_enduntil', 'mod_edzsession'),
        ]);
        $mform->setDefault('recurendmode', 'count');
        $mform->hideIf('recurendmode', 'recurrencetype', 'neq', 'weekly');

        $mform->addElement('text', 'recurcount', get_string('recur_count', 'mod_edzsession'), ['size' => 3]);
        $mform->setType('recurcount', PARAM_INT);
        $mform->setDefault('recurcount', 8);
        $mform->hideIf('recurcount', 'recurrencetype', 'neq', 'weekly');
        $mform->hideIf('recurcount', 'recurendmode', 'neq', 'count');

        $mform->addElement('date_selector', 'recuruntil', get_string('recur_until', 'mod_edzsession'));
        $mform->hideIf('recuruntil', 'recurrencetype', 'neq', 'weekly');
        $mform->hideIf('recuruntil', 'recurendmode', 'neq', 'until');

        // ---- Recording storage ----
        $mform->addElement('header', 'storageheader', get_string('storagesettings', 'mod_edzsession'));

        // Per-activity storage override. "" (blank) means "use site default".
        $storagemenu = ['' => get_string('usesitedefault', 'mod_edzsession')]
            + provider_manager::storage_provider_menu();
        $mform->addElement('select', 'storageprovider',
            get_string('storageprovider', 'mod_edzsession'), $storagemenu);
        $mform->setDefault('storageprovider', '');
        $mform->addHelpButton('storageprovider', 'storageprovider', 'mod_edzsession');

        // Storage folder: offer a dropdown of the provider's existing folders
        // (with free-typing to create a new one). Falls back to a text field if
        // the folder list can't be fetched.
        $folderoptions = null;
        try {
            $sp = $this->current->storageprovider ?? '';
            $storage = provider_manager::storage_for_activity((object) ['storageprovider' => $sp]);
            if ($storage->supports_folders() && $storage->is_configured()) {
                $labels = array_values($storage->list_folders());
                if (!empty($labels)) {
                    $folderoptions = array_combine($labels, $labels);
                }
            }
        } catch (\Throwable $e) {
            $folderoptions = null; // Provider unreachable — use the text fallback.
        }

        if ($folderoptions !== null) {
            $mform->addElement('autocomplete', 'storagefoldername',
                get_string('storagefolder', 'mod_edzsession'), $folderoptions, [
                    'tags' => true,            // Allow typing a new folder name.
                    'multiple' => false,
                    'noselectionstring' => get_string('storagefolder_root', 'mod_edzsession'),
                    'placeholder' => get_string('storagefolder_ph', 'mod_edzsession'),
                ]);
        } else {
            $mform->addElement('text', 'storagefoldername',
                get_string('storagefolder', 'mod_edzsession'), ['size' => 48]);
        }
        $mform->setType('storagefoldername', PARAM_TEXT);
        $mform->addHelpButton('storagefoldername', 'storagefolder', 'mod_edzsession');

        // ---- Standard course module elements ----
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Custom completion rule fields.
     *
     * @return array element names
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $group = [];

        $group[] = $mform->createElement('checkbox', 'completionattendancepercentenabled', '',
            get_string('completionattendancepercent', 'mod_edzsession'));
        $group[] = $mform->createElement('text', 'completionattendancepercent', '', ['size' => 3]);
        $mform->setType('completionattendancepercent', PARAM_INT);
        $mform->addGroup($group, 'completionattendancepercentgroup',
            get_string('completionattendancepercentlabel', 'mod_edzsession'), [' '], false);
        $mform->disabledIf('completionattendancepercent', 'completionattendancepercentenabled', 'notchecked');

        $group2 = [];
        $group2[] = $mform->createElement('checkbox', 'completionminutesenabled', '',
            get_string('completionminutes', 'mod_edzsession'));
        $group2[] = $mform->createElement('text', 'completionminutes', '', ['size' => 3]);
        $mform->setType('completionminutes', PARAM_INT);
        $mform->addGroup($group2, 'completionminutesgroup',
            get_string('completionminuteslabel', 'mod_edzsession'), [' '], false);
        $mform->disabledIf('completionminutes', 'completionminutesenabled', 'notchecked');

        $group3 = [];
        $group3[] = $mform->createElement('checkbox', 'completionsessionsenabled', '',
            get_string('completionsessions', 'mod_edzsession'));
        $group3[] = $mform->createElement('text', 'completionsessions', '', ['size' => 3]);
        $mform->setType('completionsessions', PARAM_INT);
        $mform->addGroup($group3, 'completionsessionsgroup',
            get_string('completionsessionslabel', 'mod_edzsession'), [' '], false);
        $mform->disabledIf('completionsessions', 'completionsessionsenabled', 'notchecked');

        return [
            'completionattendancepercentgroup',
            'completionminutesgroup',
            'completionsessionsgroup',
        ];
    }

    public function completion_rule_enabled($data) {
        return (!empty($data['completionattendancepercentenabled']) && $data['completionattendancepercent'] > 0)
            || (!empty($data['completionminutesenabled']) && $data['completionminutes'] > 0)
            || (!empty($data['completionsessionsenabled']) && $data['completionsessions'] > 0);
    }

    public function data_preprocessing(&$defaultvalues) {
        // Pre-tick the completion checkboxes when a value exists.
        $defaultvalues['completionattendancepercentenabled'] = !empty($defaultvalues['completionattendancepercent']) ? 1 : 0;
        $defaultvalues['completionminutesenabled'] = !empty($defaultvalues['completionminutes']) ? 1 : 0;
        $defaultvalues['completionsessionsenabled'] = !empty($defaultvalues['completionsessions']) ? 1 : 0;

        // Rebuild schedule + recurrence widgets from the stored schedulejson.
        if (!empty($defaultvalues['schedulejson'])) {
            $spec = json_decode($defaultvalues['schedulejson'], true) ?: [];
            $defaultvalues['starttime'] = $spec['starttime'] ?? time();
            $defaultvalues['duration'] = (int) ($spec['duration'] ?? 60) * 60; // minutes -> seconds.
            $defaultvalues['recurrencetype'] = $spec['type'] ?? 'single';
            if (($spec['type'] ?? 'single') === 'weekly') {
                $defaultvalues['recurinterval'] = $spec['interval'] ?? 1;
                foreach (($spec['weekdays'] ?? []) as $wd) {
                    $defaultvalues["recurweekdays"][$wd] = 1;
                }
                $defaultvalues['recurendmode'] = $spec['endmode'] ?? 'count';
                $defaultvalues['recurcount'] = $spec['count'] ?? 8;
                $defaultvalues['recuruntil'] = $spec['until'] ?? time();
            }
        }
    }

    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionattendancepercentenabled) || !$autocompletion) {
                $data->completionattendancepercent = 0;
            }
            if (empty($data->completionminutesenabled) || !$autocompletion) {
                $data->completionminutes = 0;
            }
            if (empty($data->completionsessionsenabled) || !$autocompletion) {
                $data->completionsessions = 0;
            }
        }
    }
}
