<?php
/**
 * Scheduled task: poll axis-ai for pending job status updates.
 *
 * Runs every 2 minutes (configured in db/tasks.php).
 * Finds all jobs in local_edzaiaxisfront_jobs where status IN ('queued','processing').
 * For each job, calls GET /api/v1/jobs/{axis_job_id} and updates local record.
 * When a job completes, triggers output_manager to sync outputs into Moodle tables.
 * When a job fails, marks the CM config as 'failed' and records the error.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront\task;

use local_edzaiaxisfront\api\axis_client;
use local_edzaiaxisfront\api\axis_client_exception;
use local_edzaiaxisfront\output_manager;

defined('MOODLE_INTERNAL') || die();

class poll_jobs extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('task_poll_jobs', 'local_edzaiaxisfront');
    }

    public function execute(): void {
        global $DB;

        if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
            return;
        }

        try {
            $client = new axis_client();
        } catch (axis_client_exception $e) {
            mtrace('Axis AI poll_jobs: not configured — ' . $e->getMessage());
            return;
        }

        $manager = new output_manager($client);

        // Reap orphaned jobs: anything stuck queued/processing well past any realistic
        // pipeline runtime is almost certainly a dead worker / lost Celery task. Mark it
        // failed so it stops masking the CM status (get_job_status keeps returning a
        // non-terminal job otherwise) and the teacher can re-run it.
        $stale = $DB->get_records_select(
            'local_edzaiaxisfront_jobs',
            "status IN ('queued', 'processing') AND timecreated < :cutoff",
            ['cutoff' => time() - 30 * MINSECS],
            '',
            'id, cmid'
        );
        foreach ($stale as $sj) {
            $DB->update_record('local_edzaiaxisfront_jobs', (object)[
                'id'            => $sj->id,
                'status'        => 'failed',
                'error_message' => 'Timed out — no completion from axis-ai (orphaned job reaped by poll_jobs).',
                'timemodified'  => time(),
                'timefinished'  => time(),
            ]);
            // Only flip the CM to failed if a later successful job hasn't already marked it ready.
            $cfg = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $sj->cmid], 'id, status');
            if ($cfg && $cfg->status !== 'ready') {
                $DB->set_field('local_edzaiaxisfront_cm_config', 'status', 'failed', ['id' => $cfg->id]);
            }
            mtrace("  Reaped orphaned job id={$sj->id} cmid={$sj->cmid}.");
        }

        // Find all active jobs
        $pending = $DB->get_records_select(
            'local_edzaiaxisfront_jobs',
            "status IN ('queued', 'processing')",
            [],
            'timecreated ASC',
            'id, cmid, axis_job_id, tasks_json, status'
        );

        if (empty($pending)) {
            mtrace('Axis AI poll_jobs: no pending jobs.');
            return;
        }

        mtrace('Axis AI poll_jobs: checking ' . count($pending) . ' job(s).');

        foreach ($pending as $job) {
            $this->check_job($job, $client, $manager, $DB);
        }
    }

    private function check_job(object $job, axis_client $client, output_manager $manager, \moodle_database $DB): void {
        try {
            $result = $client->get_job_status($job->axis_job_id);
        } catch (axis_client_exception $e) {
            mtrace("  Job {$job->axis_job_id}: API error — " . $e->getMessage());
            return;
        }

        $remote_status = $result['status'] ?? 'unknown';
        $progress      = (int) ($result['progress'] ?? 0);
        $error_msg     = $result['error'] ?? null;
        $now           = time();

        // Map axis-ai status to local status
        $local_status = $this->map_status($remote_status);

        $update = (object)[
            'id'           => $job->id,
            'status'       => $local_status,
            'progress'     => $progress,
            'timemodified' => $now,
        ];

        if ($local_status === 'failed') {
            $update->error_message = $error_msg ?? 'Unknown error';
        }

        if (in_array($local_status, ['completed', 'failed'])) {
            $update->timefinished = $now;
        }

        $DB->update_record('local_edzaiaxisfront_jobs', $update);

        mtrace("  Job {$job->axis_job_id}: {$remote_status} ({$progress}%)");

        if ($local_status === 'completed') {
            // Get content_item_id from cm_config
            $cm_config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $job->cmid], 'id,axis_content_item_id');
            if ($cm_config && !empty($cm_config->axis_content_item_id)) {
                mtrace("  Syncing outputs for cmid={$job->cmid}...");
                // A sync failure must never abort the whole poll run or strand the CM
                // in 'processing'. Catch, log, and mark the CM failed so it's re-runnable.
                try {
                    $success = $manager->sync_cm_outputs($job->cmid, $cm_config->axis_content_item_id);
                    mtrace('  Output sync: ' . ($success ? 'OK' : 'no outputs found'));
                } catch (\Throwable $se) {
                    mtrace('  Output sync FAILED for cmid=' . $job->cmid . ': ' . $se->getMessage());
                    $DB->set_field('local_edzaiaxisfront_cm_config', 'status', 'failed', ['cmid' => $job->cmid]);
                }
            } else {
                mtrace("  No content_item_id for cmid={$job->cmid}, skipping output sync.");
            }
        }

        if ($local_status === 'failed') {
            $DB->set_field('local_edzaiaxisfront_cm_config', 'status', 'failed', ['cmid' => $job->cmid]);
            mtrace("  cmid={$job->cmid} marked as failed: {$error_msg}");
        }
    }

    private function map_status(string $remote): string {
        return match ($remote) {
            'pending', 'queued' => 'queued',
            'running', 'processing', 'started' => 'processing',
            'completed', 'success', 'done' => 'completed',
            'failed', 'error' => 'failed',
            default => 'processing',  // unknown — keep polling
        };
    }
}
