<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp theme configuration.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Theme name and parent.
$THEME->name    = 'edzcorp';
$THEME->parents = ['boost'];
// DEV added the renderer factory 
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
// SCSS compilation callbacks.
$THEME->scss          = function ($theme) {
    return theme_edzcorp_get_main_scss_content($theme);
};
$THEME->prescsscallback = 'theme_edzcorp_get_pre_scss';

// No legacy CSS sheets — we use SCSS entirely.
$THEME->sheets       = [];
$THEME->editor_sheets = [];

// Use the Boost usefallback to inherit missing templates.
$THEME->usefallback = true;

// Icon system — Font Awesome (inherited from Boost).
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;

// Enable the in-page edit-mode toggle switch (Moodle 4+).
$THEME->haseditswitch = true;

// Use the built-in Moodle course index (shown in side-pre on course pages).
$THEME->usescourseindex = true;

// Where the "Add a block" button appears.
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;

// Disable legacy dock (not needed with sidebar design).
$THEME->enable_dock = false;

// -------------------------------------------------------------------------
// Layout definitions
// Map every Moodle layout type → our PHP layout file + block regions.
// -------------------------------------------------------------------------
$THEME->layouts = [

    // Base — backwards-compatible, no blocks, no sidebar chrome needed.
    'base' => [
        'file'    => 'columns1.php',
        'regions' => [],
    ],

    // Standard — sidebar + right block column.
    'standard' => [
        'file'          => 'columns2.php',
        'regions'       => ['side-pre'],
        'defaultregion' => 'side-pre', //DEV  default side-pre
    ],

    // Course main page — side-pre (course index TOC) + side-post (blocks).
    'course' => [
        'file'          => 'course.php',
        'regions'       => ['side-pre', 'side-post'], // DEV it should be side-pre
        'defaultregion' => 'side-pre', //DEV  default side-pre
    ],

    // Course category browsing — same as standard.
    'coursecategory' => [
        'file'          => 'columns2.php',
        'regions'       => ['side-post'],
        'defaultregion' => 'side-post',
    ],

    // In-course activity — pre-drawer (TOC) + blocks.
    'incourse' => [
        'file'          => 'incourse.php',
        'regions'       => ['side-pre', 'side-post'],
        'defaultregion' => 'side-pre', //DEV default side-pre
    ],

    // Front / site home.
    'frontpage' => [  // Custom enterprise homepage
        'file'          => 'frontpage.php',
        'regions'       => ['side-post'],
        'defaultregion' => 'side-post',
    ],

    // Admin pages.
    'admin' => [
        'file'          => 'columns2.php',
        'regions'       => ['side-post'],
        'defaultregion' => 'side-post',
    ],

    // My Courses page.
    'mycourses' => [
        'file'          => 'columns2.php',
        'regions'       => ['side-post'],
        'defaultregion' => 'side-post',
        'options'       => ['nocontextheader' => true],
    ],

    // My Dashboard.
    'mydashboard' => [
        'file'          => 'columns2.php',
        'regions'       => ['side-pre'], // DEV it should be side-pre
        'defaultregion' => 'side-pre', //DEV default side-pre
        'options'       => ['nocontextheader' => true],
    ],

    // My Public profile.
    'mypublic' => [
        'file'          => 'columns2.php',
        'regions'       => ['side-post'],
        'defaultregion' => 'side-post',
    ],

    // Login page — full-screen, no sidebar.
    'login' => [
        'file'    => 'login.php',
        'regions' => [],
        'options' => ['langmenu' => true, 'nologin' => true],
    ],

    // Popup — stripped down, no navigation, no footer.
    'popup' => [
        'file'    => 'popup.php',
        'regions' => [],
        'options' => ['nofooter' => true, 'nonavbar' => true, 'nologin' => true],
    ],

    // Legacy frame top.
    'frametop' => [
        'file'    => 'columns1.php',
        'regions' => [],
        'options' => ['nofooter' => true, 'nocoursefooter' => true],
    ],

    // Embedded (iframe / object).
    'embedded' => [
        'file'    => 'embedded.php',
        'regions' => [],
    ],

    // Maintenance / install / upgrade.
    'maintenance' => [
        'file'    => 'maintenance.php',
        'regions' => [],
    ],

    // Print layout.
    'print' => [
        'file'    => 'columns1.php',
        'regions' => [],
        'options' => ['nofooter' => true, 'nonavbar' => false],
    ],

    // Redirect (thin passthrough).
    'redirect' => [
        'file'    => 'embedded.php',
        'regions' => [],
    ],

    // Reports.
    'report' => [
        'file'          => 'columns2.php',
        'regions'       => ['side-post'],
        'defaultregion' => 'side-post',
    ],

    // Secure exam / safe-browser window.
    'secure' => [
        'file'    => 'secure.php',
        'regions' => ['side-post'],
        'defaultregion' => 'side-post',
    ],
];
