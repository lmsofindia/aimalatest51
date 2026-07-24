<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\task;

/**
 * Scheduled task: advance every non-terminal recording one step through the
 * offload state machine. Idempotent; safe to run frequently.
 *
 * P1: scaffold only. The state machine driver lands in P4.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_pipeline extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_pipeline', 'mod_edzsession');
    }

    public function execute(): void {
        $advanced = \mod_edzsession\local\pipeline\pipeline_manager::advance_all();
        mtrace("mod_edzsession: process_pipeline — advanced {$advanced} recording(s).");
    }
}
