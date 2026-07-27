<?php
defined('MOODLE_INTERNAL') || die();

class block_corpwelcome_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $CFG;

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // ── Block style (dark / light) ────────────────────────────────────────
        $themeopts = [
            'dark'  => get_string('themeopt_dark',  'block_corpwelcome'),
            'light' => get_string('themeopt_light', 'block_corpwelcome'),
            'hero'  => get_string('themeopt_hero',  'block_corpwelcome'),
        ];
        $mform->addElement('select', 'config_blocktheme',
            get_string('configblocktheme', 'block_corpwelcome'), $themeopts);
        $mform->setDefault('config_blocktheme', 'dark');
        $mform->setType('config_blocktheme', PARAM_ALPHA);
        $mform->addHelpButton('config_blocktheme', 'configblocktheme', 'block_corpwelcome');

        // ── Background colour (applies to dark theme only) ────────────────────
        $mform->addElement('text', 'config_bgcolor',
            get_string('configbgcolor', 'block_corpwelcome'));
        $mform->setDefault('config_bgcolor', '#4f46e5');
        $mform->setType('config_bgcolor', PARAM_TEXT);
        $mform->addHelpButton('config_bgcolor', 'configbgcolor', 'block_corpwelcome');

        // ── Background image (Hero style only) ────────────────────────────────
        // When an image is uploaded it replaces the flat accent colour behind
        // the hero header. The colour above is still used as the fallback /
        // load-in background colour behind the image.
        $mform->addElement('filemanager', 'config_backgroundimage',
            get_string('configbackgroundimage', 'block_corpwelcome'), null,
            ['subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => 2097152, 'accepted_types' => ['web_image']]);
        $mform->addHelpButton('config_backgroundimage', 'configbackgroundimage', 'block_corpwelcome');
        // Only meaningful for the Hero style — hide otherwise to avoid confusion.
        $mform->hideIf('config_backgroundimage', 'config_blocktheme', 'neq', 'hero');

        // ── Overlay darkness (readability scrim over the image) ───────────────
        $overlayopts = [
            '0'  => get_string('overlay0',  'block_corpwelcome'),
            '20' => get_string('overlay20', 'block_corpwelcome'),
            '40' => get_string('overlay40', 'block_corpwelcome'),
            '60' => get_string('overlay60', 'block_corpwelcome'),
            '80' => get_string('overlay80', 'block_corpwelcome'),
        ];
        $mform->addElement('select', 'config_overlaydarkness',
            get_string('configoverlaydarkness', 'block_corpwelcome'), $overlayopts);
        $mform->setDefault('config_overlaydarkness', '40');
        $mform->setType('config_overlaydarkness', PARAM_INT);
        $mform->addHelpButton('config_overlaydarkness', 'configoverlaydarkness', 'block_corpwelcome');
        $mform->hideIf('config_overlaydarkness', 'config_blocktheme', 'neq', 'hero');

        // ── Monthly completion goal ───────────────────────────────────────────
        $mform->addElement('text', 'config_monthlygoal',
            get_string('configmonthlygoal', 'block_corpwelcome'));
        $mform->setDefault('config_monthlygoal', 5);
        $mform->setType('config_monthlygoal', PARAM_INT);
        $mform->addHelpButton('config_monthlygoal', 'configmonthlygoal', 'block_corpwelcome');

        // ── Category filter pills ─────────────────────────────────────────────
        require_once($CFG->dirroot . '/course/lib.php');
        $allcats = core_course_category::make_categories_list();
        $mform->addElement(
            'autocomplete',
            'config_categories',
            get_string('configcategories', 'block_corpwelcome'),
            $allcats,
            [
                'multiple'        => true,
                'noselectionstring' => get_string('allcategories', 'block_corpwelcome'),
            ]
        );
        $mform->setType('config_categories', PARAM_INT);
        $mform->addHelpButton('config_categories', 'configcategories', 'block_corpwelcome');
    }

    /**
     * Prepare the background-image draft file area so the currently saved image
     * shows in the form on re-edit — and, crucially, so an untouched image is
     * preserved on save instead of being wiped by an empty draft area.
     */
    public function set_data($defaults) {
        parent::set_data($defaults);

        if (empty($this->block->context)) {
            return;
        }
        $draftitemid = file_get_submitted_draft_itemid('config_backgroundimage');
        file_prepare_draft_area(
            $draftitemid,
            $this->block->context->id,
            'block_corpwelcome',
            'backgroundimage',
            0,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['web_image']]
        );
        if ($this->_form->elementExists('config_backgroundimage')) {
            $this->_form->getElement('config_backgroundimage')->setValue($draftitemid);
        }
    }
}
