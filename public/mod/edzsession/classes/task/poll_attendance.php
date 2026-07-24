<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\task;

/**
 * Scheduled task: pull participant reports for finished occurrences, reconcile
 * to Moodle users, and recompute custom completion.
 *
 * P1: scaffold only. The attendance engine lands in P3.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class poll_attendance extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_attendance', 'mod_edzsession');
    }

    public function execute(): void {
        $count = \mod_edzsession\local\attendance\attendance_engine::poll_finished_occurrences();
        mtrace("mod_edzsession: poll_attendance — processed {$count} occurrence(s).");
    }
}
