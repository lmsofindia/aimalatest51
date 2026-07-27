<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Hook callbacks — PSR-14 Hooks API (Moodle 4.4 / 5.x).
 *
 * This file is used by Moodle 4.4+ to register hook callbacks.
 * For Moodle 4.1–4.3, the equivalent lib.php callbacks are used instead
 * (local_edzaiaxisfront_before_footer in lib.php).
 *
 * When both this file AND lib.php callbacks exist, Moodle 4.4+ automatically
 * ignores the lib.php version and uses the hook class defined here.
 * This allows a single codebase to support Moodle 4.1 through 5.x.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [

    /**
     * Inject the floating chatbot HTML into the page footer.
     *
     * Replaces: local_edzaiaxisfront_before_footer() in lib.php
     * Hook:     core\hook\output\before_footer_html_generation
     * Priority: 500 (mid-range — runs after core but before most other plugins)
     */
    [
        'hook'        => \core\hook\output\before_footer_html_generation::class,
        'callback'    => [\local_edzaiaxisfront\hook\output_callbacks::class, 'before_footer'],
        'priority'    => 500,
    ],

];
