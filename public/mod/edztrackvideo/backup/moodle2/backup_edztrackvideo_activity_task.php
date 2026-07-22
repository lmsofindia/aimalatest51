<?php
/**
 * Backup activity task for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/edztrackvideo/backup/moodle2/backup_edztrackvideo_stepslib.php');

class backup_edztrackvideo_activity_task extends backup_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new backup_edztrackvideo_activity_structure_step('edztrackvideo_structure', 'edztrackvideo.xml'));
    }

    /**
     * Encode links to this activity into backup-portable form.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '/');

        // Link to view by module id.
        $search = '/(' . $base . '\/mod\/edztrackvideo\/view\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@EDZTRACKVIDEOVIEWBYID*$2@$', $content);

        // Link to course index.
        $search = '/(' . $base . '\/mod\/edztrackvideo\/index\.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@EDZTRACKVIDEOINDEX*$2@$', $content);

        return $content;
    }
}
