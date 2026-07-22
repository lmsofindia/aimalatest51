<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp — course layout.
 * Used by: course (main course page /course/view.php).
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

use theme_edzcorp\util\sidebar;

// ---- Drawer preferences ----
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

// Course index drawer (left) — built-in Moodle course TOC.
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

// Block drawer (right).
$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks  = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}
$bodyattributes = $OUTPUT->body_attributes($extraclasses);

// ---- Primary navigation ----
$primary     = new core\navigation\output\primary($PAGE);
$renderer    = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

// ---- Secondary navigation ----
$secondarynavigation = false;
$overflow            = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav  = $PAGE->has_tablist_secondary_navigation();
    $moremenu    = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

// ---- Header ----
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions()
    && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu  = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header        = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);


// ── Navbar usermenu: pre-render to HTML string (avoids "Array" in {{{usermenu}}}) ──
$usermenu_html = '';
if (!empty($primarymenu['user'])) {
    if (is_string($primarymenu['user'])) {
        $usermenu_html = $primarymenu['user'];
    } else {
        try {
            $usermenu_html = $OUTPUT->render_from_template('core/user_menu', $primarymenu['user']);
        } catch (\Exception $e) {
            debugging('theme_edzcorp: usermenu render failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}

// ── Edit mode button ─────────────────────────────────────────────────────────
$editmodebutton = '';
if (method_exists($OUTPUT, 'edit_mode_link')) {
    $editmodebutton = $OUTPUT->edit_mode_link();
}
// Fallback: manually build toggle if edit_mode_link() returned empty
// (Moodle 5.x sometimes returns empty on incourse/activity pages).
if (empty($editmodebutton) && $PAGE->user_allowed_editing()) {
    $editingon  = $PAGE->user_is_editing();
    // Toggle edit mode via core's /editmode.php (works on every page type and
    // redirects back to the current page). See columns2.php for the full note.
    $editurl = new moodle_url('/editmode.php', [
        'setmode' => $editingon ? 0 : 1,
        'context' => $PAGE->context->id,
        'pageurl' => $PAGE->url->out_as_local_url(false),
        'sesskey' => sesskey(),
    ]);
    $tooltiplabel   = $editingon ? get_string('turneditingoff', 'core') : get_string('turneditingon', 'core');
    $editmodebutton = html_writer::link(
        $editurl,
        '<i class="fa-solid fa-gear" aria-hidden="true"></i>',
        [
            'class'        => 'edz-editmode-icon' . ($editingon ? ' edz-editmode-icon--on' : ''),
            'aria-label'   => $tooltiplabel,
            'data-tooltip' => $tooltiplabel,
            'title'        => $tooltiplabel,
        ]
    );
}

// ---- EdzCorp sidebar ----
$sidebarutil    = new sidebar($PAGE);
$sidebarcontext = $sidebarutil->get_context();

$templatecontext = array_merge($sidebarcontext, [
    'sitename'    => format_string(
        $SITE->shortname,
        true,
        ['context' => context_course::instance(SITEID), 'escape' => false]
    ),
    'output'      => $OUTPUT,
    'bodyattributes' => $bodyattributes,

    'sidepreblocks'        => $blockshtml,
    'hasblocks'            => $hasblocks,
    'blockdraweropen'      => $blockdraweropen,
    'courseindex'          => $courseindex,
    'courseindexopen'      => $courseindexopen,
    'forceblockdraweropen' => $forceblockdraweropen,
    'addblockbutton'       => $addblockbutton,

    'primarymoremenu'   => $primarymenu['moremenu'] ?? false,
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav'  => $primarymenu['mobileprimarynav'] ?? false,
    'usermenu'          => $usermenu_html,
    'langmenu'          => $primarymenu['lang'] ?? false,
    'editmodebutton'    => $editmodebutton,

    'headercontent'             => $headercontent,
    'overflow'                  => $overflow,
    'regionmainsettingsmenu'    => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
]);

$templatecontext = array_merge(
    $templatecontext,
    theme_edzcorp_get_footer_context(theme_config::load('edzcorp'))
);

echo $OUTPUT->render_from_template('theme_edzcorp/course', $templatecontext);
