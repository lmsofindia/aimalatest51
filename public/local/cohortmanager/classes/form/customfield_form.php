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

use core_cohort\customfield\cohort_handler;
use core_form\dynamic_form;
use context_system;
use context;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
/**
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    local_cohortmanager
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfield_form extends dynamic_form
{
    /**
     * Define the cohort edit form
     */
    public function definition()
    {
        $mform = $this->_form;
        $cohortid = $this->optional_param('id', 0, PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $handler = cohort_handler::create();
        $handler->instance_form_definition($mform, $cohortid);
    }

    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        $handler = cohort_handler::create();
        $errors = array_merge($errors, $handler->instance_form_validation($data, $files));

        return $errors;
    }

    /**
     *  Apply a logic after data is set.
     */
    public function definition_after_data()
    {
        $cohortid = $this->optional_param('id', 0, PARAM_INT);
        $handler = cohort_handler::create();
        $handler->instance_form_definition_after_data($this->_form, $cohortid);
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
        require_capability('moodle/cohort:manage', $context);
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * @return mixed
     */
    public function process_dynamic_submission()
    {
        $cohortid = $this->optional_param('id', 0, PARAM_INT);
        $data = $this->get_data();
        $data->id = $cohortid;
        if ($cohortid && !empty($data)) {
            $handler = cohort_handler::create();
            $handler->instance_form_save($data, $cohortid);

            return true;
        }

        return false;
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void
    {
        global $DB;

        $cohortid = $this->optional_param('id', 0, PARAM_INT);

        if ($cohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $cohortid]);
            if ($cohort) {
                $handler = cohort_handler::create();
                $handler->instance_form_before_set_data($cohort);
                $this->set_data($cohort);
            }
        }
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url
    {
        $cohortid = $this->optional_param('id', 0, PARAM_INT);
        return new moodle_url('/local/cohortmanager/customfield.php', ['cohortid' => $cohortid]);
    }
}
