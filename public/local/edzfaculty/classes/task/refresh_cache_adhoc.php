<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc wrapper so a teacher can rebuild their own courses' analytics on demand.
 * Reuses the scheduled task's logic, scoped to the given course ids.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh_cache_adhoc extends \core\task\adhoc_task {

    /**
     * Run the scoped rebuild.
     */
    public function execute() {
        $data = $this->get_custom_data();
        $courseids = (isset($data->courseids) && is_array($data->courseids))
            ? array_map('intval', $data->courseids)
            : null;

        $task = new refresh_cache();
        if ($courseids !== null) {
            $task->set_courses($courseids);
        }
        $task->execute();
    }
}
