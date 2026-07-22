<?php
/**
 * Add/edit form for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/edztrackvideo/lib.php');

class mod_edztrackvideo_mod_form extends moodleform_mod {

    public function definition() {
        $mform = $this->_form;

        // Name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Intro / description.
        $this->standard_intro_elements();

        // ---- Video source section ----
        $mform->addElement('header', 'sourceheader', get_string('sourceheader', 'mod_edztrackvideo'));
        $mform->setExpanded('sourceheader', true);

        $hasserverdir = (bool)get_config('mod_edztrackvideo', 'videobasedir');
        $directenabled = (bool)get_config('mod_edztrackvideo', 'enable_directurl');

        $sourceoptions = [
            'youtube' => get_string('source_youtube', 'mod_edztrackvideo'),
            'vimeo'   => get_string('source_vimeo', 'mod_edztrackvideo'),
            'upload'  => get_string('source_upload', 'mod_edztrackvideo'),
        ];
        if ($hasserverdir) {
            $sourceoptions['serverfile'] = get_string('source_serverfile', 'mod_edztrackvideo');
        }
        if ($directenabled) {
            $sourceoptions['directurl'] = get_string('source_directurl', 'mod_edztrackvideo');
        }
        $mform->addElement('select', 'sourcetype', get_string('sourcetype', 'mod_edztrackvideo'), $sourceoptions);
        $mform->setDefault('sourcetype', 'youtube');
        $mform->addHelpButton('sourcetype', 'sourcetype', 'mod_edztrackvideo');

        // External URL — used by YouTube, Vimeo and Direct URL sources.
        $mform->addElement('text', 'externalurl', get_string('externalurl', 'mod_edztrackvideo'), ['size' => 80]);
        $mform->setType('externalurl', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('externalurl', 'externalurl', 'mod_edztrackvideo');
        // Hidden for the file-based sources (upload / serverfile).
        $mform->hideIf('externalurl', 'sourcetype', 'eq', 'upload');
        $mform->hideIf('externalurl', 'sourcetype', 'eq', 'serverfile');

        // Local file upload.
        $mform->addElement(
            'filemanager',
            'videofile',
            get_string('videofile', 'mod_edztrackvideo'),
            null,
            edztrackvideo_filemanager_options()
        );
        $mform->addHelpButton('videofile', 'videofile', 'mod_edztrackvideo');
        $mform->hideIf('videofile', 'sourcetype', 'neq', 'upload');

        // Server file (large videos placed in the admin folder).
        if ($hasserverdir) {
            $serverfiles = edztrackvideo_list_server_files();
            if (empty($serverfiles)) {
                $serverfiles = ['' => get_string('serverpath_none', 'mod_edztrackvideo')];
            }
            $mform->addElement('select', 'serverpath', get_string('serverpath', 'mod_edztrackvideo'), $serverfiles);
            $mform->setType('serverpath', PARAM_RAW);
            $mform->addHelpButton('serverpath', 'serverpath', 'mod_edztrackvideo');
            $mform->hideIf('serverpath', 'sourcetype', 'neq', 'serverfile');
        }

        // ---- Playback controls section ----
        $mform->addElement('header', 'playbackheader', get_string('playbackheader', 'mod_edztrackvideo'));

        $mform->addElement(
            'advcheckbox',
            'no_forward_seek_first_view',
            get_string('no_forward_seek_first_view', 'mod_edztrackvideo')
        );
        $mform->addHelpButton('no_forward_seek_first_view', 'no_forward_seek_first_view', 'mod_edztrackvideo');
        $mform->setDefault('no_forward_seek_first_view', 1);

        $mform->addElement('advcheckbox', 'allow_speed', get_string('allow_speed', 'mod_edztrackvideo'));
        $mform->addHelpButton('allow_speed', 'allow_speed', 'mod_edztrackvideo');
        $mform->setDefault('allow_speed', 0);

        $speedoptions = [
            '1.25' => '1.25x',
            '1.5'  => '1.5x',
            '2.0'  => '2.0x',
        ];
        $mform->addElement('select', 'max_speed', get_string('max_speed', 'mod_edztrackvideo'), $speedoptions);
        $mform->setDefault('max_speed', '1.5');
        $mform->setType('max_speed', PARAM_FLOAT);
        $mform->hideIf('max_speed', 'allow_speed', 'notchecked');

        // Standard course module elements (availability, completion, etc.).
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Custom completion rule fields.
     *
     * @return array of element names
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $group = [
            $mform->createElement(
                'advcheckbox',
                $this->get_suffixed_name('enable_completion_percent'),
                '',
                get_string('enablecompletionpercent', 'mod_edztrackvideo')
            ),
            $mform->createElement(
                'text',
                $this->get_suffixed_name('completion_threshold'),
                '',
                ['size' => 4]
            ),
        ];
        $mform->setType($this->get_suffixed_name('enable_completion_percent'), PARAM_INT);
        $mform->setDefault($this->get_suffixed_name('enable_completion_percent'), 0);
        $mform->setType($this->get_suffixed_name('completion_threshold'), PARAM_INT);
        $mform->setDefault($this->get_suffixed_name('completion_threshold'), 100);

        $mform->addGroup(
            $group,
            $this->get_suffixed_name('completion_thresholdgroup'),
            get_string('completionthreshold', 'mod_edztrackvideo'),
            [' '],
            false
        );
        $mform->addHelpButton(
            $this->get_suffixed_name('completion_thresholdgroup'),
            'completionthreshold',
            'mod_edztrackvideo'
        );
        $mform->disabledIf(
            $this->get_suffixed_name('completion_threshold'),
            $this->get_suffixed_name('enable_completion_percent'),
            'notchecked'
        );

        return [$this->get_suffixed_name('completion_thresholdgroup')];
    }

    protected function get_suffixed_name(string $fieldname): string {
        return $fieldname . $this->get_suffix();
    }

    public function completion_rule_enabled($data) {
        return (!empty($data[$this->get_suffixed_name('enable_completion_percent')])
            && $data[$this->get_suffixed_name('completion_threshold')] != 0);
    }

    /**
     * Load the existing uploaded file into the filemanager draft area.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        // Use the form context (module context when editing, course context when adding)
        // so an empty draft area is seeded for new activities too.
        $draftitemid = file_get_submitted_draft_itemid('videofile');
        file_prepare_draft_area(
            $draftitemid,
            $this->context->id,
            'mod_edztrackvideo',
            MOD_EDZTRACKVIDEO_FILEAREA,
            0,
            edztrackvideo_filemanager_options()
        );
        $defaultvalues['videofile'] = $draftitemid;
    }

    /**
     * Validate source-specific requirements.
     *
     * @param array $data
     * @param array $files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $sourcetype = $data['sourcetype'] ?? 'youtube';

        if ($sourcetype === 'youtube' || $sourcetype === 'vimeo') {
            $url = trim($data['externalurl'] ?? '');
            if ($url === '') {
                $errors['externalurl'] = get_string('error_url_required', 'mod_edztrackvideo');
            } else if ($sourcetype === 'youtube' && edztrackvideo_extract_youtube_id($url) === '') {
                $errors['externalurl'] = get_string('error_youtube_invalid', 'mod_edztrackvideo');
            } else if ($sourcetype === 'vimeo') {
                [$vid] = edztrackvideo_extract_vimeo_id($url);
                if ($vid === '') {
                    $errors['externalurl'] = get_string('error_vimeo_invalid', 'mod_edztrackvideo');
                }
            }
        } else if ($sourcetype === 'upload') {
            $draftitemid = $data['videofile'] ?? 0;
            $draftfiles = file_get_drafarea_files($draftitemid);
            if (empty($draftfiles) || empty($draftfiles->list)) {
                $errors['videofile'] = get_string('error_file_required', 'mod_edztrackvideo');
            }
        } else if ($sourcetype === 'serverfile') {
            $rel = trim($data['serverpath'] ?? '');
            if ($rel === '') {
                $errors['serverpath'] = get_string('error_serverfile_required', 'mod_edztrackvideo');
            } else if (edztrackvideo_resolve_server_file($rel) === false) {
                $errors['serverpath'] = get_string('error_serverfile_invalid', 'mod_edztrackvideo');
            }
        } else if ($sourcetype === 'directurl') {
            if (!get_config('mod_edztrackvideo', 'enable_directurl')) {
                $errors['sourcetype'] = get_string('error_directurl_disabled', 'mod_edztrackvideo');
            } else if (trim($data['externalurl'] ?? '') === '') {
                $errors['externalurl'] = get_string('error_directurl_required', 'mod_edztrackvideo');
            }
        }

        return $errors;
    }

    /**
     * Reset completion threshold when the rule is not active.
     *
     * @return stdClass|null
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->{$this->get_suffixed_name('completion')})
                && $data->{$this->get_suffixed_name('completion')} == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{$this->get_suffixed_name('enable_completion_percent')}) || !$autocompletion) {
                $data->{$this->get_suffixed_name('completion_threshold')} = 0;
            }
        }
        return $data;
    }
}
