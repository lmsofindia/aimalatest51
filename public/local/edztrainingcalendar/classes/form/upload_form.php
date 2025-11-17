<?php

namespace local_edztrainingcalendar\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class upload_form extends \moodleform
{
    public function definition()
    {
        $mform = $this->_form;
        $mform->addElement(
            'filepicker',
            'csvfile',
            get_string('uploadcsv', 'local_edztrainingcalendar'),
            null,
            ['accepted_types' => ['.csv'], 'maxfiles' => 1]
        );
        $mform->addHelpButton('csvfile', 'uploaddesc', 'local_edztrainingcalendar');
        $mform->addElement('checkbox', 'hasheader', get_string('hasheader', 'local_edztrainingcalendar'));
        $mform->setDefault('hasheader', 1);
        $mform->addElement(
            'static',
            'sample',
            get_string('samplecsv', 'local_edztrainingcalendar'),
            '<a href="' . $this->_customdata['sampleurl'] . '">Download sample CSV</a>'
        );
        $this->add_action_buttons(true, get_string('preview', 'local_edztrainingcalendar'));
    }
}
