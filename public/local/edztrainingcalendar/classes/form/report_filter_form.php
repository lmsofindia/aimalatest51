<?php

namespace local_edztrainingcalendar\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class report_filter_form extends \moodleform
{
    public function definition()
    {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'local_edztrainingcalendar'));
        $mform->addElement('date_selector', 'enddate', get_string('enddate', 'local_edztrainingcalendar'));

        // Build course list using Moodle API get_courses()
        $courses = get_courses(); // returns array keyed by id
        $courselist = ['' => get_string('allcourses', 'local_edztrainingcalendar')];

        foreach ($courses as $c) {
            // skip frontpage or system course if you want
            if (empty($c->id) || $c->id == SITEID) {
                continue;
            }
            // use shortname as key (your report expects shortname)
            $courselist[$c->shortname] = format_string($c->fullname);
        }

        $mform->addElement('autocomplete', 'courseshortname', get_string('course', 'local_edztrainingcalendar'), $courselist, ['noselectionstring' => get_string('allcourses', 'local_edztrainingcalendar')]);

        $mform->addElement('text', 'search', get_string('search', 'local_edztrainingcalendar'));
        $mform->setType('search', PARAM_RAW_TRIMMED);

        $mform->addElement('submit', 'filter', get_string('filter', 'local_edztrainingcalendar'));
    }
}
