<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Restore support: restores per-quiz proctoring settings from quiz backups.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restores the quizaccess_edproctoring settings row for the quiz.
 */
class restore_quizaccess_edproctoring_subplugin extends restore_subplugin {

    /**
     * Paths this subplugin handles inside the quiz element.
     *
     * @return restore_path_element[]
     */
    protected function define_quiz_subplugin_structure() {
        return [
            new restore_path_element('quizaccess_edproctoring_settings',
                $this->get_pathfor('/edproctoring_settings')),
        ];
    }

    /**
     * Process one settings row.
     *
     * @param array $data parsed XML data
     */
    public function process_quizaccess_edproctoring_settings($data) {
        global $DB;

        $data = (object) $data;
        $data->quizid       = $this->get_new_parentid('quiz');
        $data->timecreated  = time();
        $data->timemodified = time();

        // Idempotent: a duplicate restore must not create a second row.
        if (!$DB->record_exists('quizaccess_edproctoring', ['quizid' => $data->quizid])) {
            $DB->insert_record('quizaccess_edproctoring', $data);
        }
    }
}
