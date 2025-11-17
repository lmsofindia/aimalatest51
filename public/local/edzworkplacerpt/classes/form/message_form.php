<?php
namespace local_edzworkplacerpt\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class message_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'subject', get_string('message_subject', 'local_edzworkplacerpt'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addElement('editor', 'message', get_string('message_body', 'local_edzworkplacerpt'));
        $this->add_action_buttons(true, get_string('sendmessage', 'local_edzworkplacerpt'));
    }
}
