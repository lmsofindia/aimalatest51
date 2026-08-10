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
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Class add_cohort_role_assignment_form
 *
 * @package    local_cohortmanager
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_cohort_role_assignment_form extends dynamic_form
{
    public function definition()
    {
        global $PAGE;

        $mform = $this->_form;
        $cohortid = $this->optional_param('cohortid', 0, PARAM_INT);

        $mform->addElement('hidden', 'cohortid');
        $mform->setType('cohortid', PARAM_INT);
        $mform->setDefault('cohortid', $cohortid);

        $roles = get_roles_for_contextlevels(CONTEXT_USER);

        if (empty($roles)) {
            $output = $PAGE->get_renderer('tool_cohortroles');
            $warning = $output->notify_problem(get_string('noassignableroles', 'local_cohortmanager'));
            $mform->addElement('html', $warning);
            return;
        }

        $useroptions = [
            'ajax' => 'core_user/form_user_selector',
            'multiple' => true
        ];
        $mform->addElement('autocomplete', 'userids', get_string('assigneduser', 'local_cohortmanager'), [], $useroptions);
        $mform->addRule('userids', get_string('required'), 'required', null, 'client');

        $names = role_get_names();
        $roleoptions = [];
        foreach ($roles as $roleid) {
            if (isset($names[$roleid])) {
                $roleoptions[$roleid] = $names[$roleid]->localname;
            }
        }

        $mform->addElement('select', 'roleid', get_string('assignedrole', 'local_cohortmanager'), $roleoptions);
        $mform->addRule('roleid', get_string('required'), 'required', null, 'client');
    }

    protected function get_context_for_dynamic_submission(): context
    {
        return context_system::instance();
    }

    protected function check_access_for_dynamic_submission(): void
    {
        $context = $this->get_context_for_dynamic_submission();
        require_capability('moodle/role:manage', $context);
    }

    public function validation($data, $files)
    {
        global $DB;

        $errors = parent::validation($data, $files);

        if (!empty($data['userids']) && !empty($data['roleid']) && !empty($data['cohortid'])) {
            $userids = (array) $data['userids'];
            foreach ($userids as $userid) {
                $exists = $DB->record_exists('tool_cohortroles', [
                    'userid' => $userid,
                    'roleid' => $data['roleid'],
                    'cohortid' => $data['cohortid'],
                ]);
                if ($exists) {
                    $errors['userids'] = get_string('cohortroleassignmentexists', 'local_cohortmanager');
                    break;
                }
            }
        }

        return $errors;
    }

    public function set_data_for_dynamic_submission(): void
    {
        $data = (object)[
            'cohortid' => $this->optional_param('cohortid', 0, PARAM_INT),
        ];
        $this->set_data($data);
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url
    {
        return new moodle_url('/local/cohortmanager/');
    }

    public function process_dynamic_submission()
    {
        $data = $this->get_data();

        $userids = (array) $data->userids;
        $success = true;
        foreach ($userids as $userid) {
            $record = (object)[
                'userid' => $userid,
                'roleid' => $data->roleid,
                'cohortid' => $data->cohortid,
            ];

            $result = \tool_cohortroles\api::create_cohort_role_assignment($record);

            if (!$result) {
                $success = false;
                break;
            }
        }

        if (!$success) {
            throw new moodle_exception('cohortroleassignmentexists', 'local_cohortmanager');
        }

        return true;
    }
}
