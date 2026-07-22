<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details for local_trackmytime.
 *
 * Theme-independent time-on-task tracker + performance page. Standalone fork
 * of local_gemui_timetracker: the heartbeat is self-injected (no theme needed).
 *
 * @package    local_trackmytime
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_trackmytime';
$plugin->version   = 2026070900;          // 1.2.0 — reporting API + module_time_totals bulk (\local_trackmytime\reporting) for cross-plugin time analytics.
$plugin->requires  = 2024100700;          // Moodle 4.5+.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.2.0';
// NOTE: intentionally NO $plugin->dependencies — runs on any theme.
