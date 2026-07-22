<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\task;

use quizaccess_edproctoring\helper\image_store;

/**
 * Scheduled task: delete snapshots older than the configured retention period.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_images extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_cleanup', 'quizaccess_edproctoring',
            null, true);
    }

    public function execute(): void {
        $retentionDays = (int) get_config('quizaccess_edproctoring', 'retention_days');

        if ($retentionDays <= 0) {
            mtrace('EDP cleanup: retention_days is 0 — auto-delete disabled. Skipping.');
            return;
        }

        $before = time() - ($retentionDays * DAYSECS);
        mtrace("EDP cleanup: deleting snapshots older than $retentionDays days (before " . date('Y-m-d', $before) . ')');

        $deleted = image_store::delete_snapshots_older_than($before);

        mtrace("EDP cleanup: deleted $deleted snapshot(s).");
    }
}
