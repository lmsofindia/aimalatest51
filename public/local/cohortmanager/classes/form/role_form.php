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
 * Class role_form
 *
 * @package    local_cohortmanager
 * @category   class
 * @copyright  2025 Davison Almeida <ramosdealmeidasistemas@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_form extends dynamic_form
{
    protected function get_context_for_dynamic_submission(): context
    {
        return context_system::instance();
    }

    protected function check_access_for_dynamic_submission(): void
    {
        $context = $this->get_context_for_dynamic_submission();
        require_capability('moodle/role:manage', $context);
    }

    public function definition()
    {
        $mform = $this->_form;
        $roleid = $this->optional_param('roleid', 0, PARAM_INT);
        $isedit = $roleid > 0;

        $mform->addElement('hidden', 'roleid');
        $mform->setType('roleid', PARAM_INT);
        $mform->setDefault('roleid', $roleid);

        $mform->addElement('header', 'general', get_string('general'));

        $mform->addElement(
            'text',
            'name',
            get_string('rolename', 'local_cohortmanager'),
            'maxlength="254" size="50"'
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'text',
            'shortname',
            get_string('roleshortname', 'local_cohortmanager'),
            'maxlength="254" size="50"'
        );
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string('description', 'local_cohortmanager'),
            ['rows' => 4]
        );
        $mform->setType('description', PARAM_RAW);

        $archetypeoptions = ['' => get_string('none')] + get_role_archetypes();
        $mform->addElement(
            'select',
            'archetype',
            get_string('rolearchetype', 'local_cohortmanager'),
            $archetypeoptions
        );
        $mform->setDefault('archetype', '');

        if ($isedit) {
            $mform->hardFreeze(['shortname']);
        }
    }

    public function validation($data, $files)
    {
        global $DB;

        $errors = parent::validation($data, $files);

        $roleid = !empty($data['roleid']) ? (int) $data['roleid'] : 0;

        if (!$roleid) {
            if (empty($data['shortname'])) {
                $errors['shortname'] = get_string('required');
            } elseif ($DB->record_exists('role', ['shortname' => $data['shortname']])) {
                $errors['shortname'] = get_string('roleexists', 'local_cohortmanager');
            }

            if (empty($data['name'])) {
                $errors['name'] = get_string('required');
            }
        }

        return $errors;
    }

    public function set_data_for_dynamic_submission(): void
    {
        global $DB;

        $roleid = $this->optional_param('roleid', 0, PARAM_INT);

        $data = (object)[
            'roleid' => $roleid,
        ];

        if ($roleid) {
            $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
            $data->name = $role->name;
            $data->shortname = $role->shortname;
            $data->description = $role->description;
            $data->archetype = $role->archetype ?? '';
        }

        $this->set_data($data);
    }

    protected function get_page_url_for_dynamic_submission(): moodle_url
    {
        return new moodle_url('/local/cohortmanager/');
    }

    public function process_dynamic_submission()
    {
        global $DB;

        $data = $this->get_data();
        $roleid = !empty($data->roleid) ? (int) $data->roleid : 0;

        if ($roleid) {
            $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
            $role->name = $data->name;
            $role->description = $data->description;
            $role->archetype = !empty($data->archetype) ? $data->archetype : '';
            $DB->update_record('role', $role);

            return (object)[
                'success' => true,
                'message' => get_string('roleupdatedsuccessfully', 'local_cohortmanager'),
                'roleid' => $roleid,
            ];
        } else {
            if ($DB->record_exists('role', ['shortname' => $data->shortname])) {
                throw new moodle_exception('roleexists', 'local_cohortmanager');
            }

            $newroleid = create_role(
                $data->name,
                $data->shortname,
                $data->description,
                !empty($data->archetype) ? $data->archetype : ''
            );

            set_role_contextlevels($newroleid, [CONTEXT_USER]);

            return (object)[
                'success' => true,
                'message' => get_string('rolecreatedsuccessfully', 'local_cohortmanager'),
                'roleid' => $newroleid,
            ];
        }
    }
}
