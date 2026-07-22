<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Backup support: includes per-quiz proctoring settings in quiz backups.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backs up the quizaccess_edproctoring settings row for the quiz.
 * Session/snapshot/violation user data is intentionally NOT backed up.
 */
class backup_quizaccess_edproctoring_subplugin extends backup_subplugin {

    /**
     * Define the structure added to the quiz element.
     *
     * @return backup_subplugin_element
     */
    protected function define_quiz_subplugin_structure() {
        $subplugin        = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());

        $settings = new backup_nested_element('edproctoring_settings', null, [
            'enabled', 'capture_interval', 'require_fullscreen', 'detect_tabs',
            'enable_face_detect', 'enable_face_recog', 'base_img_uploader',
            'require_consent', 'auto_submit', 'auto_submit_threshold',
            'grace_period', 'block_mobile', 'notify_teacher', 'notify_threshold',
        ]);

        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($settings);

        $settings->set_source_table('quizaccess_edproctoring',
            ['quizid' => backup::VAR_ACTIVITYID]);

        return $subplugin;
    }
}
