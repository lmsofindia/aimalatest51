<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\form;

use mod_edzsession\local\provider_manager;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Add/edit form for a meeting credential account.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class account_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;
        $isedit = !empty($this->_customdata['id']);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('account_name', 'mod_edzsession'), ['size' => 40]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('select', 'provider', get_string('meetingprovider', 'mod_edzsession'),
            provider_manager::meeting_provider_menu());
        $mform->setDefault('provider', 'zoom');

        $mform->addElement('text', 'accountid', get_string('account_accountid', 'mod_edzsession'), ['size' => 40]);
        $mform->setType('accountid', PARAM_RAW_TRIMMED);
        $mform->addRule('accountid', null, 'required', null, 'client');

        $mform->addElement('text', 'clientid', get_string('account_clientid', 'mod_edzsession'), ['size' => 40]);
        $mform->setType('clientid', PARAM_RAW_TRIMMED);
        $mform->addRule('clientid', null, 'required', null, 'client');

        // Secrets: on edit, leave blank to keep the stored value.
        $secretlabel = get_string('account_clientsecret', 'mod_edzsession');
        $mform->addElement('passwordunmask', 'clientsecret', $secretlabel);
        $mform->setType('clientsecret', PARAM_RAW_TRIMMED);
        if (!$isedit) {
            $mform->addRule('clientsecret', null, 'required', null, 'client');
        } else {
            $mform->addElement('static', 'clientsecrethint', '',
                get_string('account_secret_keepblank', 'mod_edzsession'));
        }

        $mform->addElement('passwordunmask', 'verificationtoken',
            get_string('account_verificationtoken', 'mod_edzsession'));
        $mform->setType('verificationtoken', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('verificationtoken', 'account_verificationtoken', 'mod_edzsession');

        $mform->addElement('advcheckbox', 'enabled', get_string('account_enabled', 'mod_edzsession'));
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons();
    }
}
