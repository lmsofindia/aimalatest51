<?php
/**
 * Restore steps for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

defined('MOODLE_INTERNAL') || die();

class restore_edztrackvideo_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('edztrackvideo', '/activity/edztrackvideo');
        if ($userinfo) {
            $paths[] = new restore_path_element('edztrackvideo_progress', '/activity/edztrackvideo/progresses/progress');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_edztrackvideo($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = isset($data->timecreated) ? $data->timecreated : time();
        $data->timemodified = isset($data->timemodified) ? $data->timemodified : time();

        $newitemid = $DB->insert_record('edztrackvideo', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_edztrackvideo_progress($data) {
        global $DB;

        $data = (object)$data;
        $data->edztrackvideoid = $this->get_new_parentid('edztrackvideo');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('edztrackvideo_progress', $data);
    }

    protected function after_execute() {
        // Restore uploaded video files into the new module context.
        $this->add_related_files('mod_edztrackvideo', 'videofile', null);
    }
}
