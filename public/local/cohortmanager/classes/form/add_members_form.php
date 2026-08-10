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
use core_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Class add_members_form
 *
 * @package    local_cohortmanager
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_members_form extends dynamic_form
{
    /**
     * Form definition - adds form elements for adding cohort members
     */
    public function definition()
    {
        $mform = $this->_form;
        $cohortid = $this->optional_param('id', 0, PARAM_INT);

        // Hidden field for cohort ID
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Users to add (multi-select)
        $options = [
            'ajax' => 'local_cohortmanager/form-members-selector',
            'cohortid' => $cohortid,
            'multiple' => true
        ];
        $mform->addElement('autocomplete', 'users', get_string('users'), [], $options);
        $mform->addRule('users', get_string('required'), 'required', null, 'client');
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
        require_capability('moodle/cohort:assign', $context);
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

        $cohort = $DB->get_record(
            'cohort',
            array(
                'id' => $cohortid
            ),
            '*',
            MUST_EXIST
        );

        if ($cohort && isset($data->users) && is_array($data->users)) {

            foreach ($data->users as $userid) {
                \cohort_add_member($cohortid, $userid);
            }

            return true;
        }

        return false;
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
}
