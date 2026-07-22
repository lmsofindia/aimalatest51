<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp — columns2 layout.
 * Used by: standard, coursecategory, frontpage, admin, mycourses,
 *          mydashboard, mypublic, report, and most general pages.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

use theme_edzcorp\util\sidebar;

// ---- Block drawer state (persisted per-user preference) ----
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

// Blocks are rendered into side-pre (Boost drawer convention).
$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks  = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

// uses-drawers enables Boost's drawer JS; theme-edzcorp identifies our custom chrome.
$extraclasses    = ['uses-drawers'];
$bodyattributes  = $OUTPUT->body_attributes($extraclasses);

// ---- Primary navigation (Boost navbar / user menu) ----
$primary     = new core\navigation\output\primary($PAGE);
$renderer    = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

// ---- Secondary navigation (tabs under the page header) ----
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

// ---- Activity / page header ----
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions()
    && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu  = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header      = $PAGE->activityheader;
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

// ── Edit mode button (Turn editing on/off) ─────────────────────────────────
// In Moodle 4+, edit_mode_link() renders the persistent edit toggle button.
// Wrap in method_exists() to be safe across Moodle versions.
$editmodebutton = '';
if (method_exists($OUTPUT, 'edit_mode_link')) {
    $editmodebutton = $OUTPUT->edit_mode_link();
}
// Fallback: manually build toggle if edit_mode_link() returned empty
// (Moodle 5.x sometimes returns empty on incourse/activity pages).
if (empty($editmodebutton) && $PAGE->user_allowed_editing()) {
    $editingon  = $PAGE->user_is_editing();
    // Toggle editing on the CURRENT page, then let Moodle strip the params.
    // Previously this was hardcoded to /course/view.php?id=<course>, which on
    // dashboard/admin pages (e.g. /my/indexsys.php) resolves to the SITE course
    // (id 1). Moodle then treats /course/view.php?id=1 as the site home and
    // redirects there — the "turn editing on bounces me to the home page" bug.
    // Using $PAGE->url keeps the toggle on whatever page we are actually editing.
    $editurl = new moodle_url($PAGE->url);
    $editurl->param('edit', $editingon ? 'off' : 'on');
    $editurl->param('sesskey', sesskey());
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

// ---- EdzCorp custom sidebar ----
$sidebarutil    = new sidebar($PAGE);
$sidebarcontext = $sidebarutil->get_context();

// ---- Template context ----
$templatecontext = array_merge($sidebarcontext, [
    'sitename'    => format_string($SITE->shortname, true,
                        ['context' => context_course::instance(SITEID), 'escape' => false]),
    'output'      => $OUTPUT,
    'bodyattributes' => $bodyattributes,

    // Drawers.
    'sidepreblocks'       => $blockshtml,
    'hasblocks'           => $hasblocks,
    'blockdraweropen'     => $blockdraweropen,
    'courseindex'         => '',   // No course index on non-course pages.
    'courseindexopen'     => false,
    'forceblockdraweropen' => $forceblockdraweropen,
    'addblockbutton'      => $addblockbutton,

    // Navigation.
    'primarymoremenu'    => $primarymenu['moremenu'] ?? false,
    'secondarymoremenu'  => $secondarynavigation ?: false,
    'mobileprimarynav'   => $primarymenu['mobileprimarynav'] ?? false,
    'usermenu'           => $usermenu_html,
    'langmenu'           => $primarymenu['lang'] ?? false,
    'editmodebutton'     => $editmodebutton,

    // Header / settings.
    'headercontent'              => $headercontent,
    'overflow'                   => $overflow,
    'regionmainsettingsmenu'     => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu'  => !empty($regionmainsettingsmenu),
]);

$templatecontext = array_merge(
    $templatecontext,
    theme_edzcorp_get_footer_context(theme_config::load('edzcorp'))
);

echo $OUTPUT->render_from_template('theme_edzcorp/columns2', $templatecontext);
