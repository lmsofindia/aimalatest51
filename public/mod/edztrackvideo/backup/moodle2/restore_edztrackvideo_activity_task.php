<?php
/**
 * Restore activity task for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/edztrackvideo/backup/moodle2/restore_edztrackvideo_stepslib.php');

class restore_edztrackvideo_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_edztrackvideo_activity_structure_step('edztrackvideo_structure', 'edztrackvideo.xml'));
    }

    /**
     * Files areas this activity restores.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('edztrackvideo', ['intro'], 'edztrackvideo');
        return $contents;
    }

    /**
     * Link decoding rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('EDZTRACKVIDEOVIEWBYID', '/mod/edztrackvideo/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('EDZTRACKVIDEOINDEX', '/mod/edztrackvideo/index.php?id=$1', 'course');
        return $rules;
    }
}
