<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\task;

/**
 * Scheduled task: discover new recordings for finished occurrences that did
 * not arrive via a webhook, and enqueue them into the offload pipeline.
 *
 * P1: scaffold only (logs + no-op). Wiring lands in P4.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discover_recordings extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_discover', 'mod_edzsession');
    }

    public function execute(): void {
        global $DB;
        $found = \mod_edzsession\local\pipeline\discovery::run();
        mtrace("mod_edzsession: discover_recordings — enqueued {$found} recording(s).");
    }
}
