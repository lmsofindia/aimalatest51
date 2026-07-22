<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp theme library functions.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the main SCSS content for the theme.
 *
 * Starts with Boost's compiled SCSS then appends our own partials.
 * Also injects:
 *  - :root { } overrides for sidebar CSS custom properties driven by settings.
 *  - Login background image CSS rule (so no JS or inline style is needed).
 *
 * This function is called by Moodle's SCSS compilation pipeline.  Compilation
 * is triggered whenever theme caches are purged (e.g. after saving settings).
 *
 * @param  theme_config $theme  The theme object.
 * @return string               Combined SCSS/CSS string.
 */
function theme_edzcorp_get_main_scss_content(theme_config $theme): string {
    global $CFG;

    $settings = $theme->settings;
    $scss     = '';

    // -------------------------------------------------------------------------
    // 1. Boost base SCSS (Bootstrap variables + utilities + Boost overrides).
    //
    // NOTE: Google Fonts are no longer injected here as @import url().
    // scssphp treats @import as a file import and fails on external URLs that
    // contain special characters (@ ; ,).  Instead, Google Fonts are injected
    // as <link rel="stylesheet"> tags via core_renderer::standard_head_html().
    // -------------------------------------------------------------------------
    $scss .= theme_boost_get_main_scss_content($theme);

    // -------------------------------------------------------------------------
    // 2. EdzCorp partial SCSS files in dependency order.
    // -------------------------------------------------------------------------
    $partialsdir = $CFG->dirroot . '/theme/edzcorp/scss/';
    $partials = [
        '_variables.scss',
        '_layout.scss',
        '_sidebar.scss',
        '_navbar.scss',
        '_components.scss',
        '_darkmode.scss',
        '_frontpage.scss',  // enterprise frontpage sections
        '_polish.scss',    // global micro-polish — loaded last to win specificity
    ];

    foreach ($partials as $partial) {
        $filepath = $partialsdir . $partial;
        if (file_exists($filepath)) {
            $scss .= "\n/* --- EdzCorp: {$partial} --- */\n";
            $scss .= file_get_contents($filepath);
        } else {
            $scss .= "\n/* --- EdzCorp WARNING: {$partial} not found --- */\n";
        }
    }

    // -------------------------------------------------------------------------
    // 3. CSS custom property overrides driven by admin settings.
    //    All vars are collected first, then output in a single :root { } block
    //    so there is exactly one override rule (clean, no duplicates).
    // -------------------------------------------------------------------------
    $cssVars = [];

    // Sidebar colours.
    if (!empty($settings->sidebarbgcolor)) {
        $cssVars[] = "    --edz-sidebar-bg: {$settings->sidebarbgcolor}";
    }
    if (!empty($settings->sidebartextcolor)) {
        $cssVars[] = "    --edz-sidebar-color: {$settings->sidebartextcolor}";
    }
    if (!empty($settings->sidebarhovercolor)) {
        $cssVars[] = "    --edz-sidebar-hover-bg: {$settings->sidebarhovercolor}";
    }
    if (!empty($settings->sidebaractivecolor)) {
        $cssVars[] = "    --edz-sidebar-active-bg: {$settings->sidebaractivecolor}";
    }

    // Page background colour.
    if (!empty($settings->pagebgcolor)) {
        $cssVars[] = "    --edz-page-bg: {$settings->pagebgcolor}";
    }

    // Main content panel background colour (overrides core's white .main-inner).
    if (!empty($settings->maininnerbg)) {
        $cssVars[] = "    --edz-main-inner-bg: {$settings->maininnerbg}";
    }

    // Course index drawer background colour.
    if (!empty($settings->courseindexbg)) {
        $cssVars[] = "    --edz-courseindex-bg: {$settings->courseindexbg}";
    }

    // Frontpage navbar background colour.
    if (!empty($settings->fp_navbar_bg)) {
        $cssVars[] = "    --edz-fp-navbar-bg: {$settings->fp_navbar_bg}";
    }

    // Frontpage navbar text colour.
    if (!empty($settings->fp_navbar_text)) {
        $cssVars[] = "    --edz-fp-navbar-text: {$settings->fp_navbar_text}";
    }

    // Frontpage navbar height (px) — controls the bar min-height and the logo cap.
    if (!empty($settings->fp_navbar_height)) {
        $h = (int) $settings->fp_navbar_height;
        if ($h >= 48 && $h <= 200) {
            $cssVars[] = "    --edz-fp-navbar-height: {$h}px";
        }
    }

    // Text colours.
    if (!empty($settings->textcolor)) {
        $cssVars[] = "    --edz-text-color: {$settings->textcolor}";
    }
    if (!empty($settings->textmutedcolor)) {
        $cssVars[] = "    --edz-text-muted: {$settings->textmutedcolor}";
    }
    if (!empty($settings->headingcolor)) {
        $cssVars[] = "    --edz-heading-color: {$settings->headingcolor}";
    }
    if (!empty($settings->breadcrumbcolor)) {
        $cssVars[] = "    --edz-breadcrumb-link: {$settings->breadcrumbcolor}";
    }

    // Login hero fallback background colour.
    if (!empty($settings->loginherobg)) {
        $cssVars[] = "    --edz-login-hero-bg: {$settings->loginherobg}";
    }

    // Typography — base font size shifts the whole scale.
    // body = base, small = base-2, h6/h5 = base, h4 = base+1, h3 = base+2,
    // h2 = base+4, h1 = base+6 (all converted to rem, 16px root).
    $basepx = !empty($settings->basefontsize) ? (int) $settings->basefontsize : 16;
    if ($basepx >= 12 && $basepx <= 22 && $basepx !== 16) {
        $rem = static function (int $px): string {
            return rtrim(rtrim(number_format($px / 16, 4, '.', ''), '0'), '.') . 'rem';
        };
        $cssVars[] = "    --edz-fs-body: " . $rem($basepx);
        $cssVars[] = "    --edz-fs-small: " . $rem($basepx - 2);
        $cssVars[] = "    --edz-fs-h6: " . $rem($basepx);
        $cssVars[] = "    --edz-fs-h5: " . $rem($basepx);
        $cssVars[] = "    --edz-fs-h4: " . $rem($basepx + 1);
        $cssVars[] = "    --edz-fs-h3: " . $rem($basepx + 2);
        $cssVars[] = "    --edz-fs-h2: " . $rem($basepx + 4);
        $cssVars[] = "    --edz-fs-h1: " . $rem($basepx + 6);
    }

    // Typography — font-family overrides.
    if (!empty($settings->headingfont)) {
        $cssVars[] = "    --edz-font-heading: '" . str_replace('+', ' ', $settings->headingfont) . "', sans-serif";
    }
    if (!empty($settings->subheadingfont)) {
        $cssVars[] = "    --edz-font-subheading: '" . str_replace('+', ' ', $settings->subheadingfont) . "', sans-serif";
    }
    if (!empty($settings->bodyfont)) {
        $cssVars[] = "    --edz-font-body: '" . str_replace('+', ' ', $settings->bodyfont) . "', sans-serif";
    }

    if (!empty($cssVars)) {
        $scss .= "\n/* --- EdzCorp: settings CSS custom properties --- */\n";
        $scss .= ":root {\n" . implode(";\n", $cssVars) . ";\n}\n";
    }

    // -------------------------------------------------------------------------
    // Sidebar active-link style override (appended AFTER main SCSS so it wins).
    // When admin selects "bgcolor" mode, override the default text-only CSS.
    // -------------------------------------------------------------------------
    $activestyle = !empty($settings->sidebaractivestyle) ? $settings->sidebaractivestyle : 'textonly';
    if ($activestyle === 'bgcolor') {
        $scss .= "\n/* --- EdzCorp: sidebar bgcolor active mode --- */\n";
        $scss .= ".edzcorp-sidenav .sidenav-item.active > .sidenav-link {\n";
        $scss .= "    background: var(--edz-sidebar-active-bg) !important;\n";
        $scss .= "    color: var(--edz-sidebar-active-color) !important;\n";
        $scss .= "    font-weight: 600 !important;\n";
        $scss .= "    box-shadow: none !important;\n";
        $scss .= "}\n";
        $scss .= ".edzcorp-sidenav .sidenav-link:hover,\n";
        $scss .= ".edzcorp-sidenav .sidenav-link:focus {\n";
        $scss .= "    background: var(--edz-sidebar-hover-bg) !important;\n";
        $scss .= "    color: var(--edz-sidebar-active-color) !important;\n";
        $scss .= "    box-shadow: none !important;\n";
        $scss .= "}\n";
    }

    // -------------------------------------------------------------------------
    // 4. Login slider background images (injected as plain CSS rules).
    //    The .edz-login-slider gradient fallback is in _layout.scss; these rules
    //    override it when images are uploaded in admin settings.
    // -------------------------------------------------------------------------
    if (!empty($settings->loginsliderimage1) || !empty($settings->loginsliderimage2)) {
        $scss .= "\n/* --- EdzCorp: login slider images --- */\n";
        if (!empty($settings->loginsliderimage1)) {
            $img1 = str_replace("'", "\\'", $theme->setting_file_url('loginsliderimage1', 'loginsliderimage1'));
            $scss .= ".edz-slide-1 { background-image: url('{$img1}'); }\n";
        }
        if (!empty($settings->loginsliderimage2)) {
            $img2 = str_replace("'", "\\'", $theme->setting_file_url('loginsliderimage2', 'loginsliderimage2'));
            $scss .= ".edz-slide-2 { background-image: url('{$img2}'); }\n";
        }
    }

    return $scss;
}

/**
 * Returns the pre-SCSS content (injected BEFORE Boost's own SCSS).
 * Used to override Bootstrap/Boost SCSS variables.
 *
 * @param  theme_config $theme  The theme object.
 * @return string               Pre-SCSS variable overrides.
 */
function theme_edzcorp_get_pre_scss(theme_config $theme): string {
    global $CFG;

    // Start with Boost's own pre-SCSS.
    $scss = theme_boost_get_pre_scss($theme);

    $settings = $theme->settings;

    // Primary colour → Bootstrap's $primary SCSS variable.
    if (!empty($settings->primarycolor)) {
        $scss .= "\$primary: {$settings->primarycolor};\n";
        $scss .= "\$link-color: {$settings->primarycolor};\n";
    }

    // Accent colour → Bootstrap's $secondary SCSS variable.
    // Used by .btn-secondary, .badge.bg-secondary, .text-secondary, etc.
    if (!empty($settings->accentcolor)) {
        $scss .= "\$secondary: {$settings->accentcolor};\n";
    }

    // Append the theme's own pre.scss for additional overrides.
    $prescssfile = $CFG->dirroot . '/theme/edzcorp/scss/pre.scss';
    if (file_exists($prescssfile)) {
        $scss .= "\n/* --- EdzCorp: pre.scss --- */\n";
        $scss .= file_get_contents($prescssfile);
    }

    return $scss;
}

/**
 * Builds the template context for the EdzCorp footer partial.
 *
 * Reads all footer-related admin settings and returns an array ready to be
 * merged into any layout's $templatecontext.
 *
 * @param  theme_config $theme
 * @return array
 */
function theme_edzcorp_get_footer_context(theme_config $theme): array {
    $s = $theme->settings;

    $copyright    = !empty($s->footercopyright)   ? format_text($s->footercopyright, FORMAT_HTML) : '';
    $footertext   = !empty($s->footertext)         ? format_text($s->footertext, FORMAT_HTML)      : '';
    $facebook     = !empty($s->footerfacebook)    ? clean_param($s->footerfacebook, PARAM_URL)     : '';
    $instagram    = !empty($s->footerinstagram)   ? clean_param($s->footerinstagram, PARAM_URL)    : '';
    $linkedin     = !empty($s->footerlinkedin)    ? clean_param($s->footerlinkedin, PARAM_URL)     : '';
    $youtube      = !empty($s->footeryoutube)     ? clean_param($s->footeryoutube, PARAM_URL)      : '';
    $twitter      = !empty($s->footertwitter)     ? clean_param($s->footertwitter, PARAM_URL)      : '';
    $appstore     = !empty($s->footerappstore)    ? clean_param($s->footerappstore, PARAM_URL)     : '';
    $googleplay   = !empty($s->footergoogleplay)  ? clean_param($s->footergoogleplay, PARAM_URL)   : '';

    $hassocials  = ($facebook || $instagram || $linkedin || $youtube || $twitter);
    $hasapplinks = ($appstore || $googleplay);

    return [
        'footercopyright'  => $copyright,
        'footertext'       => $footertext,
        'footerfacebook'   => $facebook,
        'footerinstagram'  => $instagram,
        'footerlinkedin'   => $linkedin,
        'footeryoutube'    => $youtube,
        'footertwitter'    => $twitter,
        'footerappstore'   => $appstore,
        'footergoogleplay' => $googleplay,
        'hassocials'       => $hassocials,
        'hasapplinks'      => $hasapplinks,
        'hassocialorapps'  => ($hassocials || $hasapplinks),
        'currentyear'      => date('Y'),
    ];
}


/**
 * Serves theme files (logos, favicons, background images, etc.).
 *
 * @param  stdClass  $course
 * @param  stdClass  $cm
 * @param  context   $context
 * @param  string    $filearea
 * @param  array     $args
 * @param  bool      $forcedownload
 * @param  array     $options
 * @return bool
 */
function theme_edzcorp_pluginfile(
    $course,
    $cm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        $theme = theme_config::load('edzcorp');
        if (in_array($filearea, ['logo', 'sidebarlogo', 'mobilelogo', 'loginsliderimage1', 'loginsliderimage2', 'fp_emp_photo', 'fp_skills_image', 'fp_top_image'], true)) {
            return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
        }
    }
    send_file_not_found();
}

/**
 * Builds the template context for the EdzCorp frontpage (enterprise homepage).
 *
 * Reads frontpage admin settings and assembles four sections:
 *   1. Employee spotlight  — photo/initials, quote, stars.
 *   2. Hero                — headline, CTA buttons, stats row.
 *   3. Popular topics      — top-level course categories as pill chips.
 *   4. Recently launched   — admin-selected course IDs (or 4 newest courses).
 *
 * @param  theme_config $theme
 * @return array  Key-value pairs merged into $templatecontext by frontpage.php.
 */
function theme_edzcorp_get_frontpage_context(theme_config $theme): array {
    global $DB;

    $s = $theme->settings;

    // Per-section enable flag: a section is ON unless the admin explicitly
    // unticked it (value '0'). Unset counts as enabled (default).
    $en = function ($key) use ($s) {
        return !isset($s->$key) || (string)$s->$key !== '0';
    };

    // =========================================================================
    // 0. TOP HERO (first section) — always shown, with sensible defaults.
    // =========================================================================
    $top_eyebrow = !empty($s->fp_top_eyebrow) ? clean_param($s->fp_top_eyebrow, PARAM_TEXT) : 'Let\'s learn';
    $top_heading = !empty($s->fp_top_heading) ? clean_param($s->fp_top_heading, PARAM_TEXT) : 'the best Learning platform';
    $top_subtext = !empty($s->fp_top_subtext) ? clean_param($s->fp_top_subtext, PARAM_TEXT)
        : 'Find, explore and learn in an awesome place, find, explore and learn in great service.';

    // CTA: auto Login (logged out) / Dashboard (logged in). Label can be overridden.
    if (isloggedin() && !isguestuser()) {
        $top_cta_url     = (new \moodle_url('/my/'))->out(false);
        $top_cta_default = get_string('fp_top_dashboard', 'theme_edzcorp');
    } else {
        $top_cta_url     = (new \moodle_url('/login/index.php'))->out(false);
        $top_cta_default = get_string('fp_top_login', 'theme_edzcorp');
    }
    $top_cta_label = !empty($s->fp_top_btnlabel) ? clean_param($s->fp_top_btnlabel, PARAM_TEXT) : $top_cta_default;

    $top_qlabel = !empty($s->fp_top_qlabel) ? clean_param($s->fp_top_qlabel, PARAM_TEXT) : 'Have a question?';
    $top_qurl   = !empty($s->fp_top_qurl) ? clean_param($s->fp_top_qurl, PARAM_URL) : '';

    $top_image = '';
    if (!empty($s->fp_top_image)) {
        $top_image = $theme->setting_file_url('fp_top_image', 'fp_top_image');
    }
    if (empty($top_image)) {
        $top_image = (new \moodle_url('/theme/edzcorp/pix/top-hero.svg'))->out(false);
    }

    $top_badge     = !empty($s->fp_top_badge) ? clean_param($s->fp_top_badge, PARAM_TEXT) : 'Learn more about service';
    $top_badge_url = !empty($s->fp_top_badge_url) ? clean_param($s->fp_top_badge_url, PARAM_URL) : '';

    $topstatdefaults = [
        1 => ['+120K', 'Our active monthly users'],
        2 => ['+27K',  'Our monthly products'],
        3 => ['+300K', 'Hours of learning recorded'],
    ];
    $top_stats = [];
    for ($ti = 1; $ti <= 3; $ti++) {
        $nk = "fp_top_stat{$ti}_num";
        $lk = "fp_top_stat{$ti}_label";
        $top_stats[] = [
            'num'   => !empty($s->$nk) ? clean_param($s->$nk, PARAM_TEXT) : $topstatdefaults[$ti][0],
            'label' => !empty($s->$lk) ? clean_param($s->$lk, PARAM_TEXT) : $topstatdefaults[$ti][1],
        ];
    }
    $top_avatars_text = !empty($s->fp_top_avatars_text) ? clean_param($s->fp_top_avatars_text, PARAM_TEXT) : 'Find, explore & learn with us.';

    // =========================================================================
    // 1. EMPLOYEE SPOTLIGHT
    // =========================================================================

    $emp_name  = !empty($s->fp_emp_name)  ? clean_param($s->fp_emp_name,  PARAM_TEXT) : 'Sara Chen';
    $emp_title = !empty($s->fp_emp_title) ? clean_param($s->fp_emp_title, PARAM_TEXT) : 'Product Lead';
    $emp_dept  = !empty($s->fp_emp_dept)  ? clean_param($s->fp_emp_dept,  PARAM_TEXT) : 'Innovation & Technology';
    $emp_quote = !empty($s->fp_emp_quote)
        ? format_text($s->fp_emp_quote, FORMAT_HTML, ['trusted' => false, 'noclean' => false])
        : 'This platform changed how our entire team thinks about upskilling. The breadth of content and the way courses are structured is simply unmatched at this scale.';
    $emp_rating = !empty($s->fp_emp_rating) ? max(1, min(5, (int)$s->fp_emp_rating)) : 5;

    // Spotlight style.
    $emp_style = !empty($s->fp_emp_style) ? clean_param($s->fp_emp_style, PARAM_ALPHA) : 'card';
    if (!in_array($emp_style, ['card', 'panel'], true)) {
        $emp_style = 'card';
    }

    // Generate initials (up to 2 characters) from the employee name.
    $words    = array_filter(explode(' ', trim($emp_name)));
    $initials = '';
    foreach (array_slice(array_values($words), 0, 2) as $w) {
        $initials .= strtoupper(mb_substr($w, 0, 1));
    }

    // Build star HTML: filled ★ for rating, hollow ☆ for remainder.
    $stars_html = str_repeat('★', $emp_rating) . str_repeat('☆', 5 - $emp_rating);

    // Spotlight panel appearance settings.
    $spotlight_title  = !empty($s->fp_spotlight_title)
        ? clean_param($s->fp_spotlight_title,  PARAM_TEXT)
        : 'Inspiring Education.';
    $spotlight_accent = !empty($s->fp_spotlight_accent)
        ? clean_param($s->fp_spotlight_accent, PARAM_TEXT)
        : 'Education';
    // Empty default — the SCSS purple gradient is the design default.
    // '#f8fafc' was the legacy light default; treat it as unset so existing
    // sites pick up the new gradient automatically.
    $spotlight_bg     = !empty($s->fp_spotlight_bg)
        ? clean_param($s->fp_spotlight_bg, PARAM_TEXT)
        : '';
    if (strtolower($spotlight_bg) === '#f8fafc') {
        $spotlight_bg = '';
    }

    // Wrap the accent word in a <span> for colour highlighting.
    // Case-sensitive match; only replaces the first occurrence.
    if (!empty($spotlight_accent) && strpos($spotlight_title, $spotlight_accent) !== false) {
        $spotlight_title_html = str_replace(
            $spotlight_accent,
            '<span class="edz-spotlight__accent">' . $spotlight_accent . '</span>',
            $spotlight_title
        );
    } else {
        $spotlight_title_html = htmlspecialchars($spotlight_title, ENT_QUOTES, 'UTF-8');
    }

    // Employee photo URL — served via pluginfile.
    $emp_photo_url   = '';
    $system_context  = \context_system::instance();
    $fs              = get_file_storage();
    $photo_files     = $fs->get_area_files(
        $system_context->id, 'theme_edzcorp', 'fp_emp_photo', 0, 'itemid ASC', false
    );
    if (!empty($photo_files)) {
        $photo_file    = reset($photo_files);
        $emp_photo_url = \moodle_url::make_pluginfile_url(
            $photo_file->get_contextid(),
            $photo_file->get_component(),
            $photo_file->get_filearea(),
            $photo_file->get_itemid(),
            $photo_file->get_filepath(),
            $photo_file->get_filename()
        )->out(false);
    }


    // =========================================================================
    // 2. HERO SECTION
    // =========================================================================

    $hero_eyebrow  = !empty($s->fp_hero_eyebrow)
        ? clean_param($s->fp_hero_eyebrow,  PARAM_TEXT)
        : 'Enterprise learning platform';
    $hero_headline = !empty($s->fp_hero_headline)
        ? clean_param($s->fp_hero_headline, PARAM_TEXT)
        : 'Upskill your entire workforce. At enterprise scale.';
    $hero_sub      = !empty($s->fp_hero_sub)
        ? clean_param($s->fp_hero_sub,      PARAM_TEXT)
        : 'Structured learning paths, real skills, measurable outcomes.';

    $btn1_label = !empty($s->fp_hero_btn1_label)
        ? clean_param($s->fp_hero_btn1_label, PARAM_TEXT)
        : 'Explore courses';
    $btn1_url   = !empty($s->fp_hero_btn1_url)
        ? clean_param($s->fp_hero_btn1_url,   PARAM_URL)
        : (string)(new \moodle_url('/course/index.php'));

    $btn2_label = !empty($s->fp_hero_btn2_label)
        ? clean_param($s->fp_hero_btn2_label, PARAM_TEXT)
        : 'Start my lesson';
    $btn2_url   = !empty($s->fp_hero_btn2_url)
        ? clean_param($s->fp_hero_btn2_url,   PARAM_URL)
        : (string)(new \moodle_url('/my/'));

    // Four stat tiles — each has a number string and a label. Defaults are
    // computed from REAL site data (admin can still override any tile via the
    // fp_stat*_num / fp_stat*_label settings).
    $stat_learners = (int)$DB->count_records_select('user', 'deleted = 0 AND id > 2');
    $stat_courses  = (int)$DB->count_records_select('course', 'id <> :s AND visible = 1', ['s' => SITEID]);
    $stat_cats     = (int)$DB->count_records('course_categories', ['visible' => 1]);
    $stat_enrols   = (int)$DB->count_records('user_enrolments');
    $fpnum = static function (int $n): string {
        return $n >= 1000 ? round($n / 1000, 1) . 'k' : (string)$n;
    };
    $stat_defaults = [
        1 => [$fpnum($stat_learners), 'Learners'],
        2 => [$fpnum($stat_courses),  'Courses'],
        3 => [$fpnum($stat_cats),     'Skill areas'],
        4 => [$fpnum($stat_enrols),   'Enrolments'],
    ];
    $fp_stats = [];
    for ($i = 1; $i <= 4; $i++) {
        $num_key   = "fp_stat{$i}_num";
        $label_key = "fp_stat{$i}_label";
        $fp_stats[] = [
            'num'   => !empty($s->$num_key)   ? clean_param($s->$num_key,   PARAM_TEXT) : $stat_defaults[$i][0],
            'label' => !empty($s->$label_key) ? clean_param($s->$label_key, PARAM_TEXT) : $stat_defaults[$i][1],
        ];
    }

    // =========================================================================
    // 2b. SKILLS SECTION (icon features + click-to-play media)
    // =========================================================================

    $skills_title = !empty($s->fp_skills_title) ? clean_param($s->fp_skills_title, PARAM_TEXT) : '';
    $skills_has   = ($skills_title !== '');
    $skills_lead  = !empty($s->fp_skills_lead) ? clean_param($s->fp_skills_lead, PARAM_TEXT) : '';

    $skills_features = [];
    for ($i = 1; $i <= 3; $i++) {
        $tkey = "fp_skills_feat{$i}_title";
        $ikey = "fp_skills_feat{$i}_icon";
        $dkey = "fp_skills_feat{$i}_desc";
        $ftitle = !empty($s->$tkey) ? clean_param($s->$tkey, PARAM_TEXT) : '';
        if ($ftitle === '') {
            continue;
        }
        $skills_features[] = [
            'icon'  => !empty($s->$ikey) ? clean_param($s->$ikey, PARAM_TEXT) : 'fa-circle',
            'title' => $ftitle,
            'desc'  => !empty($s->$dkey) ? clean_param($s->$dkey, PARAM_TEXT) : '',
        ];
    }

    // Video link -> playable url. YouTube => iframe embed; .mp4/.webm => <video>;
    // anything else => generic iframe (Vimeo etc). Detected first so a YouTube
    // video id can also seed the poster image below.
    $skills_videoraw    = !empty($s->fp_skills_videourl) ? clean_param($s->fp_skills_videourl, PARAM_URL) : '';
    $skills_video       = '';
    $skills_video_yt    = false;
    $skills_video_file  = false;
    $skills_video_ytid  = '';
    if ($skills_videoraw !== '') {
        if (preg_match('#(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})#', $skills_videoraw, $ytm)) {
            $skills_video_yt   = true;
            $skills_video_ytid = $ytm[1];
            $skills_video      = 'https://www.youtube.com/embed/' . $ytm[1] . '?autoplay=1&rel=0';
        } else if (preg_match('#\.(mp4|webm|ogg)(\?.*)?$#i', $skills_videoraw)) {
            $skills_video_file = true;
            $skills_video      = $skills_videoraw;
        } else {
            $skills_video = $skills_videoraw;
        }
    }

    // Media image (poster). Priority: (1) an image the admin uploaded, then
    // (2) if the video is a YouTube link, the video's own thumbnail (so the
    // section is never blank even when no image is uploaded), then
    // (3) a bundled default. For (2) we use maxresdefault (crisp 16:9) and hand
    // hqdefault to the template as a JS fallback, since not every video has a
    // maxres thumbnail but hqdefault always exists.
    $skills_image        = '';
    $skills_image_ytfb   = '';
    if (!empty($s->fp_skills_image)) {
        $skills_image = $theme->setting_file_url('fp_skills_image', 'fp_skills_image');
    }
    if (empty($skills_image) && $skills_video_ytid !== '') {
        $skills_image      = 'https://img.youtube.com/vi/' . $skills_video_ytid . '/maxresdefault.jpg';
        $skills_image_ytfb = 'https://img.youtube.com/vi/' . $skills_video_ytid . '/hqdefault.jpg';
    }
    if (empty($skills_image)) {
        $skills_image = (new \moodle_url('/theme/edzcorp/pix/skills-default.svg'))->out(false);
    }

    // =========================================================================
    // 3. POPULAR TOPICS (course categories)
    //    Source (fp_topics_source): 'alltop'   = direct top-level categories,
    //                               'all'      = every category (flattened),
    //                               'specific' = one chosen category as a tile.
    //    Style  (fp_topics_style):  'buttons'  = pill chips (default),
    //                               'cards'    = icon cards (6 or 8 per row).
    // =========================================================================

    $topics_heading = !empty($s->fp_topics_heading)
        ? clean_param($s->fp_topics_heading, PARAM_TEXT)
        : 'Popular topics to learn';

    $topics_source = !empty($s->fp_topics_source) ? clean_param($s->fp_topics_source, PARAM_ALPHA) : 'alltop';
    if (!in_array($topics_source, ['alltop', 'all', 'specific'], true)) {
        $topics_source = 'alltop';
    }
    $topics_is_cards    = (isset($s->fp_topics_style) && (string)$s->fp_topics_style === 'cards');
    $topics_percard     = (isset($s->fp_topics_percard) && (string)$s->fp_topics_percard === '8') ? 8 : 6;
    // "Specific category" now supports MULTIPLE ids (configmultiselect stores a
    // comma-separated string, e.g. "3,5,7"). Parse into a clean int list.
    $topics_specific_ids = [];
    if (!empty($s->fp_topics_specific)) {
        foreach (explode(',', (string)$s->fp_topics_specific) as $sid) {
            $sid = (int)trim($sid);
            if ($sid > 0) {
                $topics_specific_ids[] = $sid;
            }
        }
    }

    // Colour palette — cycles through if there are many categories.
    $topic_colors = ['#7c3aed', '#0891b2', '#059669', '#d97706', '#dc2626',
                     '#db2777', '#6366f1', '#ea580c', '#0d9488'];
    // Curated Font Awesome icons for the card style — cycles alongside colours.
    $topic_icons = ['fa-chart-line', 'fa-code', 'fa-palette', 'fa-briefcase',
                    'fa-flask', 'fa-gears', 'fa-book-open', 'fa-globe',
                    'fa-lightbulb', 'fa-chart-pie', 'fa-microchip', 'fa-heart-pulse'];

    $fp_topics  = [];
    $color_idx  = 0;

    // Append one category as a topic entry. Hidden categories are NEVER shown on
    // the public front page — not even to admins who could otherwise see them.
    $add_topic = function (\core_course_category $cat)
            use (&$fp_topics, &$color_idx, $topic_colors, $topic_icons) {
        if (!$cat->visible) {
            return;
        }
        $course_count = $cat->get_courses_count(['recursive' => true]);
        $count_str    = $course_count . ' ' . ($course_count === 1 ? 'course' : 'courses');
        $fp_topics[]  = [
            'name'  => $cat->get_formatted_name(),
            'url'   => (string)(new \moodle_url('/course/index.php', ['categoryid' => $cat->id])),
            'count' => $count_str,
            'color' => $topic_colors[$color_idx % count($topic_colors)],
            'icon'  => $topic_icons[$color_idx % count($topic_icons)],
        ];
        $color_idx++;
    };

    try {
        if ($topics_source === 'specific' && !empty($topics_specific_ids)) {
            // One or more chosen categories, each shown as its own featured tile,
            // in the order selected.
            foreach ($topics_specific_ids as $cid) {
                $cat = \core_course_category::get($cid, IGNORE_MISSING);
                if ($cat) {
                    $add_topic($cat);
                }
            }
        } else if ($topics_source === 'all') {
            // Every category the user can see (top-level + sub-categories).
            foreach (\core_course_category::get_all() as $cat) {
                $add_topic($cat);
            }
        } else {
            // Default 'alltop' — direct children of the root (top-level categories).
            $root = \core_course_category::get(0);
            foreach ($root->get_children() as $cat) {
                $add_topic($cat);
            }
        }
    } catch (\Exception $e) {
        // Fail silently — topics section will be hidden via {{#fp_has_topics}}.
        debugging('theme_edzcorp frontpage: category fetch failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    // First 3 topics used as skill chips in the spotlight panel.
    $spotlight_chips = array_slice(array_values($fp_topics), 0, 3);

    // =========================================================================
    // 4. RECENTLY LAUNCHED COURSES
    // =========================================================================

    $courses_heading = !empty($s->fp_courses_heading)
        ? clean_param($s->fp_courses_heading, PARAM_TEXT)
        : 'Recently launched';

    $fp_courses = [];

    // If the admin specified course IDs, use those (in the order given).
    $courses_ids_raw = !empty($s->fp_courses_ids) ? trim($s->fp_courses_ids) : '';
    $use_ids         = !empty($courses_ids_raw);

    if ($use_ids) {
        $ids = array_filter(array_map('intval', preg_split('/[\s,]+/', $courses_ids_raw)));
        $ordered_courses = [];
        foreach ($ids as $cid) {
            try {
                $course = get_course($cid);
                if (!$course || empty($course->visible)) {
                    continue;
                }
                $ordered_courses[] = $course;
            } catch (\Exception $e) {
                continue;
            }
        }
    } else {
        // Fallback: 4 most recently created visible courses (excluding SITEID).
        $ordered_courses = array_values(
            $DB->get_records_select(
                'course',
                'visible = 1 AND id != :siteid',
                ['siteid' => SITEID],
                'timecreated DESC',
                '*',
                0,
                4
            )
        );
    }

    // Teacher role ids — resolved once, reused for every course below.
    $teacherroleids = [];
    foreach (['editingteacher', 'teacher'] as $rs) {
        $rid = $DB->get_field('role', 'id', ['shortname' => $rs]);
        if ($rid) {
            $teacherroleids[] = $rid;
        }
    }
    $gradidx = 0;

    foreach ($ordered_courses as $course) {
        try {
            $ctx = \context_course::instance($course->id, IGNORE_MISSING);
            if (!$ctx) {
                continue;
            }

            // Enrolled user count.
            $enrolled = count_enrolled_users($ctx);

            // Section count.
            $sections = isset($course->numsections) ? (int)$course->numsections : 0;

            // Category name.
            $cat_name = '';
            try {
                $cat = \core_course_category::get($course->category, IGNORE_MISSING);
                if ($cat) {
                    $cat_name = $cat->get_formatted_name();
                }
            } catch (\Exception $e) {
                // Category not visible or missing — leave blank.
            }

            // Course summary — plain text, shortened for the card.
            $summary = '';
            if (!empty($course->summary)) {
                $summarytext = content_to_text(
                    format_text($course->summary, $course->summaryformat ?? FORMAT_HTML,
                        ['context' => $ctx, 'para' => false]),
                    false
                );
                $summary = shorten_text(trim($summarytext), 120);
            }

            // First teacher (editingteacher preferred, then teacher).
            $teacher    = '';
            $userfields = 'u.id,u.firstname,u.lastname,u.picture,u.imagealt,'
                        . 'u.email,u.firstnamephonetic,u.lastnamephonetic,'
                        . 'u.middlename,u.alternatename';
            foreach ($teacherroleids as $rid) {
                $users = get_role_users($rid, $ctx, false, $userfields, 'u.lastname', false, 0, 1);
                if (!empty($users)) {
                    $teacher = fullname(reset($users));
                    break;
                }
            }

            // Course image (overview files) — empty string when none uploaded.
            $image = \core_course\external\course_summary_exporter::get_course_image($course);

            $fp_courses[] = [
                'name'      => format_string($course->fullname, true, ['context' => $ctx]),
                'url'       => (string)(new \moodle_url('/course/view.php', ['id' => $course->id])),
                'category'  => $cat_name,
                'sections'  => $sections . ' ' . ($sections === 1 ? 'module' : 'modules'),
                'enrolled'  => number_format($enrolled) . ' enrolled',
                'summary'   => $summary,
                'teacher'   => $teacher,
                'image'     => $image ?: '',
                'gradclass' => 'g' . ($gradidx % 5),
            ];
            $gradidx++;
        } catch (\Exception $e) {
            debugging('theme_edzcorp frontpage: course context error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            continue;
        }
    }

    // =========================================================================
    // Return merged context array.
    // =========================================================================
    return [
        // Employee spotlight.
        'fp_emp_name'           => $emp_name,
        'fp_emp_title'          => $emp_title,
        'fp_emp_dept'           => $emp_dept,
        'fp_emp_quote'          => $emp_quote,
        'fp_emp_stars'          => $stars_html,
        'fp_emp_initials'       => $initials,
        'fp_emp_photo'          => ($emp_photo_url !== '' ? $emp_photo_url
                                    : (new \moodle_url('/theme/edzcorp/pix/spotlight-avatar.svg'))->out(false)),
        'fp_emp_has_photo'      => true,
        'fp_emp_style'          => $emp_style,
        'fp_emp_rating'         => $emp_rating,
        'fp_spotlight_title_html' => $spotlight_title_html,
        'fp_spotlight_bg'       => $spotlight_bg,
        'fp_spotlight_chips'    => $spotlight_chips,
        // Show the spotlight only when the admin has configured it (name, quote
        // or photo) — otherwise it stays hidden rather than showing placeholder data.
        'fp_has_spotlight'      => ((!empty($s->fp_emp_name) || !empty($s->fp_emp_quote) || !empty($emp_photo_url)) && $en('fp_emp_enable')),

        // Hero.
        'fp_hero_eyebrow'    => $hero_eyebrow,
        'fp_hero_headline'   => $hero_headline,
        'fp_hero_sub'        => $hero_sub,
        'fp_hero_btn1_label' => $btn1_label,
        'fp_hero_btn1_url'   => $btn1_url,
        'fp_hero_btn2_label' => $btn2_label,
        'fp_hero_btn2_url'   => $btn2_url,
        'fp_stats'           => $fp_stats,

        // Top hero (first section).
        'fp_top_has'          => $en('fp_top_enable'),
        'fp_hero_show'        => $en('fp_hero_enable'),
        'fp_philosophy_show'  => $en('fp_philosophy_enable'),
        'fp_top_eyebrow'      => $top_eyebrow,
        'fp_top_heading'      => $top_heading,
        'fp_top_subtext'      => $top_subtext,
        'fp_top_cta_url'      => $top_cta_url,
        'fp_top_cta_label'    => $top_cta_label,
        'fp_top_has_q'        => ($top_qlabel !== ''),
        'fp_top_qlabel'       => $top_qlabel,
        'fp_top_qurl'         => ($top_qurl !== '' ? $top_qurl : '#'),
        'fp_top_image'        => $top_image,
        'fp_top_badge'        => $top_badge,
        'fp_top_has_badge'    => ($top_badge !== ''),
        'fp_top_has_badge_url'=> ($top_badge_url !== ''),
        'fp_top_badge_url'    => $top_badge_url,
        'fp_top_stats'        => $top_stats,
        'fp_top_avatars_text' => $top_avatars_text,

        // Skills section.
        'fp_skills_has'            => ($skills_has && $en('fp_skills_enable')),
        'fp_skills_title'          => $skills_title,
        'fp_skills_lead'           => $skills_lead,
        'fp_skills_features'       => $skills_features,
        'fp_skills_image'          => $skills_image,
        'fp_skills_image_ytfb'     => $skills_image_ytfb,
        'fp_skills_has_image'      => ($skills_image !== ''),
        'fp_skills_has_video'      => ($skills_video !== ''),
        'fp_skills_video'          => $skills_video,
        'fp_skills_video_isyoutube'=> $skills_video_yt,
        'fp_skills_video_isfile'   => $skills_video_file,

        // Topics.
        'fp_topics_heading'    => $topics_heading,
        'fp_topics'            => $fp_topics,
        'fp_has_topics'        => (!empty($fp_topics) && $en('fp_topics_enable')),
        'fp_topics_is_cards'   => $topics_is_cards,
        'fp_topics_grid_class' => 'edz-topics-cards--' . $topics_percard,

        // Courses.
        'fp_courses_heading' => $courses_heading,
        'fp_courses'         => $fp_courses,
        'fp_has_courses'     => (!empty($fp_courses) && $en('fp_courses_enable')),
    ];
}
