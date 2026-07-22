<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp — embedded layout.
 * Used for iframes and redirect pages; absolutely minimal.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes(['theme-edzcorp', 'layout-embedded']);

$templatecontext = [
    'output'         => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'pagetitle'      => $PAGE->title,
    'maincontent'    => $OUTPUT->main_content(),
];

echo $OUTPUT->render_from_template('theme_edzcorp/embedded', $templatecontext);
