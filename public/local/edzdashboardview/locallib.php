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
 * TODO describe file locallib
 *
 * @package    local_edzdashboardview
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function log_system_usage() {
    global $DB;

    $timestamp = time();

    // --- CPU usage ---
    // Load average (1 min) normalized to 100% for 1 core.
    $cpu = sys_getloadavg()[0];
    $cpu_count = (int) shell_exec('nproc');
    $cpu_percent = round(($cpu / $cpu_count) * 100, 2);

    // --- Memory usage ---
    $meminfo = file_get_contents("/proc/meminfo");
    preg_match("/MemTotal:\s+(\d+)/", $meminfo, $m1);
    preg_match("/MemAvailable:\s+(\d+)/", $meminfo, $m2);
    $mem_total = $m1[1];
    $mem_avail = $m2[1];
    $memory_percent = round(100 - ($mem_avail / $mem_total * 100), 2);

    // --- Storage usage ---
    $df = disk_free_space("/");
    $dt = disk_total_space("/");
    $storage_percent = round(100 - ($df / $dt * 100), 2);

    // Insert into DB
    $record = (object)[
        'timestamp'     => $timestamp,
        'cpu_usage'     => $cpu_percent,
        'memory_usage'  => $memory_percent,
        'storage_usage' => $storage_percent
    ];
    $DB->insert_record('usage_log', $record);
}



