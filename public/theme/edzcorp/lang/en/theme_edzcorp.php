<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp theme language strings (English).
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin identity.
$string['pluginname']    = 'EdzCorp';
$string['choosereadme']  = 'EdzCorp is a modern SaaS-style LMS theme for Moodle 5.x built on Boost, featuring a collapsible sidebar, dark mode, and flexible block regions.';
$string['configtitle']   = 'EdzCorp settings';

// ---- Settings pages ----
$string['generalsettings']     = 'General';
$string['generalsettingsdesc'] = 'Upload branding assets such as logo, sidebar logo, and mobile logo.';

$string['colorsettings']     = 'Colours';
$string['colorsettingsdesc'] = 'Customise the colour palette used throughout the theme.';

$string['loginsettings']     = 'Login';
$string['loginsettingsdesc'] = 'Customise the appearance of the login page.';

// ---- Branding / logos ----
$string['logo']          = 'Logo';
$string['logodesc']      = 'Upload the full site logo (PNG, JPG, SVG or WebP). Displayed in the top navigation bar.';
$string['sidebarlogo']   = 'Sidebar logo';
$string['sidebarlogodesc'] = 'Upload a logo for the sidebar. A square or short-wide image works best (max height 40 px). When the sidebar is collapsed only an icon is shown.';
$string['mobilelogo']    = 'Mobile logo';
$string['mobilelogodesc'] = 'Upload a logo specifically sized for small screens (optional).';

// ---- Colours ----
$string['primarycolor']         = 'Primary colour';
$string['primarycolordesc']     = 'Main brand colour — used for active sidebar items, buttons and links.';
$string['accentcolor']          = 'Accent colour';
$string['accentcolordesc']      = 'Secondary brand colour — used for badges, secondary buttons and highlights.';
$string['sidebarbgcolor']       = 'Sidebar background';
$string['sidebarbgcolordesc']   = 'Background colour of the sidebar navigation panel.';
$string['sidebartextcolor']     = 'Sidebar text colour';
$string['sidebartextcolordesc'] = 'Default text and icon colour inside the sidebar.';
$string['sidebarhovercolor']    = 'Sidebar hover colour';
$string['sidebarhovercolordesc'] = 'Background colour of sidebar links on hover and focus.';
$string['sidebaractivecolor']     = 'Sidebar active item colour';
$string['sidebaractivecolordesc'] = 'Background colour of the currently active sidebar item. This is independent of the site primary colour so you can use a dark sidebar with a different accent without affecting links elsewhere on the page.';
$string['sidebaractivestyle']     = 'Sidebar active-link style';
$string['sidebaractiveStyledesc'] = 'How the currently active sidebar link is highlighted. "Text colour only" changes the icon and text colour with a left accent line (works on any sidebar background colour, including white). "Background colour" fills the row with the active colour.';
$string['sidebarstyle_textonly']  = 'Text colour only (recommended)';
$string['sidebarstyle_bgcolor']   = 'Background colour fill';

// ---- Login ----
$string['logintitle']    = 'Welcome back';
$string['loginsubtitle'] = 'Sign in to continue';

$string['loginlayout']       = 'Login page layout';
$string['loginlayout_desc']  = 'Choose the visual style of the login page. "Full-screen split" fills the entire viewport. "Centered card" floats a bordered card on a gradient background — great for giving the page breathing room and a polished look.';
$string['loginlayout_split'] = 'Full-screen split (slider left / form right)';
$string['loginlayout_card']  = 'Centered card with margins';

$string['loginsliderheading']     = 'Left panel heading';
$string['loginsliderheading_desc']= 'Main heading text displayed at the bottom of the left image slider panel (optional).';

$string['loginsliderimage1']      = 'Slider image 1';
$string['loginsliderimage1_desc'] = 'Upload the first background image for the login page slider (JPG, PNG or WebP). If left blank the left panel shows a gradient.';
$string['loginslidercaption1']    = 'Slider image 1 caption';
$string['loginslidercaption1_desc'] = 'Caption text displayed below the heading for the first slide (optional).';

$string['loginsliderimage2']      = 'Slider image 2';
$string['loginsliderimage2_desc'] = 'Upload the second background image for the slider. When set, the slider auto-advances between the two images every 5 seconds.';
$string['loginslidercaption2']    = 'Slider image 2 caption';
$string['loginslidercaption2_desc'] = 'Caption text for the second slide (optional).';

// ---- Navigation / Sidebar ----
$string['mainnavigation']   = 'Main navigation';
$string['togglesidebar']    = 'Toggle sidebar';
$string['togglemobilemenu'] = 'Open navigation menu';
$string['toggledarkmode']   = 'Toggle dark mode';
$string['darkmode']         = 'Dark mode';
$string['lightmode']        = 'Light mode';

// Nav item labels.
$string['navhome']        = 'Home';
$string['navdashboard']   = 'Dashboard';
$string['navmycourses']   = 'My courses';
$string['navcatalogues']  = 'Catalogues';
$string['navresources']   = 'Resources';
$string['navwebinars']    = 'Webinars';
$string['navcalendar']    = 'Calendar';
$string['navmessages']    = 'Messages';
$string['navsiteadmin']   = 'Admin Settings';
$string['navprofile']     = 'My profile';
$string['navgrades']      = 'Grades';
$string['navreports']     = 'Reports';
$string['navachievements'] = 'Achievements';
$string['navperformance']    = 'Performance';
$string['navonboarding']     = 'Onboarding';
$string['navcoursecatalogue'] = 'Catalogue';
$string['navmanageteam']     = 'Manage team';
$string['navcourseindex']    = 'Course index';
$string['navgroupcourse']    = 'This course';
$string['navlogout']      = 'Log out';

// Nav group headings (small caps labels above each set of links).
$string['navgrouplearning']  = 'Learning';
$string['navgroupanalytics'] = 'Analytics';
$string['navgroupevents']    = 'Events';
$string['navgroupadmin']     = 'Administration';
$string['navgroupauthoring'] = 'Authoring';
$string['navscormbuilder']  = 'SCORM Builder';
$string['navcoursegen']     = 'AI Course Builder';

// ---- Block regions ----
$string['region-side-pre']  = 'Left';
$string['region-side-post'] = 'Right';

// ---- Accessibility ----
$string['skipto'] = 'Skip to main content';

// ---- Footer ----
$string['footnote']              = '';
$string['footersettings']        = 'Footer';
$string['footersettingsdesc']    = 'Configure the page footer — copyright text, social media links, and app store links.';

$string['footercopyright']       = 'Copyright message';
$string['footercopyright_desc']  = 'HTML displayed as the copyright notice in the footer. You can use basic HTML tags. Defaults to the current year and "EdzCorp".';
$string['footertext']            = 'Tagline';
$string['footertext_desc']       = 'Optional short tagline displayed next to the copyright message (plain text).';

$string['socialsheading']        = 'Social media links';
$string['footerfacebook']        = 'Facebook URL';
$string['footerfacebook_desc']   = 'Full URL to your Facebook page (e.g. https://facebook.com/yourpage). Leave blank to hide.';
$string['footerinstagram']       = 'Instagram URL';
$string['footerinstagram_desc']  = 'Full URL to your Instagram profile. Leave blank to hide.';
$string['footerlinkedin']        = 'LinkedIn URL';
$string['footerlinkedin_desc']   = 'Full URL to your LinkedIn page. Leave blank to hide.';
$string['footeryoutube']         = 'YouTube URL';
$string['footeryoutube_desc']    = 'Full URL to your YouTube channel. Leave blank to hide.';
$string['footertwitter']         = 'X / Twitter URL';
$string['footertwitter_desc']    = 'Full URL to your X (formerly Twitter) profile. Leave blank to hide.';

$string['appheading']            = 'Mobile app download links';
$string['footerappstore']        = 'App Store URL';
$string['footerappstore_desc']   = 'Link to your iOS app on the Apple App Store. Leave blank to hide.';
$string['footergoogleplay']      = 'Google Play URL';
$string['footergoogleplay_desc'] = 'Link to your Android app on Google Play. Leave blank to hide.';

// Footer template strings.
$string['followus']             = 'Follow us';
$string['downloadon']           = 'Download on the';
$string['downloadappstore']     = 'Download on the App Store';
$string['getiton']              = 'Get it on';
$string['downloadgoogleplay']   = 'Get it on Google Play';
$string['currentyear']          = '';

// ---- Typography / Google Fonts ----
$string['typographysettings']     = 'Typography';
$string['typographysettingsdesc'] = 'Choose Google Fonts for headings, subheadings, and body text. Select "Default" to use the system font stack (fastest, no external request). Changes take effect after saving and purging caches.';

$string['headingfont']      = 'Heading font (h1, h2, h3)';
$string['headingfont_desc'] = 'Font used for major headings. Applied to h1, h2, and h3 elements.';

$string['subheadingfont']      = 'Subheading font (h4, h5, h6)';
$string['subheadingfont_desc'] = 'Font used for smaller headings. Applied to h4, h5, and h6 elements.';

$string['bodyfont']      = 'Body font (paragraphs, labels, inputs)';
$string['bodyfont_desc'] = 'Font used for all running body text — paragraphs, form labels, table cells, and input fields.';

$string['fontdefault'] = 'Default (system font stack)';

// ============================================================================
// Frontpage settings
// ============================================================================

$string['frontpagesettings'] = 'Frontpage';

// ── Employee spotlight tab ───────────────────────────────────────────────────
$string['fp_emp_heading']     = 'Employee spotlight';
$string['fp_emp_headingdesc'] = 'Configure the featured employee who appears in the top spotlight section of the site home page. Upload a portrait photo for the best result — the initials avatar is shown as a fallback.';

$string['fp_emp_photo']     = 'Employee photo';
$string['fp_emp_photodesc'] = 'Upload a portrait photo (PNG, JPG, or WebP). Recommended size: 320 × 400 px. The image fills the left panel of the spotlight section. If no photo is uploaded, a stylised initials avatar is shown instead.';

$string['fp_emp_name']     = 'Employee name';
$string['fp_emp_namedesc'] = 'Full name of the featured employee. Displayed below their quote and used to generate the fallback initials avatar.';

$string['fp_emp_title']     = 'Job title';
$string['fp_emp_titledesc'] = 'The employee\'s current job title (e.g. "Product Lead").';

$string['fp_emp_dept']     = 'Department';
$string['fp_emp_deptdesc'] = 'The employee\'s department or team (e.g. "Innovation & Technology").';

$string['fp_emp_rating']      = 'Star rating';
$string['fp_emp_ratingdesc']  = 'How many stars to show (filled ★ glyphs). Choose "No rating" to hide the stars entirely.';
$string['fp_emp_rating_none'] = 'No rating (hide stars)';

$string['fp_emp_label']     = 'Eyebrow label';
$string['fp_emp_labeldesc'] = 'Small caps label shown above the content (the pill). Leave blank to use "Employee Spotlight". Example: "AIMA Excellence in Management".';

$string['fp_emp_headline']     = 'Headline (optional)';
$string['fp_emp_headlinedesc'] = 'A large heading shown under the eyebrow label. Leave blank to hide it. Example: "Recognising Leadership and Management Excellence".';

$string['fp_emp_btnlabel']     = 'Button label (optional)';
$string['fp_emp_btnlabeldesc'] = 'Text for a call-to-action button. The button only appears when both a label and a URL are set.';
$string['fp_emp_btnurl']       = 'Button URL (optional)';
$string['fp_emp_btnurldesc']   = 'Where the button links to (internal or external URL).';

$string['fp_emp_quote']     = 'Feedback quote / description';
$string['fp_emp_quotedesc'] = 'The body text — a pull-quote from a featured person, or a short description for the section. Basic HTML is allowed.';

// Spotlight appearance
$string['fp_spotlight_appearance_heading']     = 'Spotlight panel appearance';
$string['fp_spotlight_appearance_headingdesc'] = 'Controls the bold headline, accent word highlight, and background colour for the employee spotlight section.';
$string['fp_spotlight_title']     = 'Spotlight headline';
$string['fp_spotlight_titledesc'] = 'The large bold headline displayed on the left side of the spotlight section. Example: "Inspiring Education."';
$string['fp_spotlight_accent']     = 'Accent word';
$string['fp_spotlight_accentdesc'] = 'One word from the headline above to highlight in the brand accent colour. Must match exactly (case-sensitive). Example: "Education"';
$string['fp_spotlight_bg']     = 'Section background colour';
$string['fp_spotlight_bgdesc'] = 'Optional flat background colour for the spotlight section. Leave empty to use the theme\'s purple gradient (recommended). Setting a colour here replaces the gradient.';
$string['employeespotlight'] = 'Employee Spotlight';

// ── Hero section ─────────────────────────────────────────────────────────────
$string['fp_hero_heading']     = 'Hero section';
$string['fp_hero_headingdesc'] = 'The main hero area sits below the spotlight. It contains an eyebrow label, a large headline, a subtitle, two CTA buttons, and a row of platform statistics.';

$string['fp_hero_eyebrow']     = 'Eyebrow label';
$string['fp_hero_eyebrowdesc'] = 'Small uppercase text shown above the main headline (e.g. "Enterprise learning platform").';

$string['fp_hero_headline']     = 'Main headline';
$string['fp_hero_headlinedesc'] = 'The primary value proposition displayed in large text. Keep it punchy — one or two short sentences.';

$string['fp_hero_sub']     = 'Subtitle';
$string['fp_hero_subdesc'] = 'A brief supporting line shown below the headline. One sentence is ideal.';

$string['fp_hero_btn1_label']     = 'Primary button — label';
$string['fp_hero_btn1_labeldesc'] = 'Text shown on the filled (primary) call-to-action button.';

$string['fp_hero_btn1_url']     = 'Primary button — URL';
$string['fp_hero_btn1_urldesc'] = 'Destination URL for the primary button. Use a relative path (e.g. /course/index.php) or a full URL.';

$string['fp_hero_btn2_label']     = 'Secondary button — label';
$string['fp_hero_btn2_labeldesc'] = 'Text shown on the outlined (secondary) call-to-action button.';

$string['fp_hero_btn2_url']     = 'Secondary button — URL';
$string['fp_hero_btn2_urldesc'] = 'Destination URL for the secondary button.';

// ── Stats row ─────────────────────────────────────────────────────────────────
$string['fp_stats_heading']     = 'Platform statistics';
$string['fp_stats_headingdesc'] = 'Four stat tiles shown as a row below the CTA buttons. Each tile has a bold number (or short string) and a label.';

$string['fp_stat_num']    = 'Stat';
$string['fp_stat_number'] = 'Number / value';
$string['fp_stat_label']  = 'Label';

// ── Topics & courses ──────────────────────────────────────────────────────────
$string['fp_content_heading']     = 'Topics & recently launched courses';
$string['fp_content_headingdesc'] = 'The "Popular topics" pills are auto-populated from top-level course categories. The "Recently launched" grid shows courses you choose, or falls back to the 4 most recently created visible courses.';

$string['fp_topics_heading']     = 'Topics section heading';
$string['fp_topics_headingdesc'] = 'Section label shown above the category pills (displayed in small caps).';
$string['fp_topics_bg']          = 'Topics band background colour';
$string['fp_topics_bgdesc']      = 'Background colour of the "Popular topics" band. Leave blank for the default dark band. Note: the pills and heading use light text, so pick a dark colour for good contrast.';

$string['fp_topics_source']          = 'Topics source';
$string['fp_topics_sourcedesc']      = 'Which categories feed the Popular topics section.';
$string['fp_topics_source_alltop']   = 'All top-level categories';
$string['fp_topics_source_all']      = 'All categories (including sub-categories)';
$string['fp_topics_source_specific'] = 'One specific category';
$string['fp_topics_specific']        = 'Specific category(ies)';
$string['fp_topics_specificdesc']    = 'Used only when "Topics source" is set to "One specific category". Hold Ctrl (Cmd on Mac) to select more than one — each selected category is shown as its own tile, in the order picked. Hidden categories are never shown.';
$string['fp_topics_specific_none']   = '— None selected —';
$string['fp_topics_style']           = 'Topics display style';
$string['fp_topics_styledesc']       = 'Show topics as pill buttons (default) or as icon cards.';
$string['fp_topics_style_buttons']   = 'Buttons (pills)';
$string['fp_topics_style_cards']     = 'Cards (icon tiles)';
$string['fp_topics_percard']         = 'Cards per row';
$string['fp_topics_percarddesc']     = 'How many cards per row on wide screens (used only for the card style). Fewer are shown automatically on smaller screens.';

$string['fp_courses_heading']     = 'Recently launched section heading';
$string['fp_courses_headingdesc'] = 'Section label shown above the course cards.';

$string['fp_courses_source']          = 'Featured courses source';
$string['fp_courses_sourcedesc']      = 'How to choose the courses shown in this section.';
$string['fp_courses_source_latest']   = 'Latest created courses';
$string['fp_courses_source_enrolled'] = 'Most enrolled courses';
$string['fp_courses_source_ids']      = 'Specific course IDs (listed below)';

$string['fp_courses_ids']     = 'Featured course IDs';
$string['fp_courses_idsdesc'] = 'Used only when the source above is "Specific course IDs". Enter course IDs (comma or newline separated) in the order you want them shown. If left blank, the section falls back to the latest created courses. Find a course ID in its URL: /course/view.php?id=<strong>42</strong>';

// Page background
$string['pagebgcolor']     = 'Page background colour';
$string['pagebgcolordesc'] = 'Background colour of the main content area (--edz-page-bg). Default is #f4f6fb.';
$string['maininnerbg']     = 'Content panel background colour';
$string['maininnerbgdesc'] = 'Background colour of the main content panel (.main-inner). Moodle hardcodes this to white; change it here. Default is #ffffff.';
$string['courseindexbg']     = 'Course index drawer background colour';
$string['courseindexbgdesc'] = 'Background colour of the course index drawer (the left-side course contents panel). Default is #eeeeee.';

// Added: typography base size + text colour settings.
$string['basefontsize'] = 'Base font size';
$string['basefontsize_desc'] = 'Shifts the entire type scale. Body text uses this size; headings and small text scale relative to it.';
$string['textcolor'] = 'Body text colour';
$string['textcolordesc'] = 'Main text colour used across page content (--edz-text-color).';
$string['textmutedcolor'] = 'Muted text colour';
$string['textmutedcolordesc'] = 'Secondary / helper text colour (--edz-text-muted).';
$string['headingcolor'] = 'Heading colour';
$string['headingcolordesc'] = 'Colour for headings h1-h6 (--edz-heading-color).';
$string['breadcrumbcolor'] = 'Breadcrumb link colour';
$string['breadcrumbcolordesc'] = 'Colour of breadcrumb links (--edz-breadcrumb-link).';

// Added: login hero fallback panel (shown when no slider images uploaded).
$string['loginhero_heading'] = 'Hero panel (no images)';
$string['loginhero_headingdesc'] = 'Shown on the left side of the login page when no slider images are uploaded. A clean branded panel with your site name, a heading, subtitle and optional stat cards.';
$string['loginhero_bg'] = 'Hero background colour';
$string['loginhero_bgdesc'] = 'Background colour of the hero panel, used only when no background image is set. Leave blank to use the theme primary colour.';
$string['loginhero_bgimage'] = 'Hero background image';
$string['loginhero_bgimagedesc'] = 'Optional single background image for the hero panel (PNG/JPG/WebP). A dark overlay is added automatically for text legibility. When empty, the background colour above (or the primary colour) is used.';
$string['loginhero_eyebrow'] = 'Hero eyebrow (small title)';
$string['loginhero_eyebrowdesc'] = 'Small accent-coloured line shown directly above the big title (e.g. "AIMA Learning Management System"). Leave blank to use the site name.';
$string['loginhero_title'] = 'Hero title';
$string['loginhero_titledesc'] = 'Large headline shown in the centre of the hero panel.';
$string['loginhero_title_default'] = 'Intelligent Learning, Powered by AI';
$string['loginhero_subtitle'] = 'Hero subtitle';
$string['loginhero_subtitledesc'] = 'Supporting text under the hero title. Press Enter for a new line — leave a blank line between paragraphs. Basic HTML (e.g. &lt;br&gt;) is allowed.';
$string['loginhero_subtitle_default'] = 'Upload any content — PDFs, videos, links — and get instant summaries, flashcards, quizzes, and glossaries. Build Learning Spaces your students will love.';
$string['loginhero_highlight'] = 'Highlight line';
$string['loginhero_highlightdesc'] = 'A short accent-coloured line shown under the subtitle (e.g. "Your learning journey. Anytime. Anywhere."). Leave blank to hide.';
$string['loginhero_statsenabled'] = 'Show feature boxes';
$string['loginhero_statsenableddesc'] = 'Show the three feature boxes at the bottom of the hero panel.';
$string['loginhero_staticon'] = 'Box {$a} — icon';
$string['loginhero_staticondesc'] = 'Font Awesome icon shown at the top of the box (e.g. fa-regular fa-clock, fa-solid fa-chart-line).';
$string['loginhero_statvalue'] = 'Box {$a} — big title';
$string['loginhero_statlabel'] = 'Box {$a} — small caption';

// Login — right panel support + technology partner.
$string['loginsupport_text'] = 'Support prompt text';
$string['loginsupport_textdesc'] = 'Small line shown under the Log in button (e.g. "Need help accessing your account?"). Leave blank to hide.';
$string['loginsupport_label'] = 'Support link label';
$string['loginsupport_labeldesc'] = 'Clickable label for the support link (e.g. "Contact Learner Support").';
$string['loginsupport_url'] = 'Support link URL';
$string['loginsupport_urldesc'] = 'Where the support link goes (a page, or a mailto: address). Leave blank to show the label as plain text.';
$string['logintechpartner'] = 'Technology-partner line';
$string['logintechpartnerdesc'] = 'Small line pinned at the bottom of the login form (e.g. "Technology Partner: EDZLearn"). Leave blank to hide.';

// Frontpage — Skills section.
$string['fp_skills_admin']        = 'Skills section';
$string['fp_skills_admindesc']    = 'A two-column section (heading + paragraph, three icon features, and a media image that plays a video on click). Leave the section title blank to hide the whole section.';
$string['fp_skills_title']        = 'Section title';
$string['fp_skills_titledesc']    = 'Large left-hand heading, e.g. “Get the skills you need for a job that is in demand.” Leave blank to hide the section.';
$string['fp_skills_lead']         = 'Lead paragraph';
$string['fp_skills_leaddesc']     = 'Short paragraph shown top-right, next to the heading.';
$string['fp_skills_feat_icon']    = 'Feature {$a} — icon';
$string['fp_skills_feat_icondesc']= 'Font Awesome icon name, e.g. fa-user-tie, fa-people-group, fa-arrows-left-right.';
$string['fp_skills_feat_title']   = 'Feature {$a} — title';
$string['fp_skills_feat_desc']    = 'Feature {$a} — description';
$string['fp_skills_image']        = 'Media image';
$string['fp_skills_imagedesc']    = 'Right-hand image (PNG/JPG/WebP). Acts as the poster; clicking it plays the video link below if set.';
$string['fp_skills_videourl']     = 'Video link';
$string['fp_skills_videourldesc'] = 'YouTube URL or a direct video file (.mp4). Clicking the image opens this in a player. Leave blank to show just the image.';
$string['fp_skills_playvideo']    = 'Play video';
$string['fp_skills_closevideo']   = 'Close video';

// Employee spotlight — style.
$string['fp_emp_style']       = 'Spotlight style';
$string['fp_emp_styledesc']   = 'Choose how the employee spotlight looks. The photo fills its whole panel in both styles.';
$string['fp_emp_style_card']  = 'Testimonial card (light)';
$string['fp_emp_style_panel'] = 'Accent panel (dark, premium)';

// Frontpage — Top hero (first section).
$string['fp_top_admin']         = 'Top hero (first section)';
$string['fp_top_admindesc']     = 'The first section of the homepage: eyebrow, big heading, subtext, a Get Started button (auto Login / Dashboard), a centre illustration with a rotating badge, and three stats with an avatar row.';
$string['fp_top_eyebrow']       = 'Eyebrow label';
$string['fp_top_heading']       = 'Heading';
$string['fp_top_subtext']       = 'Subtext';
$string['fp_top_btnlabel']      = 'Button label';
$string['fp_top_btnlabeldesc']  = 'Leave blank to auto-switch: “Log in” for visitors, “Dashboard” for signed-in users. The button always links to login / dashboard accordingly.';
$string['fp_top_qlabel']        = '“Have a question?” link text';
$string['fp_top_qurl']          = '“Have a question?” link URL';
$string['fp_top_image']         = 'Centre illustration';
$string['fp_top_imagedesc']     = 'Large centre image (PNG/JPG/WebP/SVG). A bundled illustration is shown if empty.';
$string['fp_top_badge']         = 'Circular badge text';
$string['fp_top_badge_url']     = 'Circular badge link (optional)';
$string['fp_top_stat_num']      = 'Stat {$a} — number';
$string['fp_top_stat_label']    = 'Stat {$a} — label';
$string['fp_top_stat_icon']     = 'Stat {$a} — icon';
$string['fp_top_stat_icondesc'] = 'Font Awesome icon for this stat (Split layout). Use "fa-regular …" for outline/line icons (e.g. fa-regular fa-user, fa-regular fa-clock) or "fa-solid …" for filled. A bare name (e.g. fa-clock) defaults to filled.';
$string['fp_top_avatars_text']  = 'Avatar row tagline';
$string['fp_top_login']         = 'Log in';
$string['fp_top_dashboard']     = 'Dashboard';
$string['fp_top_badge_aria']    = 'Learn more';

// Top hero — layout switch.
$string['fp_top_layout']         = 'Hero layout';
$string['fp_top_layoutdesc']     = 'Choose the hero design. Both layouts reuse the same content settings below, so you can switch any time. "Split with search" adds the Explore Programmes button and the course search box.';
$string['fp_top_layout_classic'] = 'Classic (illustration centre, stats right)';
$string['fp_top_layout_split']   = 'Split with search (photo right, search box, stats below)';

$string['fp_top_photo_shape']         = 'Hero image shape (Split layout)';
$string['fp_top_photo_shapedesc']     = 'Shape of the right-side image in the Split layout.';
$string['fp_top_photo_shape_arch']    = 'AIMA arch (dome on the left; flush top, right & bottom)';
$string['fp_top_photo_shape_circle']  = 'Full circle';

// Top hero — Split-layout extras.
$string['fp_top_explore_label']     = 'Explore button label';
$string['fp_top_explore_labeldesc'] = 'Text on the primary (filled) button in the Split layout.';
$string['fp_top_explore_url']       = 'Explore button link';
$string['fp_top_explore_urldesc']   = 'Where the primary button goes. Defaults to the course catalogue (/local/edzallcourse/index.php).';
$string['fp_top_search_enable']      = 'Show hero search box';
$string['fp_top_search_enabledesc']  = 'Show a search box in the Split-layout hero with live suggestions for courses and categories.';
$string['fp_top_search_placeholder'] = 'Search box placeholder';
$string['fp_top_bg']                 = 'Hero background colour';
$string['fp_top_bgdesc']             = 'Background colour of the Split-layout hero panel. Leave blank for a soft tint of the theme’s brand colour.';

// Frontpage — section enable toggles.
$string['fp_section_enabledesc']  = 'Untick to hide this section from the homepage.';
$string['fp_top_enable']          = 'Enable — Top hero';
$string['fp_emp_enable']          = 'Enable — Employee spotlight';
$string['fp_hero_enable']         = 'Enable — Hero';
$string['fp_skills_enable']       = 'Enable — Skills';
$string['fp_philosophy_heading']  = 'Philosophy section';
$string['fp_philosophy_headingdesc'] = 'The dark “why we built it differently” band (AI roleplay / LMS / proctoring).';
$string['fp_philosophy_enable']   = 'Enable — Philosophy';
$string['fp_topics_enable']       = 'Enable — Popular topics';
$string['fp_courses_enable']      = 'Enable — Recently launched';

// Frontpage — navbar.
$string['fp_navbar_heading']     = 'Frontpage navbar';
$string['fp_navbar_headingdesc'] = 'Styling for the homepage top bar only (other pages are unaffected).';
$string['fp_navbar_bg']          = 'Navbar background colour';
$string['fp_navbar_bgdesc']      = 'Background colour of the contained homepage navbar. Default is a soft sage (#e9ede7).';
$string['fp_navbar_text']        = 'Navbar text colour';
$string['fp_navbar_textdesc']    = 'Colour of the homepage navbar logo, menu and text. Set a light colour if you choose a dark background. Default #1f2937.';
$string['fp_navbar_height']      = 'Navbar height';
$string['fp_navbar_heightdesc']  = 'Height of the homepage navbar. Increase this if your logo is tall — the logo is automatically capped to this height minus padding, so it always keeps top/bottom breathing room. Default 64px.';
