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

        // NOTE: The Hero background IMAGE + overlay are SITE-LEVEL settings (Site
        // administration → Plugins → Blocks → Corporate welcome), not per-block,
        // so one uploaded image is shared by every user's dashboard. See
        // settings.php. They are intentionally not offered here.

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
}
