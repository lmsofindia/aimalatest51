<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp — secure layout.
 * Used for SafeBrowser / securewindow quiz attempts.
 * No navigation out of the page; just content + blocks.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes(['theme-edzcorp', 'layout-secure']);

$blockshtml = $OUTPUT->blocks('side-post');
$hasblocks  = strpos($blockshtml, 'data-block=') !== false;

$templatecontext = [
    'output'         => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'pagetitle'      => $PAGE->title,
    'maincontent'    => $OUTPUT->main_content(),
    'blockshtml'     => $blockshtml,
    'hasblocks'      => $hasblocks,
    'sitename'       => format_string($SITE->shortname, true, ['context' => context_system::instance()]),
];

echo $OUTPUT->render_from_template('theme_edzcorp/secure', $templatecontext);
