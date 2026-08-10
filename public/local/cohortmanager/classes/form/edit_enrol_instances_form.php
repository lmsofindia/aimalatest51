<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_cohortmanager\form;

use core_form\dynamic_form;
use context_system;
use context_course;
use local_cohortmanager\external\enrols;
use context;
use moodle_url;
use ReflectionClass;
use enrol_cohort_plugin;

defined('MOODLE_INTERNAL') || die();

global $CFG;

// Include the enrol cohort plugin
require_once($CFG->dirroot . '/enrol/cohort/lib.php');

/**
 * Class edit_enrol_instances_form
 *
 * @package    local_cohortmanager
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_enrol_instances_form extends dynamic_form
{
    /**
     * Form definition - adds form elements for editing cohort enrol instances
     */
    public function definition()
    {
        $mform = $this->_form;

        // Hidden field for enrol instance ID
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);


        $mform->addElement('hidden', 'customint1');
        $mform->setType('customint1', PARAM_INT);

        $mform->addElement(
            'static',
            'courseinfo',
            get_string('course')
        );

        $mform->addElement(
            'static',
            'cohortname',
            get_string('cohort', 'cohort')
        );

        $mform->addElement(
            'select',
            'status',
            get_string('status', 'enrol_cohort'),
            $this->get_status_options()
        );
        $mform->setType('status', PARAM_INT);

        $mform->addElement(
            'select',
            'roleid',
            get_string('assignrole', 'enrol_cohort'),
            $this->get_role_options()
        );
        $mform->setType('roleid', PARAM_INT);

        $mform->addElement(
            'select',
            'customint2',
            get_string('addgroup', 'enrol_cohort'),
            $this->get_group_options()
        );
        $mform->setType('customint2', PARAM_INT);
    }

    /**
     * Return an array of valid options for the roles.
     *
     * @return array
     */
    protected function get_role_options()
    {
        global $DB;

        $enrolinstanceid = $this->optional_param('id', 0, PARAM_INT);

        $instance = $DB->get_record('enrol', ['id' => $enrolinstanceid]);

        $roles = enrols::get_cohort_course_roles($instance->customint1, $instance->courseid, $instance->id);

        $options = [];

        foreach ($roles as $role) {
            $options[$role['id']] = $role['name'];
        }

        return $options;
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options()
    {
        $reflection = new ReflectionClass(enrol_cohort_plugin::class);
        $method = $reflection->getMethod('get_status_options');

        $plugin = new enrol_cohort_plugin();
        return $method->invoke($plugin);
    }

    /**
     * Return an array of valid options for the groups.
     *
     * @return array
     */
    protected function get_group_options()
    {
        global $DB;

        $enrolinstanceid = $this->optional_param('id', 0, PARAM_INT);

        $courseid = $DB->get_field('enrol', 'courseid', ['id' => $enrolinstanceid]);

        if (!$courseid) {
            return array(0 => get_string('none'));
        }

        $options = enrols::get_course_groups($courseid);

        $list = [];

        foreach($options as $option) {
            $list[$option['id']] = $option['name'];
        }

        return $list;
    }

    /**
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context
    {
        $enrolinstanceid = $this->optional_param('id', 0, PARAM_INT);
        if ($enrolinstanceid) {
            global $DB;
            $sql = "SELECT e.id, c.contextid 
                    FROM {enrol} e 
                    JOIN {cohort} c ON e.customint1 = c.id 
                    WHERE e.id = :enrolid AND e.enrol = 'cohort'";
            $record = $DB->get_record_sql($sql, ['enrolid' => $enrolinstanceid]);
            if ($record) {
                return context::instance_by_id($record->contextid);
            }
        }
        return context_system::instance();
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     */
    protected function check_access_for_dynamic_submission(): void
    {
        $context = $this->get_context_for_dynamic_submission();
        require_capability('enrol/cohort:config', $context);
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void
    {
        global $DB;

        $enrolinstanceid = $this->optional_param('id', 0, PARAM_INT);

        if ($enrolinstanceid) {
            $sql = "SELECT e.*, c.name as cohortname, c.idnumber as cohortidnumber, course.id as courseid,
                           course.fullname as coursefullname, course.shortname as courseshortname
                    FROM {enrol} e 
                    JOIN {cohort} c ON e.customint1 = c.id 
                    JOIN {course} course ON e.courseid = course.id
                    WHERE e.id = :enrolid AND e.enrol = 'cohort'";
            $instance = $DB->get_record_sql($sql, ['enrolid' => $enrolinstanceid]);

            if ($instance) {
                $data = (object)[
                    'id' => (int) $instance->id,
                    'status' => (int) $instance->status,
                    'customint1' => (int) $instance->customint1,
                    'cohortname' => $instance->cohortname,
                    'roleid' => (int) $instance->roleid,
                    'courseid' => (int) $instance->courseid,
                    'courseinfo' => $instance->coursefullname . ' (' . $instance->courseshortname . ')',
                    'customint2' => (int) $instance->customint2
                ];
                $this->set_data($data);
            }
        }
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url
    {
        return new moodle_url('/local/cohortmanager/');
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return mixed
     */
    public function process_dynamic_submission()
    {
        global $DB;

        $enrolinstanceid = $this->optional_param('id', 0, PARAM_INT);
        $data = $this->get_data();

        if (!$enrolinstanceid) {
            return false;
        }

        // Get the existing enrol instance
        $instance = $DB->get_record('enrol', ['id' => $enrolinstanceid, 'enrol' => 'cohort'], '*', MUST_EXIST);

        // Update the instance
        $instance->status = $data->status;
        $instance->roleid = $data->roleid;
        $instance->customint2 = $data->customint2;
        $instance->timemodified = time();

        $DB->update_record('enrol', $instance);

        return true;
    }

    /**
     * Validate form data using the enrol plugin's edit_instance_validation method
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array of errors
     */
    public function validation($data, $files)
    {
        global $DB;

        $errors = parent::validation($data, $files);

        $plugin = new enrol_cohort_plugin();

        $existing = $DB->record_exists_select(
            'enrol',
            "courseid = :courseid AND roleid = :roleid AND customint1 = :customint1 AND id <> :id AND enrol = 'cohort'",
            $data
        );

        if ($existing) {
            $errors['roleid'] = get_string('instanceexists', 'local_cohortmanager');
        }

        $tovalidate = array(
            'status' => array_keys($this->get_status_options()),
            'roleid' => array_keys($this->get_role_options()),
            'customint2' => array_keys($this->get_group_options()),
        );

        $typeerrors = $plugin->validate_param_types($data, $tovalidate);

        $errors = array_merge($errors, $typeerrors);

        return $errors;
    }
}
