<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp — frontpage layout.
 *
 * Renders the custom 4-section enterprise homepage (employee spotlight,
 * hero, popular topics, recently launched courses) above the standard
 * Moodle site-home content area.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

use theme_edzcorp\util\sidebar;

// ── Block drawer state ────────────────────────────────────────────────────────
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

// Frontpage uses side-post for blocks (admin quick-add).
$blockshtml = $OUTPUT->blocks('side-post');
$hasblocks  = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$extraclasses    = ['uses-drawers'];
$bodyattributes  = $OUTPUT->body_attributes($extraclasses);

// ── Navigation ────────────────────────────────────────────────────────────────
$primary     = new core\navigation\output\primary($PAGE);
$renderer    = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

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

$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions()
    && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu  = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header        = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

// ── EdzCorp sidebar ───────────────────────────────────────────────────────────
$sidebarutil    = new sidebar($PAGE);
$sidebarcontext = $sidebarutil->get_context();

// ── Navbar usermenu ───────────────────────────────────────────────────────────
// frontpage.mustache embeds Boost's navbar ({{> theme_boost/navbar}}), which
// renders {{#usermenu}}{{> core/user_menu}}{{/usermenu}} itself. It therefore
// needs the RAW DATA ARRAY from primary navigation — NOT a pre-rendered HTML
// string. Passing a string makes the partial's {{#items}}/{{#submenus}} resolve
// against a string context, producing an empty dropdown (14px blank sliver).
$usermenu_data = $primarymenu['user'] ?? false;

// Breadcrumb HTML for the navbar.
$navbar_html = $OUTPUT->navbar();

// ── Frontpage custom context ──────────────────────────────────────────────────
$fpcontext = theme_edzcorp_get_frontpage_context(theme_config::load('edzcorp'));

// ── Template context ──────────────────────────────────────────────────────────
$templatecontext = array_merge($sidebarcontext, $fpcontext, [
    'sitename'    => format_string($SITE->shortname, true,
                         ['context' => context_course::instance(SITEID), 'escape' => false]),
    'output'      => $OUTPUT,
    'bodyattributes' => $bodyattributes,

    // Navbar — breadcrumb is pre-rendered HTML; usermenu is raw data for Boost navbar.
    'navbar'    => $navbar_html,
    'usermenu'  => $usermenu_data,
    'searchurl' => false,
    'notifications' => false,

    // Drawers.
    'sidepreblocks'        => $blockshtml,
    'hasblocks'            => $hasblocks,
    'blockdraweropen'      => $blockdraweropen,
    'courseindex'          => '',
    'courseindexopen'      => false,
    'forceblockdraweropen' => $forceblockdraweropen,
    'addblockbutton'       => $addblockbutton,

    // Navigation (for Boost drawers and mobile nav).
    'primarymoremenu'   => $primarymenu['moremenu'] ?? false,
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav'  => $primarymenu['mobileprimarynav'] ?? false,
    'langmenu'          => $primarymenu['lang'] ?? false,

    // Header.
    'headercontent'             => $headercontent,
    'overflow'                  => $overflow,
    'regionmainsettingsmenu'    => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
]);

$templatecontext = array_merge(
    $templatecontext,
    theme_edzcorp_get_footer_context(theme_config::load('edzcorp'))
);

echo $OUTPUT->render_from_template('theme_edzcorp/frontpage', $templatecontext);
