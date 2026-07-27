<?php

/**
 * Scheduled task: refresh cached outputs for all ready CMs.
 *
 * Runs every 5 minutes (configured in db/tasks.php).
 * Intended to catch any AI regenerations that happened outside Moodle
 * (e.g. via axis-ai admin UI), and to handle incremental generation tasks.
 *
 * Only syncs CMs whose cm_config.status = 'ready' AND whose last sync was
 * more than SYNC_MIN_AGE_SECONDS ago, to avoid hammering axis-ai unnecessarily.
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

class sync_outputs extends \core\task\scheduled_task
{

    /** Minimum age (seconds) before re-syncing an already-ready CM. Default: 10 minutes. */
    const SYNC_MIN_AGE_SECONDS = 600;

    /** Maximum CMs to sync per run to limit execution time. */
    const MAX_CMS_PER_RUN = 50;

    public function get_name(): string
    {
        return get_string('task_sync_outputs', 'local_edzaiaxisfront');
    }

    public function execute(): void
    {
        global $DB;

        if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
            return;
        }

        try {
            $client = new axis_client();
        } catch (axis_client_exception $e) {
            mtrace('Axis AI sync_outputs: not configured — ' . $e->getMessage());
            return;
        }

        $manager = new output_manager($client);

        $cutoff = time() - self::SYNC_MIN_AGE_SECONDS;

        // Find ready CMs that haven't been synced recently
        // $configs = $DB->get_records_select(
        //     'local_edzaiaxisfront_cm_config',
        //     "status = 'ready' AND axis_content_item_id IS NOT NULL AND timemodified < ?",
        //     [$cutoff],
        //     'timemodified ASC',
        //     'id, cmid, axis_content_item_id',
        //     0,
        //     self::MAX_CMS_PER_RUN
        // );

        $configs = $DB->get_records_select(
            'local_edzaiaxisfront_cm_config',
            "status = 'ready' AND axis_content_item_id IS NOT NULL",
            [],
            'id ASC',
            'id, cmid, axis_content_item_id',
            0,
            self::MAX_CMS_PER_RUN
        );

        if (empty($configs)) {
            mtrace('Axis AI sync_outputs: nothing to refresh.');
            return;
        }

        mtrace('Axis AI sync_outputs: refreshing ' . count($configs) . ' CM(s).');

        foreach ($configs as $config) {
            mtrace("  Syncing cmid={$config->cmid}...");
            try {
                $ok = $manager->sync_cm_outputs($config->cmid, $config->axis_content_item_id);
                mtrace('  Result: ' . ($ok ? 'updated' : 'no change'));
            } catch (\Exception $e) {
                mtrace('  Error: ' . $e->getMessage());
            }
        }
    }
}
