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
use context;
use moodle_url;
use ReflectionClass;
use enrol_cohort_plugin;

defined('MOODLE_INTERNAL') || die();

global $CFG;

// Include the enrol cohort plugin
require_once($CFG->dirroot . '/enrol/cohort/lib.php');

// require_once($CFG->dirroot . '/cohort/lib.php');
// require_once($CFG->dirroot . '/enrol/locallib.php');

/**
 * Class add_enrol_instances_form
 *
 * @package    local_cohortmanager
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_enrol_instances_form extends dynamic_form
{
    /**
     * Form definition - adds form elements for adding cohort enrol instances
     */
    public function definition()
    {
        $cohortid = $this->optional_param('id', 0, PARAM_INT);
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $options = [
            'ajax' => 'local_cohortmanager/form-courses-selector',
            'cohortid' => $cohortid,
            'multiple' => true
        ];

        $mform->addElement('autocomplete', 'courses', get_string('courses'), [], $options);

        $mform->addRule('courses', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'select',
            'status',
            get_string('status', 'enrol_cohort'),
            $this->get_status_options()
        );

        $mform->addElement(
            'select',
            'roleid',
            get_string('assignrole', 'enrol_cohort'),
            $this->get_role_options()
        );
    }

    /**
     * Return an array of valid options for the roles.
     *
     * @param stdClass $instance
     * @param context $coursecontext
     * @return array
     */
    protected function get_role_options()
    {
        return get_default_enrol_roles(
            $this->get_context_for_dynamic_submission()
        );
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
     * Returns context where this form is used
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context
    {
        $cohortid = $this->optional_param('id', 0, PARAM_INT);
        if ($cohortid) {
            global $DB;
            $cohort = $DB->get_record('cohort', ['id' => $cohortid]);
            if ($cohort) {
                return context::instance_by_id($cohort->contextid);
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
        $data = (object)[
            'id' => $this->optional_param('id', 0, PARAM_INT),
        ];
        $this->set_data($data);
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

        $cohortid = $this->optional_param('id', 0, PARAM_INT);
        $data = $this->get_data();

        if (!isset($data->courses) || !is_array($data->courses)) {
            return false;
        }

        $cohort = $DB->get_record(
            'cohort',
            array(
                'id' => $cohortid
            ),
            '*',
            MUST_EXIST
        );

        $plugin = new enrol_cohort_plugin;

        foreach ($data->courses as $courseid) {
            $course = get_course($courseid);
            $plugin->add_instance(
                $course,
                [
                    'status' => $data->status,
                    'roleid' => $data->roleid,
                    'customint1' => $cohort->id
                ]
            );
        }

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

        // Get the enrol cohort plugin
        $plugin = new enrol_cohort_plugin();

        // Prepare data for validation
        $cohortid = $this->optional_param('id', 0, PARAM_INT);

        // Cast data to object if it's an array
        $dataobj = is_array($data) ? (object) $data : $data;

        // Validate for each selected course
        if (!empty($dataobj->courses) && is_array($dataobj->courses)) {
            foreach ($dataobj->courses as $courseid) {

                // Check if instance already exists for this course
                $existing = $DB->record_exists_select(
                    'enrol',
                    "courseid = :courseid AND roleid = :roleid AND customint1 = :customint1 AND enrol = 'cohort'",
                    [
                        'courseid' => $courseid,
                        'roleid' => $dataobj->roleid,
                        'customint1' => $cohortid
                    ]
                );

                if ($existing) {
                    $errors['roleid'] = get_string('instanceexists', 'local_cohortmanager');
                    break;
                }

                $tovalidate = array(
                    'status' => array_keys(self::get_status_options()),
                    'roleid' => array_keys(self::get_role_options()),
                );

                $typeerrors = $plugin->validate_param_types($data, $tovalidate);

                $errors = array_merge($errors, $typeerrors);
            }
        }

        return $errors;
    }
}
