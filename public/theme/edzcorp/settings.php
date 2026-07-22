<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp theme admin settings.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // The settings page name MUST be 'themesetting' + pluginname so Moodle's
    // admin tree links to admin/settings.php?section=themesettingedzcorp.
    if (class_exists('theme_boost_admin_settingspage_tabs')) {
        $settings = new theme_boost_admin_settingspage_tabs(
            'themesettingedzcorp',
            get_string('configtitle', 'theme_edzcorp')
        );
        $use_tabs = true;
    } else {
        $settings = new admin_settingpage(
            'themesettingedzcorp',
            get_string('configtitle', 'theme_edzcorp')
        );
        $use_tabs = false;
    }

    // =========================================================================
    // TAB 1: General (logos / branding)
    // =========================================================================
    $page = new admin_settingpage('theme_edzcorp_general', get_string('generalsettings', 'theme_edzcorp'));

    $page->add(new admin_setting_heading(
        'theme_edzcorp/generalheading',
        get_string('generalsettings', 'theme_edzcorp'),
        get_string('generalsettingsdesc', 'theme_edzcorp')
    ));

    // Full logo — used on the login page.
    $setting = new admin_setting_configstoredfile(
        'theme_edzcorp/logo',
        get_string('logo', 'theme_edzcorp'),
        get_string('logodesc', 'theme_edzcorp'),
        'logo', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.svg', '.webp']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Sidebar logo — shown in the nav panel.
    $setting = new admin_setting_configstoredfile(
        'theme_edzcorp/sidebarlogo',
        get_string('sidebarlogo', 'theme_edzcorp'),
        get_string('sidebarlogodesc', 'theme_edzcorp'),
        'sidebarlogo', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.svg', '.webp']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Mobile logo.
    $setting = new admin_setting_configstoredfile(
        'theme_edzcorp/mobilelogo',
        get_string('mobilelogo', 'theme_edzcorp'),
        get_string('mobilelogodesc', 'theme_edzcorp'),
        'mobilelogo', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.svg', '.webp']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    if ($use_tabs) {
        $settings->add($page);
    } else {
        foreach ($page->settings as $s) { $settings->add($s); }
    }

    // =========================================================================
    // TAB 2: Colours
    // =========================================================================
    $page = new admin_settingpage('theme_edzcorp_colors', get_string('colorsettings', 'theme_edzcorp'));

    $page->add(new admin_setting_heading(
        'theme_edzcorp/colorheading',
        get_string('colorsettings', 'theme_edzcorp'),
        get_string('colorsettingsdesc', 'theme_edzcorp')
    ));

    // Primary colour — Bootstrap $primary, active sidebar items, buttons.
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/primarycolor',
        get_string('primarycolor', 'theme_edzcorp'),
        get_string('primarycolordesc', 'theme_edzcorp'),
        '#6366f1'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Accent colour — Bootstrap $secondary, badges, secondary buttons.
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/accentcolor',
        get_string('accentcolor', 'theme_edzcorp'),
        get_string('accentcolordesc', 'theme_edzcorp'),
        '#8b5cf6'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Sidebar background colour.
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/sidebarbgcolor',
        get_string('sidebarbgcolor', 'theme_edzcorp'),
        get_string('sidebarbgcolordesc', 'theme_edzcorp'),
        '#1a1a2e'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Sidebar text / icon colour.
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/sidebartextcolor',
        get_string('sidebartextcolor', 'theme_edzcorp'),
        get_string('sidebartextcolordesc', 'theme_edzcorp'),
        '#c8cde4'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Sidebar hover colour — background of links on hover.
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/sidebarhovercolor',
        get_string('sidebarhovercolor', 'theme_edzcorp'),
        get_string('sidebarhovercolordesc', 'theme_edzcorp'),
        '#2d2d4a'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Sidebar active item colour — independent of the site primary colour.
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/sidebaractivecolor',
        get_string('sidebaractivecolor', 'theme_edzcorp'),
        get_string('sidebaractivecolordesc', 'theme_edzcorp'),
        '#6366f1'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Sidebar active-link highlight style.
    $setting = new admin_setting_configselect(
        'theme_edzcorp/sidebaractivestyle',
        get_string('sidebaractivestyle', 'theme_edzcorp'),
        get_string('sidebaractiveStyledesc', 'theme_edzcorp'),
        'textonly',
        [
            'textonly' => get_string('sidebarstyle_textonly', 'theme_edzcorp'),
            'bgcolor'  => get_string('sidebarstyle_bgcolor',  'theme_edzcorp'),
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Page / content area background colour (--edz-page-bg).
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/pagebgcolor',
        get_string('pagebgcolor', 'theme_edzcorp'),
        get_string('pagebgcolordesc', 'theme_edzcorp'),
        '#f4f6fb'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Main content panel background colour (--edz-main-inner-bg).
    // Overrides Moodle core's hardcoded white on .main-inner.
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/maininnerbg',
        get_string('maininnerbg', 'theme_edzcorp'),
        get_string('maininnerbgdesc', 'theme_edzcorp'),
        '#ffffff'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Course index drawer background colour (--edz-courseindex-bg).
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/courseindexbg',
        get_string('courseindexbg', 'theme_edzcorp'),
        get_string('courseindexbgdesc', 'theme_edzcorp'),
        '#eeeeee'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Body text colour (--edz-text-color).
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/textcolor',
        get_string('textcolor', 'theme_edzcorp'),
        get_string('textcolordesc', 'theme_edzcorp'),
        '#1e1e2d'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Muted / secondary text colour (--edz-text-muted).
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/textmutedcolor',
        get_string('textmutedcolor', 'theme_edzcorp'),
        get_string('textmutedcolordesc', 'theme_edzcorp'),
        '#6b7280'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Heading text colour (--edz-heading-color).
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/headingcolor',
        get_string('headingcolor', 'theme_edzcorp'),
        get_string('headingcolordesc', 'theme_edzcorp'),
        '#111827'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Breadcrumb link colour (--edz-breadcrumb-link).
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/breadcrumbcolor',
        get_string('breadcrumbcolor', 'theme_edzcorp'),
        get_string('breadcrumbcolordesc', 'theme_edzcorp'),
        '#2563eb'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    if ($use_tabs) {
        $settings->add($page);
    } else {
        foreach ($page->settings as $s) { $settings->add($s); }
    }

    // =========================================================================
    // TAB 3: Login
    // =========================================================================
    $page = new admin_settingpage('theme_edzcorp_login', get_string('loginsettings', 'theme_edzcorp'));

    $page->add(new admin_setting_heading(
        'theme_edzcorp/loginheading',
        get_string('loginsettings', 'theme_edzcorp'),
        get_string('loginsettingsdesc', 'theme_edzcorp')
    ));

    // Login page layout style.
    $setting = new admin_setting_configselect(
        'theme_edzcorp/loginlayout',
        get_string('loginlayout', 'theme_edzcorp'),
        get_string('loginlayout_desc', 'theme_edzcorp'),
        'split',
        [
            'split' => get_string('loginlayout_split', 'theme_edzcorp'),
            'card'  => get_string('loginlayout_card',  'theme_edzcorp'),
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Left panel heading.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/loginsliderheading',
        get_string('loginsliderheading', 'theme_edzcorp'),
        get_string('loginsliderheading_desc', 'theme_edzcorp'),
        '',
        PARAM_TEXT
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Slide 1 image.
    $setting = new admin_setting_configstoredfile(
        'theme_edzcorp/loginsliderimage1',
        get_string('loginsliderimage1', 'theme_edzcorp'),
        get_string('loginsliderimage1_desc', 'theme_edzcorp'),
        'loginsliderimage1', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Slide 1 caption.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/loginslidercaption1',
        get_string('loginslidercaption1', 'theme_edzcorp'),
        get_string('loginslidercaption1_desc', 'theme_edzcorp'),
        '',
        PARAM_TEXT
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Slide 2 image.
    $setting = new admin_setting_configstoredfile(
        'theme_edzcorp/loginsliderimage2',
        get_string('loginsliderimage2', 'theme_edzcorp'),
        get_string('loginsliderimage2_desc', 'theme_edzcorp'),
        'loginsliderimage2', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Slide 2 caption.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/loginslidercaption2',
        get_string('loginslidercaption2', 'theme_edzcorp'),
        get_string('loginslidercaption2_desc', 'theme_edzcorp'),
        '',
        PARAM_TEXT
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // ── Hero fallback panel (shown when NO slider images are uploaded) ──────
    $page->add(new admin_setting_heading(
        'theme_edzcorp/loginheroheading',
        get_string('loginhero_heading', 'theme_edzcorp'),
        get_string('loginhero_headingdesc', 'theme_edzcorp')
    ));

    // Hero background colour.
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/loginherobg',
        get_string('loginhero_bg', 'theme_edzcorp'),
        get_string('loginhero_bgdesc', 'theme_edzcorp'),
        '#1d4ed8'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Hero big title.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/loginherotitle',
        get_string('loginhero_title', 'theme_edzcorp'),
        get_string('loginhero_titledesc', 'theme_edzcorp'),
        'Intelligent Learning, Powered by AI',
        PARAM_TEXT
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Hero subtitle.
    $setting = new admin_setting_configtextarea(
        'theme_edzcorp/loginherosubtitle',
        get_string('loginhero_subtitle', 'theme_edzcorp'),
        get_string('loginhero_subtitledesc', 'theme_edzcorp'),
        'Upload any content — PDFs, videos, links — and get instant summaries, '
            . 'flashcards, quizzes, and glossaries. Build Learning Spaces your students will love.',
        PARAM_TEXT
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Show the stat cards at the bottom.
    $setting = new admin_setting_configcheckbox(
        'theme_edzcorp/loginherostatsenabled',
        get_string('loginhero_statsenabled', 'theme_edzcorp'),
        get_string('loginhero_statsenableddesc', 'theme_edzcorp'),
        1
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Three stat value/label pairs.
    $herostatdefaults = [
        1 => ['10K+', 'Content items'],
        2 => ['50K+', 'AI outputs'],
        3 => ['5K+',  'Learners'],
    ];
    foreach ($herostatdefaults as $i => $pair) {
        $setting = new admin_setting_configtext(
            "theme_edzcorp/loginherostat{$i}value",
            get_string('loginhero_statvalue', 'theme_edzcorp', $i),
            '',
            $pair[0],
            PARAM_TEXT
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        $setting = new admin_setting_configtext(
            "theme_edzcorp/loginherostat{$i}label",
            get_string('loginhero_statlabel', 'theme_edzcorp', $i),
            '',
            $pair[1],
            PARAM_TEXT
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    }

    if ($use_tabs) {
        $settings->add($page);
    } else {
        foreach ($page->settings as $s) { $settings->add($s); }
    }

    // =========================================================================
    // TAB 4: Footer
    // =========================================================================
    $page = new admin_settingpage('theme_edzcorp_footer', get_string('footersettings', 'theme_edzcorp'));

    $page->add(new admin_setting_heading(
        'theme_edzcorp/footerheading',
        get_string('footersettings', 'theme_edzcorp'),
        get_string('footersettingsdesc', 'theme_edzcorp')
    ));

    // Copyright text.
    $setting = new admin_setting_confightmleditor(
        'theme_edzcorp/footercopyright',
        get_string('footercopyright', 'theme_edzcorp'),
        get_string('footercopyright_desc', 'theme_edzcorp'),
        '&copy; ' . date('Y') . ' EdzCorp. All rights reserved.'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Optional tagline below copyright.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/footertext',
        get_string('footertext', 'theme_edzcorp'),
        get_string('footertext_desc', 'theme_edzcorp'),
        ''
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Social links heading.
    $page->add(new admin_setting_heading(
        'theme_edzcorp/socialsheading',
        get_string('socialsheading', 'theme_edzcorp'),
        ''
    ));

    foreach (['facebook', 'instagram', 'linkedin', 'youtube', 'twitter'] as $social) {
        $setting = new admin_setting_configtext(
            "theme_edzcorp/footer{$social}",
            get_string("footer{$social}", 'theme_edzcorp'),
            get_string("footer{$social}_desc", 'theme_edzcorp'),
            '',
            PARAM_URL
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    }

    // App download heading.
    $page->add(new admin_setting_heading(
        'theme_edzcorp/appheading',
        get_string('appheading', 'theme_edzcorp'),
        ''
    ));

    $setting = new admin_setting_configtext(
        'theme_edzcorp/footerappstore',
        get_string('footerappstore', 'theme_edzcorp'),
        get_string('footerappstore_desc', 'theme_edzcorp'),
        '',
        PARAM_URL
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $setting = new admin_setting_configtext(
        'theme_edzcorp/footergoogleplay',
        get_string('footergoogleplay', 'theme_edzcorp'),
        get_string('footergoogleplay_desc', 'theme_edzcorp'),
        '',
        PARAM_URL
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    if ($use_tabs) {
        $settings->add($page);
    } else {
        foreach ($page->settings as $s) { $settings->add($s); }
    }

    // =========================================================================
    // TAB 5: Typography (Google Fonts)
    // =========================================================================
    $page = new admin_settingpage('theme_edzcorp_typography', get_string('typographysettings', 'theme_edzcorp'));

    $page->add(new admin_setting_heading(
        'theme_edzcorp/typographyheading',
        get_string('typographysettings', 'theme_edzcorp'),
        get_string('typographysettingsdesc', 'theme_edzcorp')
    ));

    // Base font size — shifts the entire type scale (body, headings, small).
    $setting = new admin_setting_configselect(
        'theme_edzcorp/basefontsize',
        get_string('basefontsize', 'theme_edzcorp'),
        get_string('basefontsize_desc', 'theme_edzcorp'),
        '16',
        [
            '14' => '14px (compact)',
            '15' => '15px',
            '16' => '16px (default)',
            '17' => '17px',
            '18' => '18px (large)',
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Font options — these are Google Fonts family names (with + for spaces).
    // The value is used directly in the Google Fonts API URL and as a CSS font-family name.
    $fontoptions = [
        ''                => get_string('fontdefault', 'theme_edzcorp'),
        'Instrument+Sans' => 'Instrument Sans (Default)',
        'Inter'           => 'Inter',
        'Sora'            => 'Sora',
        'Source+Sans+3'   => 'Source Sans 3',
        'Roboto'          => 'Roboto',
        'Lato'            => 'Lato',
        'Nunito'          => 'Nunito',
    ];

    // Heading font — applied to h1, h2, h3.
    $setting = new admin_setting_configselect(
        'theme_edzcorp/headingfont',
        get_string('headingfont', 'theme_edzcorp'),
        get_string('headingfont_desc', 'theme_edzcorp'),
        'Instrument+Sans',
        $fontoptions
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Subheading font — applied to h4, h5, h6.
    $setting = new admin_setting_configselect(
        'theme_edzcorp/subheadingfont',
        get_string('subheadingfont', 'theme_edzcorp'),
        get_string('subheadingfont_desc', 'theme_edzcorp'),
        'Instrument+Sans',
        $fontoptions
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Body font — applied to body text (p, span, inputs, etc.).
    $setting = new admin_setting_configselect(
        'theme_edzcorp/bodyfont',
        get_string('bodyfont', 'theme_edzcorp'),
        get_string('bodyfont_desc', 'theme_edzcorp'),
        'Instrument+Sans',
        $fontoptions
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    if ($use_tabs) {
        $settings->add($page);
    } else {
        foreach ($page->settings as $s) { $settings->add($s); }
    }

    // =========================================================================
    // TAB: Frontpage
    // =========================================================================
    $page = new admin_settingpage('theme_edzcorp_frontpage', get_string('frontpagesettings', 'theme_edzcorp'));

    // ── Frontpage navbar ──────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_navbar_heading',
        get_string('fp_navbar_heading', 'theme_edzcorp'),
        get_string('fp_navbar_headingdesc', 'theme_edzcorp')
    ));

    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/fp_navbar_bg',
        get_string('fp_navbar_bg', 'theme_edzcorp'),
        get_string('fp_navbar_bgdesc', 'theme_edzcorp'),
        '#e9ede7'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/fp_navbar_text',
        get_string('fp_navbar_text', 'theme_edzcorp'),
        get_string('fp_navbar_textdesc', 'theme_edzcorp'),
        '#1f2937'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Frontpage navbar height (px) — gives tall customer logos vertical breathing
    // room. The logo is capped to this height minus padding so it never touches
    // the top/bottom edges.
    $setting = new admin_setting_configselect(
        'theme_edzcorp/fp_navbar_height',
        get_string('fp_navbar_height', 'theme_edzcorp'),
        get_string('fp_navbar_heightdesc', 'theme_edzcorp'),
        '64',
        [
            '56'  => '56px',
            '64'  => '64px (default)',
            '72'  => '72px',
            '80'  => '80px',
            '88'  => '88px',
            '96'  => '96px',
            '112' => '112px',
            '128' => '128px',
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // ── Top hero (first section) ──────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_top_admin',
        get_string('fp_top_admin', 'theme_edzcorp'),
        get_string('fp_top_admindesc', 'theme_edzcorp')
    ));

    $page->add(new admin_setting_configcheckbox('theme_edzcorp/fp_top_enable',
        get_string('fp_top_enable', 'theme_edzcorp'),
        get_string('fp_section_enabledesc', 'theme_edzcorp'), '1'));

    $page->add(new admin_setting_configtext('theme_edzcorp/fp_top_eyebrow',
        get_string('fp_top_eyebrow', 'theme_edzcorp'), '', 'Let\'s learn', PARAM_TEXT));

    $page->add(new admin_setting_configtextarea('theme_edzcorp/fp_top_heading',
        get_string('fp_top_heading', 'theme_edzcorp'), '', 'the best Learning platform', PARAM_TEXT));

    $page->add(new admin_setting_configtextarea('theme_edzcorp/fp_top_subtext',
        get_string('fp_top_subtext', 'theme_edzcorp'), '',
        'Find, explore and learn in an awesome place, find, explore and learn in great service.', PARAM_TEXT));

    // CTA button label — blank = auto (Log in when logged out, Dashboard when logged in).
    $page->add(new admin_setting_configtext('theme_edzcorp/fp_top_btnlabel',
        get_string('fp_top_btnlabel', 'theme_edzcorp'),
        get_string('fp_top_btnlabeldesc', 'theme_edzcorp'), '', PARAM_TEXT));

    // Secondary "Have any question?" link.
    $page->add(new admin_setting_configtext('theme_edzcorp/fp_top_qlabel',
        get_string('fp_top_qlabel', 'theme_edzcorp'), '', 'Have any question?', PARAM_TEXT));
    $page->add(new admin_setting_configtext('theme_edzcorp/fp_top_qurl',
        get_string('fp_top_qurl', 'theme_edzcorp'), '', '', PARAM_URL));

    // Centre illustration (upload — a bundled default is used if empty).
    $setting = new admin_setting_configstoredfile(
        'theme_edzcorp/fp_top_image',
        get_string('fp_top_image', 'theme_edzcorp'),
        get_string('fp_top_imagedesc', 'theme_edzcorp'),
        'fp_top_image', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp', '.svg']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Circular badge (rotating text over the illustration).
    $page->add(new admin_setting_configtext('theme_edzcorp/fp_top_badge',
        get_string('fp_top_badge', 'theme_edzcorp'), '', 'Learn more about service', PARAM_TEXT));
    $page->add(new admin_setting_configtext('theme_edzcorp/fp_top_badge_url',
        get_string('fp_top_badge_url', 'theme_edzcorp'), '', '', PARAM_URL));

    // Three stats (right column).
    $topstat = [
        1 => ['+120K', 'Our active monthly users'],
        2 => ['+27K',  'Our monthly products'],
        3 => ['+300K', 'Hours of learning recorded'],
    ];
    for ($i = 1; $i <= 3; $i++) {
        $page->add(new admin_setting_configtext("theme_edzcorp/fp_top_stat{$i}_num",
            get_string('fp_top_stat_num', 'theme_edzcorp', $i), '', $topstat[$i][0], PARAM_TEXT));
        $page->add(new admin_setting_configtext("theme_edzcorp/fp_top_stat{$i}_label",
            get_string('fp_top_stat_label', 'theme_edzcorp', $i), '', $topstat[$i][1], PARAM_TEXT));
    }

    // Avatar-row tagline.
    $page->add(new admin_setting_configtext('theme_edzcorp/fp_top_avatars_text',
        get_string('fp_top_avatars_text', 'theme_edzcorp'), '', 'Find, explore & learn with us.', PARAM_TEXT));

    // ── Employee spotlight ────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_emp_heading',
        get_string('fp_emp_heading', 'theme_edzcorp'),
        get_string('fp_emp_headingdesc', 'theme_edzcorp')
    ));

    $page->add(new admin_setting_configcheckbox('theme_edzcorp/fp_emp_enable',
        get_string('fp_emp_enable', 'theme_edzcorp'),
        get_string('fp_section_enabledesc', 'theme_edzcorp'), '1'));

    // Spotlight style (Testimonial card / Accent panel).
    $page->add(new admin_setting_configselect(
        'theme_edzcorp/fp_emp_style',
        get_string('fp_emp_style', 'theme_edzcorp'),
        get_string('fp_emp_styledesc', 'theme_edzcorp'),
        'card',
        [
            'card'  => get_string('fp_emp_style_card', 'theme_edzcorp'),
            'panel' => get_string('fp_emp_style_panel', 'theme_edzcorp'),
        ]
    ));

    // Employee photo.
    $setting = new admin_setting_configstoredfile(
        'theme_edzcorp/fp_emp_photo',
        get_string('fp_emp_photo', 'theme_edzcorp'),
        get_string('fp_emp_photodesc', 'theme_edzcorp'),
        'fp_emp_photo', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Employee name.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_emp_name',
        get_string('fp_emp_name', 'theme_edzcorp'),
        get_string('fp_emp_namedesc', 'theme_edzcorp'),
        'Sara Chen',
        PARAM_TEXT
    );
    $page->add($setting);

    // Employee title.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_emp_title',
        get_string('fp_emp_title', 'theme_edzcorp'),
        get_string('fp_emp_titledesc', 'theme_edzcorp'),
        'Product Lead',
        PARAM_TEXT
    );
    $page->add($setting);

    // Employee department.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_emp_dept',
        get_string('fp_emp_dept', 'theme_edzcorp'),
        get_string('fp_emp_deptdesc', 'theme_edzcorp'),
        'Innovation & Technology',
        PARAM_TEXT
    );
    $page->add($setting);

    // Employee star rating.
    $setting = new admin_setting_configselect(
        'theme_edzcorp/fp_emp_rating',
        get_string('fp_emp_rating', 'theme_edzcorp'),
        get_string('fp_emp_ratingdesc', 'theme_edzcorp'),
        '5',
        ['1' => '★☆☆☆☆ (1)', '2' => '★★☆☆☆ (2)', '3' => '★★★☆☆ (3)',
         '4' => '★★★★☆ (4)', '5' => '★★★★★ (5)']
    );
    $page->add($setting);

    // Employee feedback quote.
    $setting = new admin_setting_configtextarea(
        'theme_edzcorp/fp_emp_quote',
        get_string('fp_emp_quote', 'theme_edzcorp'),
        get_string('fp_emp_quotedesc', 'theme_edzcorp'),
        'This platform changed how our entire team thinks about upskilling. The breadth of content and the way courses are structured is simply unmatched at this scale.',
        PARAM_RAW
    );
    $page->add($setting);

    // ── Spotlight panel appearance ────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_spotlight_appearance_heading',
        get_string('fp_spotlight_appearance_heading', 'theme_edzcorp'),
        get_string('fp_spotlight_appearance_headingdesc', 'theme_edzcorp')
    ));

    // Big bold headline shown on the left panel (e.g. "Inspiring Education.").
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_spotlight_title',
        get_string('fp_spotlight_title', 'theme_edzcorp'),
        get_string('fp_spotlight_titledesc', 'theme_edzcorp'),
        'Inspiring Education.',
        PARAM_TEXT
    );
    $page->add($setting);

    // The word inside the headline to highlight in accent colour.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_spotlight_accent',
        get_string('fp_spotlight_accent', 'theme_edzcorp'),
        get_string('fp_spotlight_accentdesc', 'theme_edzcorp'),
        'Education',
        PARAM_TEXT
    );
    $page->add($setting);

    // Background colour of the entire spotlight section.
    // Empty default = the theme's purple gradient. A colour set here
    // overrides the gradient (legacy value #f8fafc is treated as unset).
    $setting = new admin_setting_configcolourpicker(
        'theme_edzcorp/fp_spotlight_bg',
        get_string('fp_spotlight_bg', 'theme_edzcorp'),
        get_string('fp_spotlight_bgdesc', 'theme_edzcorp'),
        ''
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // ── Hero section ──────────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_hero_heading',
        get_string('fp_hero_heading', 'theme_edzcorp'),
        get_string('fp_hero_headingdesc', 'theme_edzcorp')
    ));

    $page->add(new admin_setting_configcheckbox('theme_edzcorp/fp_hero_enable',
        get_string('fp_hero_enable', 'theme_edzcorp'),
        get_string('fp_section_enabledesc', 'theme_edzcorp'), '1'));

    // Eyebrow label.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_hero_eyebrow',
        get_string('fp_hero_eyebrow', 'theme_edzcorp'),
        get_string('fp_hero_eyebrowdesc', 'theme_edzcorp'),
        'Enterprise learning platform',
        PARAM_TEXT
    );
    $page->add($setting);

    // Main headline.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_hero_headline',
        get_string('fp_hero_headline', 'theme_edzcorp'),
        get_string('fp_hero_headlinedesc', 'theme_edzcorp'),
        'Upskill your entire workforce. At enterprise scale.',
        PARAM_TEXT
    );
    $page->add($setting);

    // Subtitle / description.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_hero_sub',
        get_string('fp_hero_sub', 'theme_edzcorp'),
        get_string('fp_hero_subdesc', 'theme_edzcorp'),
        'Structured learning paths, real skills, measurable outcomes.',
        PARAM_TEXT
    );
    $page->add($setting);

    // Button 1.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_hero_btn1_label',
        get_string('fp_hero_btn1_label', 'theme_edzcorp'),
        get_string('fp_hero_btn1_labeldesc', 'theme_edzcorp'),
        'Explore courses',
        PARAM_TEXT
    );
    $page->add($setting);

    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_hero_btn1_url',
        get_string('fp_hero_btn1_url', 'theme_edzcorp'),
        get_string('fp_hero_btn1_urldesc', 'theme_edzcorp'),
        '/course/index.php',
        PARAM_TEXT
    );
    $page->add($setting);

    // Button 2.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_hero_btn2_label',
        get_string('fp_hero_btn2_label', 'theme_edzcorp'),
        get_string('fp_hero_btn2_labeldesc', 'theme_edzcorp'),
        'Start my lesson',
        PARAM_TEXT
    );
    $page->add($setting);

    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_hero_btn2_url',
        get_string('fp_hero_btn2_url', 'theme_edzcorp'),
        get_string('fp_hero_btn2_urldesc', 'theme_edzcorp'),
        '/my/',
        PARAM_TEXT
    );
    $page->add($setting);

    // ── Stats row ─────────────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_stats_heading',
        get_string('fp_stats_heading', 'theme_edzcorp'),
        get_string('fp_stats_headingdesc', 'theme_edzcorp')
    ));

    $stat_defaults = [
        1 => ['99k+', 'Learners'],
        2 => ['30+',  'Skill areas'],
        3 => ['4.9★', 'Avg rating'],
        4 => ['200+', 'Courses'],
    ];
    for ($i = 1; $i <= 4; $i++) {
        $setting = new admin_setting_configtext(
            "theme_edzcorp/fp_stat{$i}_num",
            get_string("fp_stat_num", 'theme_edzcorp') . " {$i} — " . get_string('fp_stat_number', 'theme_edzcorp'),
            '',
            $stat_defaults[$i][0],
            PARAM_TEXT
        );
        $page->add($setting);

        $setting = new admin_setting_configtext(
            "theme_edzcorp/fp_stat{$i}_label",
            get_string("fp_stat_num", 'theme_edzcorp') . " {$i} — " . get_string('fp_stat_label', 'theme_edzcorp'),
            '',
            $stat_defaults[$i][1],
            PARAM_TEXT
        );
        $page->add($setting);
    }

    // ── Skills section (icon features + media) ────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_skills_admin',
        get_string('fp_skills_admin', 'theme_edzcorp'),
        get_string('fp_skills_admindesc', 'theme_edzcorp')
    ));

    $page->add(new admin_setting_configcheckbox('theme_edzcorp/fp_skills_enable',
        get_string('fp_skills_enable', 'theme_edzcorp'),
        get_string('fp_section_enabledesc', 'theme_edzcorp'), '1'));

    // Section title — leave BLANK to hide the whole section.
    $page->add(new admin_setting_configtextarea(
        'theme_edzcorp/fp_skills_title',
        get_string('fp_skills_title', 'theme_edzcorp'),
        get_string('fp_skills_titledesc', 'theme_edzcorp'),
        'Get the skills you need for a job that is in demand.',
        PARAM_TEXT
    ));

    // Lead paragraph (top-right).
    $page->add(new admin_setting_configtextarea(
        'theme_edzcorp/fp_skills_lead',
        get_string('fp_skills_lead', 'theme_edzcorp'),
        get_string('fp_skills_leaddesc', 'theme_edzcorp'),
        'The modern workplace moves fast. To stay competitive, your team needs more than professional skills — they need continuous, structured growth.',
        PARAM_TEXT
    ));

    // Three feature items (icon + title + description).
    $featicon  = [1 => 'fa-user-tie', 2 => 'fa-people-group', 3 => 'fa-arrows-left-right'];
    $feattitle = [1 => 'Leadership',  2 => 'Responsibility',  3 => 'Flexibility'];
    $featdesc  = [
        1 => 'Fully committed to the success of the company',
        2 => 'Employees will always be my top priority',
        3 => 'The ability to switch is an important skill',
    ];
    for ($i = 1; $i <= 3; $i++) {
        $page->add(new admin_setting_configtext(
            "theme_edzcorp/fp_skills_feat{$i}_icon",
            get_string('fp_skills_feat_icon', 'theme_edzcorp', $i),
            get_string('fp_skills_feat_icondesc', 'theme_edzcorp'),
            $featicon[$i],
            PARAM_TEXT
        ));
        $page->add(new admin_setting_configtext(
            "theme_edzcorp/fp_skills_feat{$i}_title",
            get_string('fp_skills_feat_title', 'theme_edzcorp', $i),
            '',
            $feattitle[$i],
            PARAM_TEXT
        ));
        $page->add(new admin_setting_configtextarea(
            "theme_edzcorp/fp_skills_feat{$i}_desc",
            get_string('fp_skills_feat_desc', 'theme_edzcorp', $i),
            '',
            $featdesc[$i],
            PARAM_TEXT
        ));
    }

    // Right-side media image (poster / still).
    $setting = new admin_setting_configstoredfile(
        'theme_edzcorp/fp_skills_image',
        get_string('fp_skills_image', 'theme_edzcorp'),
        get_string('fp_skills_imagedesc', 'theme_edzcorp'),
        'fp_skills_image', 0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp']]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Video link (YouTube or a direct .mp4). Click the image to play it.
    $page->add(new admin_setting_configtext(
        'theme_edzcorp/fp_skills_videourl',
        get_string('fp_skills_videourl', 'theme_edzcorp'),
        get_string('fp_skills_videourldesc', 'theme_edzcorp'),
        'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
        PARAM_URL
    ));

    // ── Philosophy section ────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_philosophy_heading',
        get_string('fp_philosophy_heading', 'theme_edzcorp'),
        get_string('fp_philosophy_headingdesc', 'theme_edzcorp')
    ));

    $page->add(new admin_setting_configcheckbox('theme_edzcorp/fp_philosophy_enable',
        get_string('fp_philosophy_enable', 'theme_edzcorp'),
        get_string('fp_section_enabledesc', 'theme_edzcorp'), '1'));

    // ── Topics & courses ──────────────────────────────────────────────────────

    $page->add(new admin_setting_heading(
        'theme_edzcorp/fp_content_heading',
        get_string('fp_content_heading', 'theme_edzcorp'),
        get_string('fp_content_headingdesc', 'theme_edzcorp')
    ));

    $page->add(new admin_setting_configcheckbox('theme_edzcorp/fp_topics_enable',
        get_string('fp_topics_enable', 'theme_edzcorp'),
        get_string('fp_section_enabledesc', 'theme_edzcorp'), '1'));

    $page->add(new admin_setting_configcheckbox('theme_edzcorp/fp_courses_enable',
        get_string('fp_courses_enable', 'theme_edzcorp'),
        get_string('fp_section_enabledesc', 'theme_edzcorp'), '1'));

    // Topics section heading.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_topics_heading',
        get_string('fp_topics_heading', 'theme_edzcorp'),
        get_string('fp_topics_headingdesc', 'theme_edzcorp'),
        'Popular topics to learn',
        PARAM_TEXT
    );
    $page->add($setting);

    // Topics source — which categories feed the section.
    $setting = new admin_setting_configselect(
        'theme_edzcorp/fp_topics_source',
        get_string('fp_topics_source', 'theme_edzcorp'),
        get_string('fp_topics_sourcedesc', 'theme_edzcorp'),
        'alltop',
        [
            'alltop'   => get_string('fp_topics_source_alltop', 'theme_edzcorp'),
            'all'      => get_string('fp_topics_source_all', 'theme_edzcorp'),
            'specific' => get_string('fp_topics_source_specific', 'theme_edzcorp'),
        ]
    );
    $page->add($setting);

    // Specific category(ies) — only used when source = "specific".
    // Multi-select: hold Ctrl/Cmd to pick more than one; each becomes a tile.
    $catoptions = [];
    if (!during_initial_install()) {
        try {
            $catoptions = \core_course_category::make_categories_list();
        } catch (\Throwable $e) {
            // Category tree not ready (e.g. mid-upgrade) — leave empty.
        }
    }
    $setting = new admin_setting_configmultiselect(
        'theme_edzcorp/fp_topics_specific',
        get_string('fp_topics_specific', 'theme_edzcorp'),
        get_string('fp_topics_specificdesc', 'theme_edzcorp'),
        [],
        $catoptions
    );
    $page->add($setting);

    // Display style — pill buttons (default) or icon cards.
    $setting = new admin_setting_configselect(
        'theme_edzcorp/fp_topics_style',
        get_string('fp_topics_style', 'theme_edzcorp'),
        get_string('fp_topics_styledesc', 'theme_edzcorp'),
        'buttons',
        [
            'buttons' => get_string('fp_topics_style_buttons', 'theme_edzcorp'),
            'cards'   => get_string('fp_topics_style_cards', 'theme_edzcorp'),
        ]
    );
    $page->add($setting);

    // Cards per row — only used when style = "cards".
    $setting = new admin_setting_configselect(
        'theme_edzcorp/fp_topics_percard',
        get_string('fp_topics_percard', 'theme_edzcorp'),
        get_string('fp_topics_percarddesc', 'theme_edzcorp'),
        '6',
        ['6' => '6', '8' => '8']
    );
    $page->add($setting);

    // Recently launched section heading.
    $setting = new admin_setting_configtext(
        'theme_edzcorp/fp_courses_heading',
        get_string('fp_courses_heading', 'theme_edzcorp'),
        get_string('fp_courses_headingdesc', 'theme_edzcorp'),
        'Recently launched',
        PARAM_TEXT
    );
    $page->add($setting);

    // Course IDs (comma-separated). Blank = auto (4 newest courses).
    $setting = new admin_setting_configtextarea(
        'theme_edzcorp/fp_courses_ids',
        get_string('fp_courses_ids', 'theme_edzcorp'),
        get_string('fp_courses_idsdesc', 'theme_edzcorp'),
        '',
        PARAM_RAW
    );
    $page->add($setting);

    if ($use_tabs) {
        $settings->add($page);
    } else {
        foreach ($page->settings as $s) { $settings->add($s); }
    }
}
