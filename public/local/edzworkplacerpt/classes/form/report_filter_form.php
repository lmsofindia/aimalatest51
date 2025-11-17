<?php

namespace local_edzworkplacerpt\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class report_filter_form extends \moodleform
{
    public function definition()
    {
        global $CFG;
        $mform = $this->_form;

        // Start date + enable checkbox
        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'local_edzworkplacerpt'));
        $mform->addElement('advcheckbox', 'use_startdate', get_string('use_startdate', 'local_edzworkplacerpt'), '', [], [0, 1]);
        $mform->setDefault('use_startdate', 0);

        // End date + enable checkbox
        $mform->addElement('date_selector', 'enddate', get_string('enddate', 'local_edzworkplacerpt'));
        $mform->addElement('advcheckbox', 'use_enddate', get_string('use_enddate', 'local_edzworkplacerpt'), '', [], [0, 1]);
        $mform->setDefault('use_enddate', 0);

        // Levels
        $levels = [
            -1 => get_string('level_all', 'local_edzworkplacerpt'),
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5'
        ];
        $mform->addElement('select', 'level', get_string('levels', 'local_edzworkplacerpt'), $levels);
        $mform->setDefault('level', 1);

        // Categories
        $categories = [0 => 'Select Category'];
        $cats = $this->get_all_categories();
        foreach ($cats as $cat) {
            $categories[$cat->id] = $cat->name;
        }
        $mform->addElement('select', 'category', get_string('category', 'local_edzworkplacerpt'), $categories);

        // Courses (excluding id=1)
        $courseoptions = [0 => 'Select Course'];
        $courses = $this->get_courses();
        foreach ($courses as $c) {
            $courseoptions[$c->id] = $c->fullname;
        }
        $mform->addElement('select', 'course', get_string('course', 'local_edzworkplacerpt'), $courseoptions);

        // Department text
        $mform->addElement('text', 'department', get_string('department', 'local_edzworkplacerpt'));
        $mform->setType('department', PARAM_TEXT);

        // Show-only radio group
        $mform->addElement('html', '<div class="form-group"><label>' . get_string('showonly', 'local_edzworkplacerpt') . '</label>');
        $mform->addElement('radio', 'showonly', '', get_string('show_all', 'local_edzworkplacerpt'), 'all');
        $mform->addElement('radio', 'showonly', '', get_string('show_completed', 'local_edzworkplacerpt'), 'completed');
        $mform->addElement('radio', 'showonly', '', get_string('show_notcompleted', 'local_edzworkplacerpt'), 'notcompleted');
        $mform->addElement('html', '</div>');
        $mform->setDefault('showonly', 'all');

        // Submit
        $this->add_action_buttons(false, get_string('submit', 'local_edzworkplacerpt'));
    }

    protected function get_all_categories()
    {
        global $DB;
        return $DB->get_records('course_categories', null, 'name', 'id,name');
    }

    protected function get_courses()
    {
        global $DB;
        // Exclude front page (id = 1)
        return $DB->get_records_select('course', 'id <> :frontpage', ['frontpage' => 1], 'fullname', 'id,fullname');
    }
}
