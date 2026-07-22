<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Quiz access rule: Ed Proctoring.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use quizaccess_edproctoring\helper\session_manager;
use quizaccess_edproctoring\helper\mobile_detector;
use quizaccess_edproctoring\helper\trust_score;

/**
 * Quiz access rule providing webcam-based proctoring.
 */
class quizaccess_edproctoring extends \mod_quiz\local\access_rule_base {

    /** @var \stdClass|null Proctoring settings for this quiz. Null = not enabled. */
    private ?\stdClass $procsettings = null;

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Return an instance of this rule for the given quiz, if it is applicable.
     *
     * @param \mod_quiz\quiz_settings $quizobj
     * @param int  $timenow   current Unix timestamp
     * @param bool $canignore whether the user can bypass access rules
     * @return \mod_quiz\local\access_rule_base|null rule instance, or null if not applicable
     */
    public static function make(\mod_quiz\quiz_settings $quizobj, $timenow, $canignore) {
        global $DB;

        // Global site-level kill switch — admin must enable plugin before it runs.
        if (!get_config('quizaccess_edproctoring', 'plugin_enabled')) {
            return null;
        }

        $settings = $DB->get_record('quizaccess_edproctoring',
            ['quizid' => $quizobj->get_quizid()]);

        if (!$settings || !$settings->enabled) {
            return null;
        }

        $rule = new self($quizobj, $timenow);
        $rule->procsettings = $settings;
        return $rule;
    }

    // -------------------------------------------------------------------------
    // Access checks
    // -------------------------------------------------------------------------

    /**
     * Block mobile / tablet devices when the setting requires it.
     *
     * @return array of error strings (empty = access allowed)
     */
    public function prevent_access() {
        global $USER;

        if (!$this->procsettings) {
            return [];
        }

        if ($this->procsettings->block_mobile && mobile_detector::is_mobile()) {
            return [get_string('mobileblockedmessage', 'quizaccess_edproctoring')];
        }

        return [];
    }

    /**
     * Message shown on the quiz view page. Students see the proctoring notice;
     * teachers/admins additionally get a button to the proctoring report.
     *
     * @return array of messages / HTML fragments
     */
    public function description() {
        $messages = [get_string('proctoringnotice', 'quizaccess_edproctoring')];

        $context = $this->quizobj->get_context();
        if (has_capability('quizaccess/edproctoring:viewreport', $context)) {
            $url = new \moodle_url('/mod/quiz/accessrule/edproctoring/report/index.php',
                ['cmid' => $this->quizobj->get_cmid()]);
            $messages[] = \html_writer::link($url,
                '📷 ' . get_string('viewreport', 'quizaccess_edproctoring'),
                ['class' => 'btn btn-outline-primary btn-sm']);
        }

        return $messages;
    }

    // -------------------------------------------------------------------------
    // Pre-flight check (consent + camera verification)
    // -------------------------------------------------------------------------

    /**
     * Pre-flight is required until the user has passed it for this quiz in this PHP session.
     *
     * We store a flag in $SESSION once notify_preflight_check_passed() fires so that
     * Moodle's attempt.php access-manager re-check does not loop back here.
     *
     * @param int|null $attemptid quiz attempt id (null on first attempt)
     * @return bool
     */
    public function is_preflight_check_required($attemptid) {
        global $SESSION;
        $key = 'edproctoring_preflight_' . $this->quizobj->get_quizid();
        if (!empty($SESSION->$key)) {
            return false;
        }
        return true;
    }

    /**
     * Add the consent checkbox and camera permission iframe to the pre-flight form.
     *
     * @param \mod_quiz\form\preflight_check_form $quizform the preflight form wrapper
     * @param \MoodleQuickForm                    $mform    the underlying MoodleQuickForm
     * @param int|null                            $attemptid attempt id (null on first attempt)
     */
    public function add_preflight_check_form_fields(
            \mod_quiz\form\preflight_check_form $quizform,
            \MoodleQuickForm $mform,
            $attemptid) {
        global $PAGE;

        $settings   = $this->procsettings;
        $quizname   = $this->quizobj->get_quiz()->name;
        $retention  = get_config('quizaccess_edproctoring', 'retention_days') ?: 90;
        $privacyurl = get_config('quizaccess_edproctoring', 'privacy_url') ?: '';

        // Render consent HTML via renderer.
        $renderer = $PAGE->get_renderer('quizaccess_edproctoring');
        $html = $renderer->render_consent_screen($quizname, $retention, $privacyurl);

        $mform->addElement('html', $html);

        if ($settings->require_consent) {
            $mform->addElement('checkbox', 'edp_consent',
                get_string('consentchecklabel', 'quizaccess_edproctoring'));
            $mform->addRule('edp_consent', null, 'required', null, 'client');
        }

        // Phase 2: camera preflight wizard (face-api.js). Not active yet — field
        // is pre-set to 1 so validation passes without the AMD module.
        $mform->addElement('hidden', 'edp_preflight_passed', 1);
        $mform->setType('edp_preflight_passed', PARAM_INT);
    }

    /**
     * Validate the pre-flight form submission.
     *
     * @param array    $data      form data
     * @param array    $files     uploaded files
     * @param array    $errors    errors already accumulated by other rules
     * @param int|null $attemptid attempt id (null on first attempt)
     * @return array validation errors keyed by element name
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        if ($this->procsettings->require_consent && empty($data['edp_consent'])) {
            $errors['edp_consent'] = get_string('mustconsent', 'quizaccess_edproctoring');
        }

        // Phase 2: camera preflight gate removed until face-api.js AMD module is compiled.
        // edp_preflight_passed is pre-set to 1 in the form so this check is not needed yet.

        return $errors;
    }

    /**
     * Called after pre-flight validation passes — record consent and create the proctoring session.
     *
     * @param int $attemptid quiz_attempts.id
     */
    public function notify_preflight_check_passed($attemptid) {
        global $USER, $SESSION;

        // Mark preflight as passed for this quiz in the current PHP session.
        // This prevents is_preflight_check_required() from returning true again
        // when attempt.php re-runs the access-manager checks after the redirect.
        $key = 'edproctoring_preflight_' . $this->quizobj->get_quizid();
        $SESSION->$key = true;

        // $attemptid is null on the very first attempt (attempt record does not exist yet).
        // In that case, skip session creation here — setup_attempt_page() will create it
        // once Moodle has a real attempt ID.
        if ($attemptid === null) {
            return;
        }

        $sessionid = session_manager::get_or_create(
            $this->quizobj->get_quizid(),
            $this->quizobj->get_cmid(),
            $USER->id,
            $attemptid
        );

        if ($this->procsettings->require_consent) {
            session_manager::record_consent($sessionid);
        }
    }

    // -------------------------------------------------------------------------
    // In-quiz: inject AMD proctoring session on the quiz attempt page
    // -------------------------------------------------------------------------

    /**
     * Called by the access manager when the attempt page is being set up —
     * injects the AMD proctoring session. This hook is also invoked on the
     * summary and review pages, so we guard on pagetype.
     *
     * @param \moodle_page $page the page object to initialise.
     */
    public function setup_attempt_page($page) {
        global $DB, $USER;

        // Only run on the live attempt page, not summary/review.
        if ($page->pagetype !== 'mod-quiz-attempt') {
            return;
        }

        $attemptid = optional_param('attempt', 0, PARAM_INT);
        if (!$attemptid) {
            return;
        }

        $session = $DB->get_record('quizaccess_edproctoring_session',
            ['attemptid' => $attemptid]);

        // Session may not exist yet if notify_preflight_check_passed received a null
        // attempt ID (first attempt). Create it now that we have a real attempt ID.
        if (!$session) {
            $sessionid = session_manager::get_or_create(
                $this->quizobj->get_quizid(),
                $this->quizobj->get_cmid(),
                $USER->id,
                (int) $attemptid
            );
            if ($this->procsettings->require_consent) {
                session_manager::record_consent($sessionid);
            }
            $session = $DB->get_record('quizaccess_edproctoring_session', ['id' => $sessionid]);
        }

        if (!$session) {
            return;
        }

        $settings = $this->procsettings;

        // Note: js_call_amd JSON-encodes each array element as a SEPARATE positional
        // argument, so the whole config map must be wrapped as one parameter.
        $page->requires->js_call_amd('quizaccess_edproctoring/proctoring_session', 'init', [[
            'sessionId'            => (int)  $session->id,
            'cmid'                 => (int)  $this->quizobj->get_cmid(),
            'captureInterval'      => (int)  $settings->capture_interval,
            'gracePeriod'          => (int)  $settings->grace_period,
            'enableFaceDetect'     => (bool) $settings->enable_face_detect,
            'requireFullscreen'    => (bool) $settings->require_fullscreen,
            'detectTabs'           => (bool) $settings->detect_tabs,
            'autoSubmit'           => (bool) $settings->auto_submit,
            'autoSubmitThreshold'  => (int)  $settings->auto_submit_threshold,
        ]]);
    }

    // -------------------------------------------------------------------------
    // Attempt completion
    // -------------------------------------------------------------------------

    /**
     * Called when the current attempt is finished (submitted or timed out).
     * Marks the proctoring session complete and triggers trust score calculation.
     */
    public function current_attempt_finished() {
        global $DB, $USER;

        // Locate the most recent attempt for this user/quiz.
        $latestAttempt = $DB->get_record_sql(
            'SELECT id FROM {quiz_attempts} WHERE quiz = ? AND userid = ? ORDER BY id DESC LIMIT 1',
            [$this->quizobj->get_quizid(), $USER->id]
        );

        if (!$latestAttempt) {
            return;
        }

        $session = $DB->get_record('quizaccess_edproctoring_session',
            ['attemptid' => $latestAttempt->id]);

        if ($session && $session->status === 'active') {
            session_manager::mark_completed($session->id);
        }
    }

    // -------------------------------------------------------------------------
    // Quiz edit form: settings fields
    // -------------------------------------------------------------------------

    /**
     * Add the proctoring configuration fields to the quiz settings form.
     *
     * @param \mod_quiz_mod_form $quizform the quiz mod form wrapper
     * @param \MoodleQuickForm   $mform    the underlying MoodleQuickForm
     */
    public static function add_settings_form_fields(\mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {

        $mform->addElement('header', 'edproctoring_header',
            get_string('pluginname', 'quizaccess_edproctoring'));

        // Master switch.
        $mform->addElement('selectyesno', 'edproctoring_enabled',
            get_string('enabled', 'quizaccess_edproctoring'));
        $mform->setDefault('edproctoring_enabled', 0);

        // Capture interval.
        $mform->addElement('text', 'edproctoring_capture_interval',
            get_string('captureinterval', 'quizaccess_edproctoring'), ['size' => 5]);
        $mform->setType('edproctoring_capture_interval', PARAM_INT);
        $mform->setDefault('edproctoring_capture_interval',
            get_config('quizaccess_edproctoring', 'default_capture_interval') ?: 30);
        $mform->addRule('edproctoring_capture_interval', null, 'numeric');
        $mform->disabledIf('edproctoring_capture_interval', 'edproctoring_enabled', 'eq', 0);

        // Require consent.
        $mform->addElement('selectyesno', 'edproctoring_require_consent',
            get_string('requireconsent', 'quizaccess_edproctoring'));
        $mform->setDefault('edproctoring_require_consent', 1);
        $mform->disabledIf('edproctoring_require_consent', 'edproctoring_enabled', 'eq', 0);

        // Block mobile.
        $mform->addElement('selectyesno', 'edproctoring_block_mobile',
            get_string('blockmobile', 'quizaccess_edproctoring'));
        $sitedefault = get_config('quizaccess_edproctoring', 'block_mobile_default') ?? 1;
        $mform->setDefault('edproctoring_block_mobile', $sitedefault);
        $mform->disabledIf('edproctoring_block_mobile', 'edproctoring_enabled', 'eq', 0);

        // Face detection (browser-side).
        $mform->addElement('selectyesno', 'edproctoring_enable_face_detect',
            get_string('enablefacedetect', 'quizaccess_edproctoring'));
        $mform->setDefault('edproctoring_enable_face_detect', 1);
        $mform->disabledIf('edproctoring_enable_face_detect', 'edproctoring_enabled', 'eq', 0);

        // Face recognition (Phase 2 — server-side).
        $mform->addElement('selectyesno', 'edproctoring_enable_face_recog',
            get_string('enablefacerecog', 'quizaccess_edproctoring'));
        $mform->setDefault('edproctoring_enable_face_recog', 0);
        $mform->disabledIf('edproctoring_enable_face_recog', 'edproctoring_enabled', 'eq', 0);

        // Fullscreen enforcement.
        $mform->addElement('selectyesno', 'edproctoring_require_fullscreen',
            get_string('requirefullscreen', 'quizaccess_edproctoring'));
        $mform->setDefault('edproctoring_require_fullscreen', 0);
        $mform->disabledIf('edproctoring_require_fullscreen', 'edproctoring_enabled', 'eq', 0);

        // Tab detection.
        $mform->addElement('selectyesno', 'edproctoring_detect_tabs',
            get_string('detecttabs', 'quizaccess_edproctoring'));
        $mform->setDefault('edproctoring_detect_tabs', 1);
        $mform->disabledIf('edproctoring_detect_tabs', 'edproctoring_enabled', 'eq', 0);

        // Grace period (seconds before FACE_ABSENT is flagged).
        $mform->addElement('text', 'edproctoring_grace_period',
            get_string('graceperiod', 'quizaccess_edproctoring'), ['size' => 5]);
        $mform->setType('edproctoring_grace_period', PARAM_INT);
        $mform->setDefault('edproctoring_grace_period', 5);
        $mform->disabledIf('edproctoring_grace_period', 'edproctoring_enabled', 'eq', 0);

        // Auto-submit on too many violations.
        $mform->addElement('selectyesno', 'edproctoring_auto_submit',
            get_string('autosubmit', 'quizaccess_edproctoring'));
        $mform->setDefault('edproctoring_auto_submit', 0);
        $mform->disabledIf('edproctoring_auto_submit', 'edproctoring_enabled', 'eq', 0);

        $mform->addElement('text', 'edproctoring_auto_submit_threshold',
            get_string('autosubmitthreshold', 'quizaccess_edproctoring'), ['size' => 5]);
        $mform->setType('edproctoring_auto_submit_threshold', PARAM_INT);
        $mform->setDefault('edproctoring_auto_submit_threshold', 5);
        $mform->disabledIf('edproctoring_auto_submit_threshold', 'edproctoring_auto_submit', 'eq', 0);

        // Notify teacher when trust score drops below threshold.
        $mform->addElement('selectyesno', 'edproctoring_notify_teacher',
            get_string('notifyteacher', 'quizaccess_edproctoring'));
        $mform->setDefault('edproctoring_notify_teacher', 1);
        $mform->disabledIf('edproctoring_notify_teacher', 'edproctoring_enabled', 'eq', 0);

        $mform->addElement('text', 'edproctoring_notify_threshold',
            get_string('notifythreshold', 'quizaccess_edproctoring'), ['size' => 5]);
        $mform->setType('edproctoring_notify_threshold', PARAM_INT);
        $sitenotify = get_config('quizaccess_edproctoring', 'notify_threshold') ?: 60;
        $mform->setDefault('edproctoring_notify_threshold', $sitenotify);
        $mform->disabledIf('edproctoring_notify_threshold', 'edproctoring_notify_teacher', 'eq', 0);
    }

    /**
     * Validate quiz settings form fields.
     *
     * @param array $errors  current validation errors
     * @param array $data    form data
     * @param array $files   uploaded files
     * @param object $quizobj quiz edit form object
     * @return array updated errors
     */
    public static function validate_settings_form_fields(array $errors, array $data, $files, $quizobj) {

        if (!empty($data['edproctoring_enabled'])) {
            $interval = $data['edproctoring_capture_interval'] ?? 0;
            if ($interval < 10) {
                $errors['edproctoring_capture_interval'] =
                    get_string('captureintervaltoolow', 'quizaccess_edproctoring');
            }
            $grace = $data['edproctoring_grace_period'] ?? 0;
            if ($grace < 1 || $grace > 60) {
                $errors['edproctoring_grace_period'] =
                    get_string('graceperiodrange', 'quizaccess_edproctoring');
            }
        }

        return $errors;
    }

    /**
     * Save per-quiz proctoring settings.
     *
     * @param \stdClass $quiz the quiz data (including edproctoring_* fields)
     */
    public static function save_settings($quiz) {
        global $DB;

        $existing = $DB->get_record('quizaccess_edproctoring', ['quizid' => $quiz->id]);
        $enabled  = !empty($quiz->edproctoring_enabled) ? 1 : 0;

        $record = (object)[
            'quizid'                => $quiz->id,
            'enabled'               => $enabled,
            'capture_interval'      => $quiz->edproctoring_capture_interval ?? 30,
            'require_fullscreen'    => $quiz->edproctoring_require_fullscreen ?? 0,
            'detect_tabs'           => $quiz->edproctoring_detect_tabs ?? 1,
            'enable_face_detect'    => $quiz->edproctoring_enable_face_detect ?? 1,
            'enable_face_recog'     => $quiz->edproctoring_enable_face_recog ?? 0,
            'base_img_uploader'     => 0,
            'require_consent'       => $quiz->edproctoring_require_consent ?? 1,
            'auto_submit'           => $quiz->edproctoring_auto_submit ?? 0,
            'auto_submit_threshold' => $quiz->edproctoring_auto_submit_threshold ?? 5,
            'grace_period'          => $quiz->edproctoring_grace_period ?? 5,
            'block_mobile'          => $quiz->edproctoring_block_mobile ?? 1,
            'notify_teacher'        => $quiz->edproctoring_notify_teacher ?? 1,
            'notify_threshold'      => $quiz->edproctoring_notify_threshold ?? 60,
            'timemodified'          => time(),
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('quizaccess_edproctoring', $record);
        } else {
            $record->timecreated = time();
            $DB->insert_record('quizaccess_edproctoring', $record);
        }
    }

    /**
     * Delete quiz settings when a quiz is deleted.
     *
     * @param \stdClass $quiz quiz record
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_edproctoring', ['quizid' => $quiz->id]);
    }

    /**
     * SQL to load this rule's settings into the quiz object in one query.
     * The aliases match the settings-form element names, which is how Moodle
     * repopulates the quiz settings form with previously saved values.
     *
     * @param int $quizid quiz id
     * @return array [fields, joins, params]
     */
    public static function get_settings_sql($quizid) {
        $fields = '
            edp.enabled               AS edproctoring_enabled,
            edp.capture_interval      AS edproctoring_capture_interval,
            edp.require_fullscreen    AS edproctoring_require_fullscreen,
            edp.detect_tabs           AS edproctoring_detect_tabs,
            edp.enable_face_detect    AS edproctoring_enable_face_detect,
            edp.enable_face_recog     AS edproctoring_enable_face_recog,
            edp.require_consent       AS edproctoring_require_consent,
            edp.auto_submit           AS edproctoring_auto_submit,
            edp.auto_submit_threshold AS edproctoring_auto_submit_threshold,
            edp.grace_period          AS edproctoring_grace_period,
            edp.block_mobile          AS edproctoring_block_mobile,
            edp.notify_teacher        AS edproctoring_notify_teacher,
            edp.notify_threshold      AS edproctoring_notify_threshold';
        $joins = 'LEFT JOIN {quizaccess_edproctoring} edp ON edp.quizid = quiz.id';
        return [$fields, $joins, []];
    }
}
