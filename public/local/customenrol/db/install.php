<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_customenrol_install()
{
    global $DB, $CFG;

    require_once($CFG->dirroot . '/customfield/lib.php');

    $categoryname = 'Custom Enrol Fields';
    $category = $DB->get_record('customfield_category', [
        'name' => $categoryname,
        'component' => 'core_course'
    ]);

    if (!$category) {
        $category = new stdClass();
        $category->name = $categoryname;
        $category->component = 'core_course';
        $category->area = 'course';
        $category->contextid = \context_system::instance()->id;
        $category->timecreated = time();
        $category->timemodified = time();
        $categoryid = $DB->insert_record('customfield_category', $category);
    } else {
        $categoryid = $category->id;
    }

    // Helper to create a custom field.
    $createfield = function ($shortname, $name, $type, $configdata) use ($DB, $categoryid) {
        if (!$DB->record_exists('customfield_field', ['shortname' => $shortname, 'categoryid' => $categoryid])) {
            $field = new stdClass();
            $field->shortname = $shortname;
            $field->name = $name;
            $field->type = $type;
            $field->categoryid = $categoryid;
            $field->sortorder = 0;
            $field->description = '';
            $field->descriptionformat = FORMAT_HTML;
            $field->configdata = json_encode($configdata);
            $field->timecreated = time();
            $field->timemodified = time();
            $DB->insert_record('customfield_field', $field);
        }
    };

    // Fields with safe defaults.
    $createfield('previewvideo', 'Preview Video', 'textarea', [
        'defaultvalue' => '',
        'uniquevalues' => 0,
        'locked' => 0,
        'visibility' => 2,
        'required' => 0
    ]);

    $createfield('whatyouwilllearn', 'What You Will Learn', 'textarea', [
        'defaultvalue' => '',
        'uniquevalues' => 0,
        'locked' => 0,
        'visibility' => 2,
        'required' => 0
    ]);

    $createfield('compliance', 'Compliance', 'checkbox', [
        'checkbydefault' => 0, // for checkbox fields
        'uniquevalues' => 0,
        'locked' => 0,
        'visibility' => 2,
        'required' => 0
    ]);

    $createfield('learning_level', 'Learning Level', 'select', [
        'defaultvalue' => 'Beginner',
        'uniquevalues' => 0,
        'locked' => 0,
        'visibility' => 2,
        'options' => "Beginner\nAdvanced\nExpert",
        'required' => 0
    ]);

    $createfield('skills', 'Skills', 'text', [
        'defaultvalue' => '',
        'uniquevalues' => 0,
        'locked' => 0,
        'visibility' => 2,
        'displaysize' => 50,
        'maxlength' => 1280,
        'ispassword' => 0,
        'required' => 0
    ]);

    $createfield('duration', 'Duration', 'text', [
        'defaultvalue' => '',
        'uniquevalues' => 0,
        'locked' => 0,
        'visibility' => 2,
        'displaysize' => 50,
        'maxlength' => 1280,
        'ispassword' => 0,
        'required' => 0
    ]);
}
