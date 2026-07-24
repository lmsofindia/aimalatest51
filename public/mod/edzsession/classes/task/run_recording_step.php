<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\task;

use mod_edzsession\local\pipeline\pipeline_manager;
use mod_edzsession\local\pipeline\states;

/**
 * Adhoc task: advance a single recording through the offload pipeline as far as
 * it can go right now. Stops when it stalls (e.g. provider still transcoding) or
 * reaches a terminal state; the scheduled sweep re-visits stalled rows.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class run_recording_step extends \core\task\adhoc_task {

    public function get_component(): string {
        return 'mod_edzsession';
    }

    public function execute(): void {
        global $DB;
        $data = (array) $this->get_custom_data();
        $recordingid = (int) ($data['recordingid'] ?? 0);
        if (!$recordingid) {
            return;
        }

        // Advance up to a few steps this run; break on stall or terminal.
        for ($i = 0; $i < 8; $i++) {
            $rec = $DB->get_record('edzsession_recording', ['id' => $recordingid]);
            if (!$rec || states::is_terminal($rec->state)) {
                break;
            }
            if (!pipeline_manager::advance($rec)) {
                break; // No change => waiting on the provider; try again later.
            }
        }
    }
}
