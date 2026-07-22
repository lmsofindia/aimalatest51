<?php
// This file is part of the local_edzfaculty plugin for Moodle.
//
// Faculty / Teacher Dashboard for the AIMA academic Moodle instance.

/**
 * Plugin version and metadata.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_edzfaculty';
$plugin->version   = 2026071804;      // YYYYMMDDXX.
$plugin->requires  = 2024100700;      // Moodle 4.5+ baseline (installs on 5.0+).
$plugin->maturity  = MATURITY_ALPHA;  // Phase 3: admin All-Faculty overview + picker.
$plugin->release   = '0.3.0';
