<?php

/**
 * External web service functions — AJAX backbone for the plugin.
 *
 * Functions:
 *  Teacher / Dashboard:
 *   - get_course_cms            : list all CMs in a course with AI status
 *   - get_cm_config             : current config for one CM (for wizard pre-population)
 *   - save_wizard_config        : step 3 — save tasks + generation parameters
 *   - submit_ingest             : step 4 confirm — trigger axis-ai ingest + return job_id
 *   - get_job_status            : step 5 — live polling while wizard monitors progress
 *   - toggle_output_visibility  : teacher shows/hides an output type from students
 *   - save_summary_edit         : teacher edits the AI summary text
 *   - trigger_regenerate        : teacher re-runs specific output tasks
 *
 *  Student panel:
 *   - get_cm_outputs            : returns all visible outputs for student view
 *   - create_chat_session       : start a chat session (study or support mode)
 *   - send_chat_message         : send a chat message, returns AI reply
 *   - end_chat_session          : close the session
 *
 *  Admin (KB):
 *   - ingest_kb_url             : ingest a URL into the knowledge base
 *   - list_kb_items             : list all KB items for admin table
 *   - toggle_kb_item            : enable/disable a KB item
 *   - delete_kb_item            : remove a KB item from axis-ai + local
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront\external;

use local_edzaiaxisfront\api\axis_client;
use local_edzaiaxisfront\api\axis_client_exception;
use local_edzaiaxisfront\content_router;
use local_edzaiaxisfront\output_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class api extends \external_api
{

    // =========================================================================
    //  get_course_cms
    // =========================================================================

    public static function get_course_cms_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function get_course_cms(int $courseid): array
    {
        global $DB, $USER;

        ['courseid' => $courseid] = self::validate_parameters(self::get_course_cms_parameters(), compact('courseid'));

        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecourse', $context);

        $modinfo  = get_fast_modinfo($courseid);
        $cms      = [];
        $configs  = $DB->get_records('local_edzaiaxisfront_cm_config', ['courseid' => $courseid], '', 'cmid,status,generated_features,enabled_features');
        $cfg_map  = [];
        foreach ($configs as $c) {
            $cfg_map[$c->cmid] = $c;
        }

        foreach ($modinfo->cms as $cm) {
            if (!$cm->uservisible) continue;
            if (!content_router::is_supported($cm)) continue;

            $config = $cfg_map[$cm->id] ?? null;
            $cms[]  = [
                'cmid'               => (int) $cm->id,
                'name'               => $cm->name,
                'modname'            => $cm->modname,
                'section'            => (int) $cm->sectionnum,
                'content_type'       => content_router::detect_content_type($cm),
                'status'             => $config ? $config->status : 'not_ingested',
                'generated_features' => $config ? ($config->generated_features ?? '[]') : '[]',
                'enabled_features'   => $config ? ($config->enabled_features  ?? '[]') : '[]',
            ];
        }

        return $cms;
    }

    public static function get_course_cms_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'cmid'               => new \external_value(PARAM_INT),
                'name'               => new \external_value(PARAM_TEXT),
                'modname'            => new \external_value(PARAM_RAW),
                'section'            => new \external_value(PARAM_INT),
                'content_type'       => new \external_value(PARAM_RAW),
                'status'             => new \external_value(PARAM_RAW),
                'generated_features' => new \external_value(PARAM_RAW),
                'enabled_features'   => new \external_value(PARAM_RAW),
            ])
        );
    }

    // =========================================================================
    //  get_cm_config
    // =========================================================================

    public static function get_cm_config_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    public static function get_cm_config(int $cmid): array
    {
        global $DB;

        ['cmid' => $cmid] = self::validate_parameters(self::get_cm_config_parameters(), compact('cmid'));

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecourse', $context);

        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid]);

        return [
            'cmid'              => $cmid,
            'status'            => $config ? $config->status : 'not_ingested',
            'enabled_features'  => $config ? ($config->enabled_features  ?? '[]') : '[]',
            'visible_features'  => $config ? ($config->visible_features  ?? '[]') : '[]',
            'generated_features' => $config ? ($config->generated_features ?? '[]') : '[]',
            'generation_config' => $config ? ($config->generation_config ?? '{}') : '{}',
        ];
    }

    public static function get_cm_config_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'cmid'               => new \external_value(PARAM_INT),
            'status'             => new \external_value(PARAM_RAW),
            'enabled_features'   => new \external_value(PARAM_RAW),
            'visible_features'   => new \external_value(PARAM_RAW),
            'generated_features' => new \external_value(PARAM_RAW),
            'generation_config'  => new \external_value(PARAM_RAW),
        ]);
    }

    // =========================================================================
    //  save_wizard_config
    // =========================================================================

    public static function save_wizard_config_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'              => new \external_value(PARAM_INT),
            'enabled_features'  => new \external_value(PARAM_RAW, 'JSON array of feature keys'),
            'generation_config' => new \external_value(PARAM_RAW, 'JSON object with generation params'),
        ]);
    }

    public static function save_wizard_config(int $cmid, string $enabled_features, string $generation_config): bool
    {
        global $DB;

        ['cmid' => $cmid, 'enabled_features' => $enabled_features, 'generation_config' => $generation_config]
            = self::validate_parameters(self::save_wizard_config_parameters(), compact('cmid', 'enabled_features', 'generation_config'));

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecourse', $context);

        // Validate JSON
        json_decode($enabled_features);
        if (json_last_error() !== JSON_ERROR_NONE) throw new \invalid_parameter_exception('enabled_features must be valid JSON');
        json_decode($generation_config);
        if (json_last_error() !== JSON_ERROR_NONE) throw new \invalid_parameter_exception('generation_config must be valid JSON');

        // Clamp enabled_features against site-level flags
        $site_features = self::get_site_features();
        $requested = json_decode($enabled_features, true);
        $allowed   = array_intersect($requested, $site_features);
        $enabled_features = json_encode(array_values($allowed));

        $now       = time();
        $cm        = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $existing  = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid]);

        if ($existing) {
            $DB->update_record('local_edzaiaxisfront_cm_config', (object)[
                'id'                => $existing->id,
                'enabled_features'  => $enabled_features,
                'generation_config' => $generation_config,
                'timemodified'      => $now,
            ]);
        } else {
            $DB->insert_record('local_edzaiaxisfront_cm_config', (object)[
                'cmid'              => $cmid,
                'courseid'          => (int) $cm->course,
                'status'            => 'not_ingested',
                'enabled_features'  => $enabled_features,
                'visible_features'  => $enabled_features,  // default all visible
                'generated_features' => '[]',
                'generation_config' => $generation_config,
                'timecreated'       => $now,
                'timemodified'      => $now,
            ]);
        }

        return true;
    }

    public static function save_wizard_config_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  submit_ingest
    // =========================================================================

    public static function submit_ingest_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmids' => new \external_value(PARAM_RAW, 'JSON array of cmids to ingest'),
        ]);
    }

    /**
     * Trigger axis-ai ingest for one or more CMs.
     * Returns array of {cmid, job_id, error?} results.
     */
    public static function submit_ingest(string $cmids_json): array
    {
        global $DB, $USER;

        ['cmids' => $cmids_json] = self::validate_parameters(self::submit_ingest_parameters(), ['cmids' => $cmids_json]);

        $cmids = json_decode($cmids_json, true);
        if (!is_array($cmids) || empty($cmids)) throw new \invalid_parameter_exception('cmids must be a non-empty JSON array');

        $results = [];

        try {
            $client  = new axis_client();
            $manager = new output_manager($client);
        } catch (axis_client_exception $e) {
            throw new \moodle_exception('error_api_connection', 'local_edzaiaxisfront', '', ($e instanceof axis_client_exception ? $e->detail : $e->getMessage()), $e->getMessage());
        }

        $modinfo_cache = [];

        foreach ($cmids as $cmid) {
            $cmid = (int) $cmid;
            $result = ['cmid' => $cmid, 'job_id' => '', 'axis_content_item_id' => '', 'error' => ''];
            try {
                $context = \context_module::instance($cmid);
                self::validate_context($context);
                require_capability('local/edzaiaxisfront:managecourse', $context);

                $cm_record = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
                $courseid  = (int) $cm_record->course;

                if (!isset($modinfo_cache[$courseid])) {
                    $modinfo_cache[$courseid] = get_fast_modinfo($courseid);
                }
                $cm = $modinfo_cache[$courseid]->get_cm($cmid);

                $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid]);
                if (!$config) {
                    throw new \moodle_exception('No config found for cmid — save wizard config first.');
                }

                $gen_config = json_decode($config->generation_config ?? '{}', true) ?: [];
                $tasks      = json_decode($config->enabled_features  ?? '[]', true) ?: ['summary'];

                // Map feature keys to axis-ai task keys
                // Feature keys already match axis-ai task names. IMPORTANT: the backend
                // task key is 'quiz' (pipeline _task_to_output_type) — NOT 'quiz_questions';
                // sending 'quiz_questions' logs unknown_task_type and silently skips the quiz.
                $task_map = ['faq' => 'faq'];
                $axis_tasks = array_map(fn($t) => $task_map[$t] ?? $t, $tasks);

                $options = array_merge($gen_config, ['tasks' => $axis_tasks]);

                $content_type = content_router::detect_content_type($cm);
                $now          = time();

                // print_r(['content_type' => $content_type, 'options' => $options]); // DEBUGGING
                // Ingest
                if ($content_type === 'scorm') {
                    // SCORM: PHP extracts, then structured ingest
                    $extractor = new \local_edzaiaxisfront\scorm_extractor($cm);
                    $chunks    = $extractor->extract();
                    $payload   = content_router::build_scorm_structured_payload($cm, $chunks, $USER->id, $options);
                    $response  = $client->ingest_structured($payload);
                } else if (content_router::needs_file_upload($cm)) {
                    // Protected mods (pdfprotect) don't serve files over webservice/pluginfile,
                    // so upload the raw bytes to axis-ai's /ingest/file endpoint instead.
                    $file = content_router::get_pdfprotect_file($cm);
                    if (!$file) {
                        throw new \moodle_exception('No file found for ' . $cm->modname . ' (cmid ' . $cmid . ')');
                    }
                    $response = $client->ingest_file(
                        $file->get_filename(),
                        $file->get_content(),
                        $file->get_mimetype(),
                        [
                            'content_type'     => $content_type,
                            'moodle_course_id' => (int) $courseid,
                            'moodle_cmid'      => (int) $cmid,
                            'moodle_user_id'   => (int) $USER->id,
                            'title'            => $cm->name,
                            'tasks'            => implode(',', $axis_tasks),
                            'language'         => (string) ($gen_config['language'] ?? ''),
                        ]
                    );
                } else {
                    $payload  = content_router::build_ingest_payload($cm, $USER->id, $options);

                    // 🔥 FIX 2: Ensure metadata is always an object as per FAST API 
                    if (!isset($payload['metadata']) || !is_array($payload['metadata'])) {
                        $payload['metadata'] = [];
                    }

                    // ❗ CRITICAL: force it to be object, not array
                    if (empty($payload['metadata'])) {
                        $payload['metadata'] = new \stdClass();
                    }
                    if (!$payload) {
                        throw new \moodle_exception('Unsupported content type: ' . $content_type);
                    }
                    $response = $client->ingest_url($payload);
                }

                $axis_job_id         = $response['job_id'] ?? '';
                $axis_content_item_id = $response['content_item_id'] ?? '';

                // Record the axis-ai content item id + mark processing for now.
                $DB->update_record('local_edzaiaxisfront_cm_config', (object)[
                    'id'                    => $config->id,
                    'axis_content_item_id'  => $axis_content_item_id,
                    'status'                => 'processing',
                    'timemodified'          => $now,
                ]);

                if ($axis_job_id) {
                    // A job is queued/running — insert a row for the poller to track.
                    $DB->insert_record('local_edzaiaxisfront_jobs', (object)[
                        'cmid'         => $cmid,
                        'axis_job_id'  => $axis_job_id,
                        'status'       => 'queued',
                        'progress'     => 0,
                        'tasks_json'   => json_encode($axis_tasks),
                        'timecreated'  => $now,
                        'timemodified' => $now,
                    ]);
                } else if (!empty($axis_content_item_id)) {
                    // No job returned — axis-ai reports the content is ALREADY processed
                    // (status "ready"/unchanged). There is nothing to poll, so sync the
                    // outputs now and mark the CM ready; otherwise it would sit in
                    // "processing" forever with no poller to advance it.
                    try {
                        $manager->sync_cm_outputs($cmid, $axis_content_item_id);
                    } catch (\Exception $se) {
                        debugging('Axis AI immediate sync failed for cmid ' . $cmid . ': ' . $se->getMessage(), DEBUG_DEVELOPER);
                    }
                }

                $result['job_id']               = $axis_job_id;
                $result['axis_content_item_id'] = $axis_content_item_id;
            } catch (\Exception $e) {
                $result['error'] = $e->getMessage();
                // Mark CM as failed in config if it exists
                if ($config_row = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid])) {
                    $DB->set_field('local_edzaiaxisfront_cm_config', 'status', 'failed', ['id' => $config_row->id]);
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    public static function submit_ingest_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'cmid'                  => new \external_value(PARAM_INT),
                'job_id'                => new \external_value(PARAM_TEXT),
                'axis_content_item_id'  => new \external_value(PARAM_TEXT),
                'error'                 => new \external_value(PARAM_TEXT),
            ])
        );
    }

    // =========================================================================
    //  get_job_status
    // =========================================================================

    public static function get_job_status_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT),
        ]);
    }

    public static function get_job_status(int $cmid): array
    {
        global $DB;

        ['cmid' => $cmid] = self::validate_parameters(self::get_job_status_parameters(), compact('cmid'));

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecourse', $context);

        $job = $DB->get_record_select(
            'local_edzaiaxisfront_jobs',
            'cmid = ? AND status NOT IN (\'completed\', \'failed\')',
            [$cmid],
            'id, status, progress, error_message',
            IGNORE_MULTIPLE
        );

        if (!$job) {
            // Check cm_config for final status
            $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid], 'status, generated_features');
            return [
                'status'             => $config ? $config->status : 'not_ingested',
                'progress'           => $config && $config->status === 'ready' ? 100 : 0,
                'error'              => '',
                'generated_features' => $config ? ($config->generated_features ?? '[]') : '[]',
            ];
        }

        return [
            'status'             => $job->status,
            'progress'           => (int) $job->progress,
            'error'              => $job->error_message ?? '',
            'generated_features' => '[]',
        ];
    }

    public static function get_job_status_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'status'             => new \external_value(PARAM_RAW),
            'progress'           => new \external_value(PARAM_INT),
            'error'              => new \external_value(PARAM_TEXT),
            'generated_features' => new \external_value(PARAM_RAW),
        ]);
    }

    // =========================================================================
    //  toggle_output_visibility
    // =========================================================================

    public static function toggle_output_visibility_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'        => new \external_value(PARAM_INT),
            'output_type' => new \external_value(PARAM_RAW),
            'is_visible'  => new \external_value(PARAM_BOOL),
        ]);
    }

    public static function toggle_output_visibility(int $cmid, string $output_type, bool $is_visible): bool
    {
        global $DB;

        ['cmid' => $cmid, 'output_type' => $output_type, 'is_visible' => $is_visible]
            = self::validate_parameters(self::toggle_output_visibility_parameters(), compact('cmid', 'output_type', 'is_visible'));

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecourse', $context);

        // Update visible_features JSON in cm_config
        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid]);
        if (!$config) throw new \moodle_exception('CM not configured');

        $visible = json_decode($config->visible_features ?? '[]', true) ?: [];

        if ($is_visible && !in_array($output_type, $visible)) {
            $visible[] = $output_type;
        } elseif (!$is_visible) {
            $visible = array_values(array_filter($visible, fn($f) => $f !== $output_type));
        }

        $DB->update_record('local_edzaiaxisfront_cm_config', (object)[
            'id'               => $config->id,
            'visible_features' => json_encode($visible),
            'timemodified'     => time(),
        ]);

        // Also update the outputs table directly for single-type outputs
        if (in_array($output_type, ['summary', 'faq', 'infographic'])) {
            $DB->set_field(
                'local_edzaiaxisfront_outputs',
                'is_visible',
                (int) $is_visible,
                ['cmid' => $cmid, 'output_type' => $output_type]
            );
        }

        return true;
    }

    public static function toggle_output_visibility_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  save_summary_edit
    // =========================================================================

    public static function save_summary_edit_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'    => new \external_value(PARAM_INT),
            'content' => new \external_value(PARAM_RAW),
        ]);
    }

    public static function save_summary_edit(int $cmid, string $content): bool
    {
        global $DB, $USER;

        ['cmid' => $cmid, 'content' => $content]
            = self::validate_parameters(self::save_summary_edit_parameters(), compact('cmid', 'content'));

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecourse', $context);

        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid], 'axis_content_item_id');
        if (!$config || empty($config->axis_content_item_id)) {
            throw new \moodle_exception('No content item for this CM');
        }

        $manager = new output_manager();
        $manager->save_teacher_edit($cmid, $config->axis_content_item_id, $content, $USER->id);

        return true;
    }

    public static function save_summary_edit_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  trigger_regenerate
    // =========================================================================

    public static function trigger_regenerate_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'  => new \external_value(PARAM_INT),
            'tasks' => new \external_value(PARAM_RAW, 'JSON array of task keys to regenerate'),
        ]);
    }

    public static function trigger_regenerate(int $cmid, string $tasks_json): array
    {
        global $DB;

        // The external param is named 'tasks' (not 'tasks_json') — pass the right key,
        // else validate_parameters rejects every call with "Invalid parameter value".
        ['cmid' => $cmid, 'tasks' => $tasks_json]
            = self::validate_parameters(self::trigger_regenerate_parameters(), ['cmid' => $cmid, 'tasks' => $tasks_json]);

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecourse', $context);

        $tasks = json_decode($tasks_json, true);
        if (!is_array($tasks) || empty($tasks)) throw new \invalid_parameter_exception('tasks must be a non-empty JSON array');

        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid], 'axis_content_item_id, generation_config');
        if (!$config || empty($config->axis_content_item_id)) throw new \moodle_exception('No content item for this CM');

        $gen_config = json_decode($config->generation_config ?? '{}', true) ?: [];
        $options    = array_intersect_key($gen_config, array_flip(['chunk_size', 'chunk_overlap', 'language', 'output_language']));

        $client   = new axis_client();
        $response = $client->trigger_generation($config->axis_content_item_id, $tasks, $options);

        $job_id = $response['job_id'] ?? '';
        $now    = time();

        if ($job_id) {
            $DB->insert_record('local_edzaiaxisfront_jobs', (object)[
                'cmid'         => $cmid,
                'axis_job_id'  => $job_id,
                'status'       => 'queued',
                'progress'     => 0,
                'tasks_json'   => json_encode($tasks),
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }

        return ['job_id' => $job_id];
    }

    public static function trigger_regenerate_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'job_id' => new \external_value(PARAM_TEXT),
        ]);
    }

    // =========================================================================
    //  get_cm_outputs  (student-facing)
    // =========================================================================

    public static function get_cm_outputs_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT),
        ]);
    }

    public static function get_cm_outputs(int $cmid): array
    {
        global $DB;

        ['cmid' => $cmid] = self::validate_parameters(self::get_cm_outputs_parameters(), compact('cmid'));

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:viewstudent', $context);

        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid]);
        if (!$config || $config->status !== 'ready') {
            return self::empty_outputs();
        }

        $site_features     = self::get_site_features();
        $enabled_features  = json_decode($config->enabled_features  ?? '[]', true) ?: [];
        $visible_features  = json_decode($config->visible_features  ?? '[]', true) ?: [];
        $generated_features = json_decode($config->generated_features ?? '[]', true) ?: [];

        // Only show features that passed ALL three gates + site level
        $show = array_intersect($site_features, $enabled_features, $visible_features, $generated_features);

        $outputs = ['available_features' => array_values($show)];

        if (in_array('summary', $show)) {
            $row = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'summary', 'is_visible' => 1]);
            $outputs['summary'] = $row ? $row->content : '';
        }

        if (in_array('faq', $show)) {
            $row = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'faq', 'is_visible' => 1]);
            $outputs['faq'] = $row ? $row->content : '';
        }

        if (in_array('infographic', $show)) {
            $row = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'infographic', 'is_visible' => 1]);
            $outputs['infographic'] = $row ? $row->content : '';
        }

        if (in_array('flashcards', $show)) {
            $cards = $DB->get_records('local_edzaiaxisfront_flashcards', ['cmid' => $cmid, 'is_active' => 1], 'position ASC', 'id,front,back');
            $outputs['flashcards'] = json_encode(array_values($cards));
        }

        if (in_array('glossary', $show)) {
            $terms = $DB->get_records('local_edzaiaxisfront_glossary', ['cmid' => $cmid], 'term ASC', 'id,term,definition,context_note');
            $outputs['glossary'] = json_encode(array_values($terms));
        }

        if (in_array('quiz', $show)) {
            // Table column is correct_answer (NOT answer); remap to the 'answer' key the
            // student panel scores against. Selecting 'answer' throws dmlreadexception.
            $rows = $DB->get_records('local_edzaiaxisfront_quiz_questions', ['cmid' => $cmid, 'is_active' => 1], '', 'id,question_text,correct_answer,options_json,blooms_level,difficulty,question_type');
            $questions = [];
            foreach ($rows as $r) {
                $questions[] = [
                    'id'            => $r->id,
                    'question_text' => $r->question_text,
                    'answer'        => $r->correct_answer,
                    'options_json'  => $r->options_json,
                    'blooms_level'  => $r->blooms_level,
                    'difficulty'    => $r->difficulty,
                    'question_type' => $r->question_type,
                ];
            }
            $outputs['quiz'] = json_encode($questions);
        }

        if (in_array('transcript', $show)) {
            $row = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'transcript', 'is_visible' => 1]);
            $outputs['transcript'] = $row ? $row->content : '';
        }

        // Chatbot availability (no local content needed — just a flag)
        $outputs['chatbot_enabled'] = in_array('chatbot', $show) ? '1' : '0';
        $outputs['kb_chat_enabled']  = in_array('kb_chat', $show) ? '1' : '0';

        return $outputs;
    }

    public static function get_cm_outputs_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'available_features' => new \external_multiple_structure(new \external_value(PARAM_RAW)),
            'summary'            => new \external_value(PARAM_RAW, '', VALUE_OPTIONAL),
            'faq'                => new \external_value(PARAM_RAW, '', VALUE_OPTIONAL),
            'infographic'        => new \external_value(PARAM_RAW, '', VALUE_OPTIONAL),
            'flashcards'         => new \external_value(PARAM_RAW, '', VALUE_OPTIONAL),
            'glossary'           => new \external_value(PARAM_RAW, '', VALUE_OPTIONAL),
            'quiz'               => new \external_value(PARAM_RAW, '', VALUE_OPTIONAL),
            'transcript'         => new \external_value(PARAM_RAW, '', VALUE_OPTIONAL),
            'chatbot_enabled'    => new \external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
            'kb_chat_enabled'    => new \external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
        ]);
    }

    // =========================================================================
    //  get_teacher_cm_outputs  (teacher / admin view — all generated content)
    // =========================================================================

    public static function get_teacher_cm_outputs_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Returns ALL generated AI outputs for a CM regardless of visibility.
     * Intended for the teacher content-editing modal in the AI Manager dashboard.
     * Requires managecontent capability.
     */
    public static function get_teacher_cm_outputs(int $cmid): array
    {
        global $DB;

        ['cmid' => $cmid] = self::validate_parameters(
            self::get_teacher_cm_outputs_parameters(),
            compact('cmid')
        );

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecontent', $context);

        // Resolve CM name
        $cm     = $DB->get_record('course_modules', ['id' => $cmid], 'module, instance', MUST_EXIST);
        $module = $DB->get_field('modules', 'name', ['id' => $cm->module]);
        $name   = $DB->get_field($module, 'name', ['id' => $cm->instance]) ?: 'Activity ' . $cmid;

        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid]);
        if (!$config) {
            return ['cm_name' => $name, 'summary' => '', 'faq' => '', 'glossary' => '', 'flashcards' => '', 'quiz' => ''];
        }

        $generated = json_decode($config->generated_features ?? '[]', true) ?: [];

        $out = ['cm_name' => $name];

        if (in_array('summary', $generated)) {
            $row = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'summary']);
            $out['summary'] = $row ? $row->content : '';
        } else {
            $out['summary'] = '';
        }

        if (in_array('faq', $generated)) {
            $row = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'faq']);
            $out['faq'] = $row ? $row->content : '';
        } else {
            $out['faq'] = '';
        }

        if (in_array('glossary', $generated)) {
            $terms = $DB->get_records('local_edzaiaxisfront_glossary', ['cmid' => $cmid], 'term ASC', 'id,term,definition,context_note');
            $out['glossary'] = json_encode(array_values($terms));
        } else {
            $out['glossary'] = '';
        }

        if (in_array('flashcards', $generated)) {
            $cards = $DB->get_records('local_edzaiaxisfront_flashcards', ['cmid' => $cmid, 'is_active' => 1], 'position ASC', 'id,front,back');
            $out['flashcards'] = json_encode(array_values($cards));
        } else {
            $out['flashcards'] = '';
        }

        if (in_array('quiz', $generated)) {
            $questions = $DB->get_records(
                'local_edzaiaxisfront_quiz_questions',
                ['cmid' => $cmid, 'is_active' => 1],
                '',
                'id,question_text,correct_answer,options_json,explanation,blooms_level,difficulty,question_type'
            );
            // Normalise to a consistent shape for the JS editor. correct_index is derived
            // by locating correct_answer text within the (plain-string) options array.
            $qs = [];
            foreach ($questions as $q) {
                $options = json_decode($q->options_json ?? '[]', true) ?: [];
                $idx = array_search($q->correct_answer, $options, true);
                $qs[] = [
                    'question'      => $q->question_text,
                    'options'       => $options,
                    'correct_index' => $idx === false ? 0 : (int) $idx,
                    'explanation'   => $q->explanation ?? '',
                ];
            }
            $out['quiz'] = json_encode($qs);
        } else {
            $out['quiz'] = '';
        }

        // Infographic — generated HTML, shown read-only (preview) in the editor.
        if (in_array('infographic', $generated)) {
            $row = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'infographic']);
            $out['infographic'] = $row ? $row->content : '';
        } else {
            $out['infographic'] = '';
        }

        return $out;
    }

    public static function get_teacher_cm_outputs_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'cm_name'     => new \external_value(PARAM_TEXT),
            'summary'     => new \external_value(PARAM_RAW,  '', VALUE_OPTIONAL),
            'faq'         => new \external_value(PARAM_RAW,  '', VALUE_OPTIONAL),
            'glossary'    => new \external_value(PARAM_RAW,  '', VALUE_OPTIONAL),
            'flashcards'  => new \external_value(PARAM_RAW,  '', VALUE_OPTIONAL),
            'quiz'        => new \external_value(PARAM_RAW,  '', VALUE_OPTIONAL),
            'infographic' => new \external_value(PARAM_RAW,  '', VALUE_OPTIONAL),
        ]);
    }

    // =========================================================================
    //  create_chat_session
    // =========================================================================

    public static function create_chat_session_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'      => new \external_value(PARAM_INT),
            'chat_mode' => new \external_value(PARAM_RAW, 'study|support|learning', VALUE_DEFAULT, 'study'),
        ]);
    }

    public static function create_chat_session(int $cmid, string $chat_mode = 'study'): array
    {
        global $DB, $USER;

        ['cmid' => $cmid, 'chat_mode' => $chat_mode]
            = self::validate_parameters(self::create_chat_session_parameters(), compact('cmid', 'chat_mode'));

        $tenant_id = get_config('local_edzaiaxisfront', 'tenant_id');
        $content_item_id = null;
        $course_id = 0;

        // Support / KB-chat mode does not require a specific CM — it operates on the
        // global KB.  Use system context so cmid=0 is accepted.
        if (in_array($chat_mode, ['support', 'kb_chat', 'kb'])) {
            $context = \context_system::instance();
            self::validate_context($context);
            // Support mode only requires the user to be logged in (no course enrolment needed)
            if (!isloggedin() || isguestuser()) throw new \moodle_exception('noguest');
        } else {
            // Study / practice / learning mode: requires a valid CM with AI content
            if ($cmid <= 0) throw new \invalid_parameter_exception('cmid required for study/practice mode');

            $context = \context_module::instance($cmid);
            self::validate_context($context);
            require_capability('local/edzaiaxisfront:viewstudent', $context);

            $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid], 'axis_content_item_id, courseid');
            if (!$config) throw new \moodle_exception('AI not ready for this activity');

            $content_item_id = $config->axis_content_item_id ?: null;
            $course_id       = (int) $config->courseid;
        }

        $payload = [
            'moodle_user_id'     => $USER->id,
            'moodle_course_id'   => $course_id ?: null,
            'moodle_cmid'        => $cmid > 0 ? $cmid : null,
            'scoped_content_ids' => $content_item_id ? [$content_item_id] : null,
            'chat_mode'          => $chat_mode,
        ];
        // axisstand resolves the tenant from the API key. Only send tenant_id when
        // one is configured (legacy full axis-ai); an empty string would be
        // rejected as an invalid tenant by strict server-side validation.
        if (!empty($tenant_id)) {
            $payload['tenant_id'] = $tenant_id;
        }

        $client   = new axis_client();
        $response = $client->create_chat_session($payload);

        //print_r("Chat session creation response: " . json_encode($response)); // DEBUGGING
        //Mihir 
        // in response there is no field of session_id, but there is a field of session with the session id inside it. So we need to extract the session id from the session field.
        // so we will use the id field inside the session field as the session id.

        $now = time();
        $DB->insert_record('local_edzaiaxisfront_chat_sessions', (object)[
            'cmid'             => $cmid > 0 ? $cmid : 0,
            'userid'           => $USER->id,
            'axis_session_id'  => $response['id'],
            'chat_mode'        => $chat_mode,
            'tokens_used'      => 0,
            'timecreated'      => $now,
            'timemodified'     => $now,
        ]);

        return ['session_id' => $response['id']]; // changed from $response['session_id'] to $response['id']
    }

    public static function create_chat_session_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'session_id' => new \external_value(PARAM_TEXT),
        ]);
    }

    // =========================================================================
    //  send_chat_message
    // =========================================================================

    public static function send_chat_message_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_TEXT),
            'message'    => new \external_value(PARAM_RAW),
        ]);
    }

    public static function send_chat_message(string $session_id, string $message): array
    {
        global $DB, $USER;

        ['session_id' => $session_id, 'message' => $message]
            = self::validate_parameters(self::send_chat_message_parameters(), compact('session_id', 'message'));

        // Verify session belongs to this user
        $session = $DB->get_record('local_edzaiaxisfront_chat_sessions', ['axis_session_id' => $session_id, 'userid' => $USER->id]);
        if (!$session) throw new \moodle_exception('Invalid session');

        // Support/KB sessions have cmid=0 — use system context to avoid context_module::instance(0) crash
        if (!empty($session->cmid) && $session->cmid > 0) {
            $context = \context_module::instance($session->cmid);
            self::validate_context($context);
            require_capability('local/edzaiaxisfront:viewstudent', $context);
        } else {
            $context = \context_system::instance();
            self::validate_context($context);
            if (!isloggedin() || isguestuser()) throw new \moodle_exception('noguest');
        }

        $now      = time();
        $msg_tbl  = 'local_edzaiaxisfront_chat_messages';
        $sess_tbl = 'local_edzaiaxisfront_chat_sessions';
        $msgs_table_exists = $DB->get_manager()->table_exists($msg_tbl);

        // Store the user message
        if ($msgs_table_exists) {
            $DB->insert_record($msg_tbl, (object)[
                'sessionid'   => $session->id,
                'userid'      => $USER->id,
                'role'        => 'user',
                'message'     => $message,
                'tokens_used' => 0,
                'timecreated' => $now,
            ]);
        }

        $client   = new axis_client();
        $response = $client->send_chat_message([
            'session_id' => $session_id,
            'message'    => $message,
        ]);

        $reply  = $response['answer'] ?? $response['default_message'] ?? '';
        $tokens = (int) ($response['meta']['tokens_total'] ?? 0);

        // Store the AI reply
        if ($msgs_table_exists) {
            $DB->insert_record($msg_tbl, (object)[
                'sessionid'   => $session->id,
                'userid'      => $USER->id,
                'role'        => 'assistant',
                'message'     => $reply,
                'tokens_used' => $tokens,
                'timecreated' => $now + 1, // ensure ordering
            ]);
        }

        // Auto-set thread name from first user message (first 80 chars)
        if (!empty($message) && empty($session->thread_name)
                && $DB->get_manager()->field_exists($sess_tbl, 'thread_name')) {
            $auto_name = mb_substr(trim(preg_replace('/\s+/', ' ', $message)), 0, 80);
            $DB->set_field($sess_tbl, 'thread_name', $auto_name, ['id' => $session->id]);
        }

        // Update token + message counts
        $DB->update_record($sess_tbl, (object)[
            'id'            => $session->id,
            'message_count' => ($session->message_count ?? 0) + 1,
            'tokens_used'   => ($session->tokens_used ?? 0) + $tokens,
            'timemodified'  => $now,
        ]);

        if ($tokens > 0) {
            self::record_token_usage($USER->id, $tokens);
        }

        return [
            'reply'       => $reply,
            'tokens_used' => $tokens,
            'sources'     => json_encode($response['sources'] ?? []),
            'suggestions' => json_encode($response['suggestions'] ?? []),
        ];
    }

    public static function send_chat_message_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'reply'       => new \external_value(PARAM_RAW),
            'tokens_used' => new \external_value(PARAM_INT),
            'sources'     => new \external_value(PARAM_RAW),
            'suggestions' => new \external_value(PARAM_RAW),
        ]);
    }

    // =========================================================================
    //  end_chat_session
    // =========================================================================

    public static function end_chat_session_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_TEXT),
        ]);
    }

    public static function end_chat_session(string $session_id): bool
    {
        global $DB, $USER;

        ['session_id' => $session_id]
            = self::validate_parameters(self::end_chat_session_parameters(), compact('session_id'));

        $session = $DB->get_record('local_edzaiaxisfront_chat_sessions', ['axis_session_id' => $session_id, 'userid' => $USER->id]);
        if (!$session) return false;

        $client = new axis_client();
        $client->end_chat_session($session_id);

        $DB->update_record('local_edzaiaxisfront_chat_sessions', (object)[
            'id'          => $session->id,
            'timemodified' => time(),
        ]);

        return true;
    }

    public static function end_chat_session_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  KB functions (admin only)
    // =========================================================================

    public static function ingest_kb_url_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'url'         => new \external_value(PARAM_URL),
            'title'       => new \external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
            'description' => new \external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
            'doc_type'    => new \external_value(PARAM_TEXT, 'Document type', VALUE_DEFAULT, 'support'),
        ]);
    }

    public static function ingest_kb_url(string $url, string $title = '', string $description = '', string $doc_type = 'support'): array
    {
        global $DB;

        ['url' => $url, 'title' => $title, 'description' => $description, 'doc_type' => $doc_type]
            = self::validate_parameters(self::ingest_kb_url_parameters(), compact('url', 'title', 'description', 'doc_type'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $tenant_id = get_config('local_edzaiaxisfront', 'tenant_id');
        $client    = new axis_client();

        $safe_doc_type = in_array($doc_type, ['support','policy','how_to','faq','announcement','other'], true)
            ? $doc_type : 'support';

        $kb_payload = [
            'url'         => $url,
            'title'       => $title ?: $url,
            'description' => $description,
            'doc_type'    => $safe_doc_type,
        ];
        // Tenant resolved from API key on axisstand — only send when explicitly set.
        if (!empty($tenant_id)) {
            $kb_payload['tenant_id'] = $tenant_id;
        }
        $response = $client->kb_ingest_url($kb_payload);

        $now = time();
        $DB->insert_record('local_edzaiaxisfront_kb_items', (object)[
            'axis_kb_item_id' => $response['kb_item_id'] ?? '',
            'axis_job_id'     => $response['job_id'] ?? '',
            'doc_type'        => $safe_doc_type,
            'title'           => $title ?: $url,
            'source_url'      => $url,
            'status'          => 'processing',
            'is_active'       => 1,
            'timecreated'     => $now,
            'timemodified'    => $now,
        ]);

        return ['kb_item_id' => $response['kb_item_id'] ?? '', 'job_id' => $response['job_id'] ?? ''];
    }

    public static function ingest_kb_url_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'kb_item_id' => new \external_value(PARAM_TEXT),
            'job_id'     => new \external_value(PARAM_TEXT),
        ]);
    }

    // =========================================================================
    //  ingest_kb_text  — paste plain text into KB (avoids PARAM_URL rejection)
    // =========================================================================

    public static function ingest_kb_text_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'title'       => new \external_value(PARAM_TEXT),
            'content'     => new \external_value(PARAM_RAW, 'Plain text content to index'),
            'description' => new \external_value(PARAM_TEXT, '', VALUE_DEFAULT, ''),
            'doc_type'    => new \external_value(PARAM_TEXT, 'Document type', VALUE_DEFAULT, 'support'),
        ]);
    }

    public static function ingest_kb_text(string $title, string $content, string $description = '', string $doc_type = 'support'): array
    {
        global $DB, $USER;

        ['title' => $title, 'content' => $content, 'description' => $description, 'doc_type' => $doc_type]
            = self::validate_parameters(self::ingest_kb_text_parameters(), compact('title', 'content', 'description', 'doc_type'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $safe_doc_type = in_array($doc_type, ['support','policy','how_to','faq','announcement','other'], true)
            ? $doc_type : 'support';

        $client = new axis_client();
        $response = $client->kb_ingest_text([
            'text'                        => $content,
            'title'                       => $title,
            'doc_type'                    => $safe_doc_type,
            'uploaded_by_moodle_user_id'  => $USER->id,
        ]);

        $now = time();
        $DB->insert_record('local_edzaiaxisfront_kb_items', (object)[
            'axis_kb_item_id' => $response['kb_item_id'] ?? '',
            'axis_job_id'     => $response['job_id'] ?? '',
            'doc_type'        => $safe_doc_type,
            'title'           => $title,
            'content'         => $content,
            'source_url'      => '',
            'status'          => 'processing',
            'is_active'       => 1,
            'timecreated'     => $now,
            'timemodified'    => $now,
        ]);

        return ['kb_item_id' => $response['kb_item_id'] ?? '', 'job_id' => $response['job_id'] ?? ''];
    }

    public static function ingest_kb_text_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'kb_item_id' => new \external_value(PARAM_TEXT),
            'job_id'     => new \external_value(PARAM_TEXT),
        ]);
    }

    // =========================================================================
    //  ingest_kb_file  — upload a file as base64 into KB
    // =========================================================================

    public static function ingest_kb_file_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'title'       => new \external_value(PARAM_TEXT),
            'filename'    => new \external_value(PARAM_TEXT, 'Original file name'),
            'file_base64' => new \external_value(PARAM_RAW, 'Base64-encoded file content (no data: prefix)'),
            'doc_type'    => new \external_value(PARAM_TEXT, 'Document type', VALUE_DEFAULT, 'support'),
        ]);
    }

    public static function ingest_kb_file(string $title, string $filename, string $file_base64, string $doc_type = 'support'): array
    {
        global $DB, $USER;

        ['title' => $title, 'filename' => $filename, 'file_base64' => $file_base64, 'doc_type' => $doc_type]
            = self::validate_parameters(self::ingest_kb_file_parameters(), compact('title', 'filename', 'file_base64', 'doc_type'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $safe_doc_type = in_array($doc_type, ['support','policy','how_to','faq','announcement','other'], true)
            ? $doc_type : 'support';

        $client = new axis_client();
        $response = $client->kb_ingest_file_base64([
            'filename'                    => $filename,
            'file_base64'                 => $file_base64,
            'title'                       => $title ?: $filename,
            'doc_type'                    => $safe_doc_type,
            'uploaded_by_moodle_user_id'  => $USER->id,
        ]);

        $now = time();
        $DB->insert_record('local_edzaiaxisfront_kb_items', (object)[
            'axis_kb_item_id' => $response['kb_item_id'] ?? '',
            'axis_job_id'     => $response['job_id'] ?? '',
            'doc_type'        => $safe_doc_type,
            'title'           => $title ?: $filename,
            'source_url'      => $filename,   // store original filename for reference
            'status'          => 'processing',
            'is_active'       => 1,
            'timecreated'     => $now,
            'timemodified'    => $now,
        ]);

        return ['kb_item_id' => $response['kb_item_id'] ?? '', 'job_id' => $response['job_id'] ?? ''];
    }

    public static function ingest_kb_file_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'kb_item_id' => new \external_value(PARAM_TEXT),
            'job_id'     => new \external_value(PARAM_TEXT),
        ]);
    }

    public static function list_kb_items_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    public static function list_kb_items(): array
    {
        global $DB;

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $rows = $DB->get_records(
            'local_edzaiaxisfront_kb_items', [],
            'timecreated DESC',
            'id,axis_kb_item_id,doc_type,title,content,source_url,status,is_active,timecreated'
        );

        // ── Lazy status sync from Python ───────────────────────────────────────
        // Python is the source of truth for job status.
        // For any item still in a non-terminal state, fetch the live status from
        // Python and update the local record so future loads are also correct.
        $non_terminal = ['processing', 'pending', 'queued'];
        $needs_sync   = array_filter($rows, fn($r) =>
            in_array($r->status, $non_terminal, true) && !empty($r->axis_kb_item_id)
        );

        if (!empty($needs_sync)) {
            try {
                $client      = new axis_client();
                $python_list = $client->list_kb_items();   // GET /api/v1/kb/items

                // Build map: python_uuid → status
                $status_map = [];
                foreach ($python_list as $pitem) {
                    if (!empty($pitem['id'])) {
                        $status_map[$pitem['id']] = $pitem['status'];
                    }
                }

                // Apply updates
                foreach ($needs_sync as $row) {
                    $py_status = $status_map[$row->axis_kb_item_id] ?? null;
                    if ($py_status !== null && $py_status !== $row->status) {
                        $DB->set_field(
                            'local_edzaiaxisfront_kb_items',
                            'status', $py_status,
                            ['id' => $row->id]
                        );
                        $row->status = $py_status;   // update in-memory object too
                    }
                }
            } catch (\Throwable $e) {
                // Python unreachable — return cached statuses gracefully.
                debugging('Axis AI KB status sync failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id'              => (int) $row->id,
                'axis_kb_item_id' => $row->axis_kb_item_id,
                'doc_type'        => $row->doc_type,
                'title'           => $row->title,
                'content'         => $row->content ?? '',
                'source_url'      => $row->source_url ?? '',
                'status'          => $row->status,
                'is_active'       => (bool) $row->is_active,
                'timecreated'     => (int) $row->timecreated,
            ];
        }
        return $items;
    }

    public static function list_kb_items_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id'              => new \external_value(PARAM_INT),
                'axis_kb_item_id' => new \external_value(PARAM_TEXT),
                'doc_type'        => new \external_value(PARAM_RAW),
                'title'           => new \external_value(PARAM_TEXT),
                'content'         => new \external_value(PARAM_RAW,  '', VALUE_OPTIONAL),
                'source_url'      => new \external_value(PARAM_TEXT),
                'status'          => new \external_value(PARAM_RAW),
                'is_active'       => new \external_value(PARAM_BOOL),
                'timecreated'     => new \external_value(PARAM_INT),
            ])
        );
    }

    public static function toggle_kb_item_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'kb_item_id' => new \external_value(PARAM_TEXT),
            'is_active'  => new \external_value(PARAM_BOOL),
        ]);
    }

    public static function toggle_kb_item(string $kb_item_id, bool $is_active): bool
    {
        global $DB;

        ['kb_item_id' => $kb_item_id, 'is_active' => $is_active]
            = self::validate_parameters(self::toggle_kb_item_parameters(), compact('kb_item_id', 'is_active'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $client = new axis_client();
        $client->toggle_kb_item($kb_item_id, $is_active);

        $DB->set_field('local_edzaiaxisfront_kb_items', 'is_active', (int) $is_active, ['axis_kb_item_id' => $kb_item_id]);
        $DB->set_field('local_edzaiaxisfront_kb_items', 'timemodified', time(), ['axis_kb_item_id' => $kb_item_id]);

        return true;
    }

    public static function toggle_kb_item_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    public static function delete_kb_item_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'kb_item_id' => new \external_value(PARAM_TEXT),
        ]);
    }

    public static function delete_kb_item(string $kb_item_id): bool
    {
        global $DB;

        ['kb_item_id' => $kb_item_id]
            = self::validate_parameters(self::delete_kb_item_parameters(), compact('kb_item_id'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $client = new axis_client();
        $client->delete_kb_item($kb_item_id);

        $DB->delete_records('local_edzaiaxisfront_kb_items', ['axis_kb_item_id' => $kb_item_id]);

        return true;
    }

    public static function delete_kb_item_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  update_kb_item  — edit title / doc_type of an existing KB item
    // =========================================================================

    public static function update_kb_item_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'kb_item_id' => new \external_value(PARAM_TEXT, 'Axis KB item UUID'),
            'title'      => new \external_value(PARAM_TEXT,  'New title',    VALUE_DEFAULT, ''),
            'doc_type'   => new \external_value(PARAM_TEXT,  'New doc_type', VALUE_DEFAULT, ''),
            'content'    => new \external_value(PARAM_RAW,   'New text content (text items only)', VALUE_DEFAULT, ''),
        ]);
    }

    public static function update_kb_item(string $kb_item_id, string $title = '', string $doc_type = '', string $content = ''): bool
    {
        global $DB, $USER;

        ['kb_item_id' => $kb_item_id, 'title' => $title, 'doc_type' => $doc_type, 'content' => $content]
            = self::validate_parameters(self::update_kb_item_parameters(), compact('kb_item_id', 'title', 'doc_type', 'content'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $record = $DB->get_record('local_edzaiaxisfront_kb_items', ['axis_kb_item_id' => $kb_item_id]);
        if (!$record) {
            return false;
        }

        $client = new axis_client();

        // ── If content has changed, re-index the EXISTING Python item ────────
        // This calls POST /kb/items/{id}/reindex which keeps the same axis_kb_item_id
        // and just queues a new Celery job to re-embed with the new text content.
        $content_changed = ($content !== '' && $content !== ($record->content ?? ''));
        if ($content_changed) {
            $use_title    = $title    ?: $record->title;
            $use_doc_type = $doc_type ?: $record->doc_type;
            $safe_dt      = in_array($use_doc_type, ['support','policy','how_to','faq','announcement','other'], true)
                ? $use_doc_type : 'support';

            $response = $client->kb_reindex_item($kb_item_id, [
                'text'                       => $content,
                'title'                      => $use_title,
                'doc_type'                   => $safe_dt,
                'uploaded_by_moodle_user_id' => $USER->id,
            ]);

            // axis_kb_item_id stays the same — only status + content + job_id change
            $record->axis_job_id  = $response['job_id'] ?? '';
            $record->title        = $use_title;
            $record->doc_type     = $safe_dt;
            $record->content      = $content;
            $record->status       = 'processing';
            $record->timemodified = time();
            $DB->update_record('local_edzaiaxisfront_kb_items', $record);

        } else {
            // ── Metadata-only update (title / doc_type) ───────────────────────
            $fields = [];
            if ($title !== '')    { $fields['title']    = $title; }
            if ($doc_type !== '') { $fields['doc_type'] = $doc_type; }

            if (!empty($fields)) {
                $client->update_kb_item($kb_item_id, $fields);

                foreach ($fields as $k => $v) {
                    $record->$k = $v;
                }
                $record->timemodified = time();
                $DB->update_record('local_edzaiaxisfront_kb_items', $record);
            }
        }

        return true;
    }

    public static function update_kb_item_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  kb_backfill_active  — one-time fix: stamp is_active=true on existing chunks
    // =========================================================================

    public static function kb_backfill_active_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    public static function kb_backfill_active(): array
    {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $client = new axis_client();
        $result = $client->kb_backfill_active();

        return [
            'updated_items'     => (int)  ($result['updated_items']     ?? 0),
            'total_active_items'=> (int)  ($result['total_active_items'] ?? 0),
            'message'           => (string)($result['message']           ?? ''),
        ];
    }

    public static function kb_backfill_active_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'updated_items'      => new \external_value(PARAM_INT),
            'total_active_items' => new \external_value(PARAM_INT),
            'message'            => new \external_value(PARAM_TEXT),
        ]);
    }

    // =========================================================================
    //  get_token_usage_report  (admin)
    // =========================================================================

    public static function get_token_usage_report_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'month_year' => new \external_value(PARAM_TEXT, 'YYYY-MM format, e.g. 2026-03', VALUE_DEFAULT, ''),
            'search'     => new \external_value(PARAM_TEXT, 'Search by username/name', VALUE_DEFAULT, ''),
            'page'       => new \external_value(PARAM_INT,  'Page number (0-based)', VALUE_DEFAULT, 0),
            'perpage'    => new \external_value(PARAM_INT,  'Rows per page', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * Returns per-user token usage report with daily breakdown option.
     * Admin only.
     */
    public static function get_token_usage_report(string $month_year = '', string $search = '', int $page = 0, int $perpage = 50): array
    {
        global $DB;

        ['month_year' => $month_year, 'search' => $search, 'page' => $page, 'perpage' => $perpage]
            = self::validate_parameters(
                self::get_token_usage_report_parameters(),
                compact('month_year', 'search', 'page', 'perpage')
            );

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:viewreports', $context);

        // Default: current month
        if (!$month_year) $month_year = date('Y-m');

        // Validate format
        if (!preg_match('/^\d{4}-\d{2}$/', $month_year)) {
            throw new \invalid_parameter_exception('month_year must be in YYYY-MM format');
        }

        // Aggregate tokens per user for the month
        $params  = ['month_year' => $month_year];
        $search_sql = '';

        if ($search) {
            $search_sql = ' AND (' . $DB->sql_like('u.username', ':search1', false) .
                ' OR '  . $DB->sql_like($DB->sql_fullname('u.firstname', 'u.lastname'), ':search2', false) .
                ' OR '  . $DB->sql_like('u.email', ':search3', false) . ')';
            $params['search1'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['search2'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['search3'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        $sql = "SELECT tu.userid,
                       u.username,
                       u.firstname,
                       u.lastname,
                       u.email,
                       SUM(tu.tokens_day)   AS tokens_month,
                       MAX(tu.timemodified)  AS last_activity,
                       ul.token_monthly_limit AS override_monthly_limit,
                       ul.chat_daily_msg_limit AS override_daily_limit
                FROM {local_edzaiaxisfront_token_usage} tu
                JOIN {user} u ON u.id = tu.userid
                LEFT JOIN {local_edzaiaxisfront_user_limits} ul ON ul.userid = tu.userid
                WHERE tu.month_year = :month_year
                {$search_sql}
                GROUP BY tu.userid, u.username, u.firstname, u.lastname, u.email,
                         ul.token_monthly_limit, ul.chat_daily_msg_limit
                ORDER BY tokens_month DESC";

        $total = count($DB->get_records_sql($sql, $params));
        $rows  = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        $site_monthly_limit = (int) get_config('local_edzaiaxisfront', 'token_monthly_limit') ?: 0;

        $users = [];
        foreach ($rows as $row) {
            $effective_limit = $row->override_monthly_limit !== null
                ? (int) $row->override_monthly_limit
                : $site_monthly_limit;

            $pct = ($effective_limit > 0) ? round(($row->tokens_month / $effective_limit) * 100, 1) : 0;

            $users[] = [
                'userid'            => (int) $row->userid,
                'username'          => $row->username,
                'fullname'          => fullname($row),
                'email'             => $row->email,
                'tokens_month'      => (int) $row->tokens_month,
                'effective_limit'   => $effective_limit,
                'usage_pct'         => $pct,
                'has_override'      => ($row->override_monthly_limit !== null),
                'last_activity'     => (int) $row->last_activity,
            ];
        }

        // Site-wide totals for the month
        $site_total = (int) $DB->get_field_sql(
            "SELECT SUM(tokens_day) FROM {local_edzaiaxisfront_token_usage} WHERE month_year = :m",
            ['m' => $month_year]
        );

        return [
            'month_year'        => $month_year,
            'users'             => $users,
            'total_count'       => $total,
            'site_tokens_month' => $site_total,
            'site_limit'        => $site_monthly_limit,
        ];
    }

    public static function get_token_usage_report_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'month_year'        => new \external_value(PARAM_TEXT),
            'users'             => new \external_multiple_structure(
                new \external_single_structure([
                    'userid'          => new \external_value(PARAM_INT),
                    'username'        => new \external_value(PARAM_TEXT),
                    'fullname'        => new \external_value(PARAM_TEXT),
                    'email'           => new \external_value(PARAM_TEXT),
                    'tokens_month'    => new \external_value(PARAM_INT),
                    'effective_limit' => new \external_value(PARAM_INT),
                    'usage_pct'       => new \external_value(PARAM_FLOAT),
                    'has_override'    => new \external_value(PARAM_BOOL),
                    'last_activity'   => new \external_value(PARAM_INT),
                ])
            ),
            'total_count'       => new \external_value(PARAM_INT),
            'site_tokens_month' => new \external_value(PARAM_INT),
            'site_limit'        => new \external_value(PARAM_INT),
        ]);
    }

    // =========================================================================
    //  get_user_daily_usage  (admin drill-down)
    // =========================================================================

    public static function get_user_daily_usage_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'userid'     => new \external_value(PARAM_INT),
            'month_year' => new \external_value(PARAM_TEXT, 'YYYY-MM'),
        ]);
    }

    public static function get_user_daily_usage(int $userid, string $month_year): array
    {
        global $DB;

        ['userid' => $userid, 'month_year' => $month_year]
            = self::validate_parameters(self::get_user_daily_usage_parameters(), compact('userid', 'month_year'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:viewreports', $context);

        $rows = $DB->get_records(
            'local_edzaiaxisfront_token_usage',
            ['userid' => $userid, 'month_year' => $month_year],
            'day_date ASC',
            'day_date, tokens_day'
        );

        $days = [];
        foreach ($rows as $row) {
            $days[] = [
                'day_date'   => $row->day_date,
                'tokens_day' => (int) $row->tokens_day,
            ];
        }

        $user = $DB->get_record('user', ['id' => $userid], 'id,firstname,lastname,username', MUST_EXIST);

        return [
            'userid'     => $userid,
            'fullname'   => fullname($user),
            'month_year' => $month_year,
            'days'       => $days,
        ];
    }

    public static function get_user_daily_usage_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'userid'     => new \external_value(PARAM_INT),
            'fullname'   => new \external_value(PARAM_TEXT),
            'month_year' => new \external_value(PARAM_TEXT),
            'days'       => new \external_multiple_structure(
                new \external_single_structure([
                    'day_date'   => new \external_value(PARAM_TEXT),
                    'tokens_day' => new \external_value(PARAM_INT),
                ])
            ),
        ]);
    }

    // =========================================================================
    //  save_user_override  (admin)
    // =========================================================================

    public static function save_user_override_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'userid'                  => new \external_value(PARAM_INT),
            'chat_session_msg_limit'  => new \external_value(PARAM_INT, 'NULL=-1 (use site default)', VALUE_DEFAULT, -1),
            'chat_daily_msg_limit'    => new \external_value(PARAM_INT, 'NULL=-1', VALUE_DEFAULT, -1),
            'chat_monthly_msg_limit'  => new \external_value(PARAM_INT, 'NULL=-1', VALUE_DEFAULT, -1),
            'token_monthly_limit'     => new \external_value(PARAM_INT, 'NULL=-1', VALUE_DEFAULT, -1),
            'note'                    => new \external_value(PARAM_TEXT, 'Optional admin note', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Save or update a per-user token/limit override.
     * Pass -1 for any field to mean "use site default" (stored as NULL).
     * Syncs to axis-ai immediately.
     */
    public static function save_user_override(
        int $userid,
        int $chat_session_msg_limit = -1,
        int $chat_daily_msg_limit   = -1,
        int $chat_monthly_msg_limit = -1,
        int $token_monthly_limit    = -1,
        string $note = ''
    ): array {
        global $DB, $USER;

        $args = compact(
            'userid',
            'chat_session_msg_limit',
            'chat_daily_msg_limit',
            'chat_monthly_msg_limit',
            'token_monthly_limit',
            'note'
        );
        $args = self::validate_parameters(self::save_user_override_parameters(), $args);
        extract($args);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        // -1 sentinel → NULL (use site default)
        $to_null = fn($v) => $v === -1 ? null : $v;

        $session_limit  = $to_null($chat_session_msg_limit);
        $daily_limit    = $to_null($chat_daily_msg_limit);
        $monthly_limit  = $to_null($chat_monthly_msg_limit);
        $token_limit    = $to_null($token_monthly_limit);

        $moodle_user = $DB->get_record('user', ['id' => $userid], 'id,username,firstname,lastname', MUST_EXIST);
        $now = time();

        $existing = $DB->get_record('local_edzaiaxisfront_user_limits', ['userid' => $userid]);
        if ($existing) {
            $DB->update_record('local_edzaiaxisfront_user_limits', (object)[
                'id'                      => $existing->id,
                'chat_session_msg_limit'  => $session_limit,
                'chat_daily_msg_limit'    => $daily_limit,
                'chat_monthly_msg_limit'  => $monthly_limit,
                'token_monthly_limit'     => $token_limit,
                'note'                    => $note,
                'set_by_userid'           => $USER->id,
                'timemodified'            => $now,
            ]);
        } else {
            $DB->insert_record('local_edzaiaxisfront_user_limits', (object)[
                'userid'                  => $userid,
                'chat_session_msg_limit'  => $session_limit,
                'chat_daily_msg_limit'    => $daily_limit,
                'chat_monthly_msg_limit'  => $monthly_limit,
                'token_monthly_limit'     => $token_limit,
                'note'                    => $note,
                'set_by_userid'           => $USER->id,
                'timecreated'             => $now,
                'timemodified'            => $now,
            ]);
        }

        // Sync to axis-ai — uses /me route, no tenant UUID required
        if (get_config('local_edzaiaxisfront', 'api_base_url')) {
            try {
                $client = new axis_client();
                $client->upsert_user_override([
                    'moodle_user_id'          => $userid,
                    'chat_session_msg_limit'  => $session_limit,
                    'chat_daily_msg_limit'    => $daily_limit,
                    'chat_monthly_msg_limit'  => $monthly_limit,
                    'token_monthly_limit'     => $token_limit,
                    'note'                    => $note,
                    'set_by_moodle_user_id'   => $USER->id,
                ]);
            } catch (\Exception $e) {
                // Log but don't fail the Moodle save
                debugging('save_user_override: axis-ai sync failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return [
            'success'  => true,
            'userid'   => $userid,
            'fullname' => fullname($moodle_user),
        ];
    }

    public static function save_user_override_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'success'  => new \external_value(PARAM_BOOL),
            'userid'   => new \external_value(PARAM_INT),
            'fullname' => new \external_value(PARAM_TEXT),
        ]);
    }

    // =========================================================================
    //  delete_user_override  (admin)
    // =========================================================================

    public static function delete_user_override_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'userid' => new \external_value(PARAM_INT),
        ]);
    }

    public static function delete_user_override(int $userid): bool
    {
        global $DB;

        ['userid' => $userid]
            = self::validate_parameters(self::delete_user_override_parameters(), compact('userid'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $DB->delete_records('local_edzaiaxisfront_user_limits', ['userid' => $userid]);

        // Remove from axis-ai — uses /me route, no tenant UUID required
        if (get_config('local_edzaiaxisfront', 'api_base_url')) {
            try {
                $client = new axis_client();
                $client->delete_user_override($userid);
            } catch (\Exception $e) {
                debugging('delete_user_override: axis-ai sync failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return true;
    }

    public static function delete_user_override_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  list_user_overrides  (admin)
    // =========================================================================

    public static function list_user_overrides_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    public static function list_user_overrides(): array
    {
        global $DB;

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $sql = "SELECT ul.*, u.username, u.firstname, u.lastname, u.email,
                       s.username AS set_by_username, s.firstname AS set_by_firstname, s.lastname AS set_by_lastname
                FROM {local_edzaiaxisfront_user_limits} ul
                JOIN {user} u  ON u.id  = ul.userid
                LEFT JOIN {user} s ON s.id = ul.set_by_userid
                ORDER BY ul.timemodified DESC";

        $rows  = $DB->get_records_sql($sql);
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'userid'                  => (int) $row->userid,
                'fullname'                => fullname($row),
                'email'                   => $row->email,
                'chat_session_msg_limit'  => $row->chat_session_msg_limit !== null ? (int)$row->chat_session_msg_limit : -1,
                'chat_daily_msg_limit'    => $row->chat_daily_msg_limit   !== null ? (int)$row->chat_daily_msg_limit   : -1,
                'chat_monthly_msg_limit'  => $row->chat_monthly_msg_limit !== null ? (int)$row->chat_monthly_msg_limit : -1,
                'token_monthly_limit'     => $row->token_monthly_limit    !== null ? (int)$row->token_monthly_limit    : -1,
                'note'                    => $row->note ?? '',
                'set_by'                  => $row->set_by_username ? fullname((object)['firstname' => $row->set_by_firstname, 'lastname' => $row->set_by_lastname]) : '',
                'timemodified'            => (int) $row->timemodified,
            ];
        }
        return $items;
    }

    public static function list_user_overrides_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'userid'                 => new \external_value(PARAM_INT),
                'fullname'               => new \external_value(PARAM_TEXT),
                'email'                  => new \external_value(PARAM_TEXT),
                'chat_session_msg_limit' => new \external_value(PARAM_INT),
                'chat_daily_msg_limit'   => new \external_value(PARAM_INT),
                'chat_monthly_msg_limit' => new \external_value(PARAM_INT),
                'token_monthly_limit'    => new \external_value(PARAM_INT),
                'note'                   => new \external_value(PARAM_TEXT),
                'set_by'                 => new \external_value(PARAM_TEXT),
                'timemodified'           => new \external_value(PARAM_INT),
            ])
        );
    }

    // =========================================================================
    //  sync_tenant_settings  (admin)
    // =========================================================================

    public static function sync_tenant_settings_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    /**
     * Push current Moodle rate-limit settings to the axis-ai tenant record.
     * Call this after changing settings in Administration → axis-ai → Settings.
     */
    public static function sync_tenant_settings(): array
    {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $payload = [
            'chat_session_msg_limit'  => (int) get_config('local_edzaiaxisfront', 'chat_session_msg_limit'),
            'chat_daily_msg_limit'    => (int) get_config('local_edzaiaxisfront', 'chat_daily_msg_limit'),
            'chat_monthly_msg_limit'  => (int) get_config('local_edzaiaxisfront', 'chat_monthly_msg_limit'),
            'token_monthly_limit'     => (int) get_config('local_edzaiaxisfront', 'token_monthly_limit'),
        ];

        try {
            $client = new axis_client();
            // Uses POST /api/v1/admin/tenants/me/settings — tenant identified by API key,
            // so no tenant UUID is needed and Moodle's PUT curl bug is avoided.
            $client->sync_rate_limits($payload);
        } catch (\Exception $e) {
            throw new \moodle_exception('error_api_connection', 'local_edzaiaxisfront', '', ($e instanceof axis_client_exception ? $e->detail : $e->getMessage()), $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Rate limits synced to axis-ai: ' . json_encode($payload),
        ];
    }

    public static function sync_tenant_settings_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL),
            'message' => new \external_value(PARAM_TEXT),
        ]);
    }

    // =========================================================================
    //  search_users  (admin — user search autocomplete)
    // =========================================================================

    public static function search_users_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'query' => new \external_value(PARAM_TEXT, 'Search string (name, username, or email)'),
        ]);
    }

    /**
     * Search Moodle users by name/username/email for the override user-picker.
     * Returns up to 20 matches. Admin-only.
     */
    public static function search_users(string $query): array
    {
        global $DB;

        ['query' => $query] = self::validate_parameters(self::search_users_parameters(), compact('query'));

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:manageplugin', $context);

        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $like  = $DB->sql_like_escape($query);
        $params = array_fill(0, 5, "%{$like}%");

        $sql = "SELECT id, firstname, lastname, username, email
                FROM {user}
                WHERE deleted = 0 AND confirmed = 1
                  AND (
                      " . $DB->sql_like('firstname', '?', false) . "
                      OR " . $DB->sql_like('lastname',  '?', false) . "
                      OR " . $DB->sql_like($DB->sql_concat('firstname', "' '", 'lastname'), '?', false) . "
                      OR " . $DB->sql_like('username',  '?', false) . "
                      OR " . $DB->sql_like('email',     '?', false) . "
                  )
                ORDER BY lastname, firstname
                LIMIT 20";

        $rows   = $DB->get_records_sql($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id'       => (int) $row->id,
                'fullname' => fullname($row),
                'username' => $row->username,
                'email'    => $row->email,
            ];
        }
        return $result;
    }

    public static function search_users_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id'       => new \external_value(PARAM_INT),
                'fullname' => new \external_value(PARAM_TEXT),
                'username' => new \external_value(PARAM_TEXT),
                'email'    => new \external_value(PARAM_TEXT),
            ])
        );
    }

    // =========================================================================
    //  Private helpers
    // =========================================================================

    private static function get_site_features(): array
    {
        $features = [];
        foreach (['summary', 'glossary', 'flashcards', 'quiz', 'faq', 'infographic', 'transcript', 'chatbot', 'kb_chat'] as $f) {
            if (get_config('local_edzaiaxisfront', "feature_{$f}")) {
                $features[] = $f;
            }
        }
        return $features;
    }

    private static function empty_outputs(): array
    {
        return [
            'available_features' => [],
            'summary'            => '',
            'faq'                => '',
            'infographic'        => '',
            'flashcards'         => '[]',
            'glossary'           => '[]',
            'quiz'               => '[]',
            'transcript'         => '',
            'chatbot_enabled'    => '0',
            'kb_chat_enabled'    => '0',
        ];
    }

    private static function record_token_usage(int $userid, int $tokens): void
    {
        global $DB;

        $today     = date('Y-m-d');
        $month     = date('Y-m');
        $now       = time();

        $existing = $DB->get_record('local_edzaiaxisfront_token_usage', ['userid' => $userid, 'day_date' => $today]);
        if ($existing) {
            $DB->update_record('local_edzaiaxisfront_token_usage', (object)[
                'id'           => $existing->id,
                'tokens_day'   => $existing->tokens_day + $tokens,
                'tokens_month' => $existing->tokens_month + $tokens,
                'msg_count'    => $existing->msg_count + 1,
                'timemodified' => $now,
            ]);
        } else {
            // Sum existing monthly tokens for this user
            $month_total = (int) $DB->get_field_select(
                'local_edzaiaxisfront_token_usage',
                'SUM(tokens_day)',
                'userid = ? AND month_year = ?',
                [$userid, $month]
            );
            $DB->insert_record('local_edzaiaxisfront_token_usage', (object)[
                'userid'       => $userid,
                'day_date'     => $today,
                'month_year'   => $month,
                'tokens_day'   => $tokens,
                'tokens_month' => $month_total + $tokens,
                'msg_count'    => 1,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }
    }

    // =========================================================================
    //  get_session_messages  — returns stored messages for a thread
    // =========================================================================

    public static function get_session_messages_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_TEXT, 'axis session UUID'),
        ]);
    }

    public static function get_session_messages(string $session_id): array
    {
        global $DB, $USER;

        ['session_id' => $session_id]
            = self::validate_parameters(self::get_session_messages_parameters(), compact('session_id'));

        $session = $DB->get_record(
            'local_edzaiaxisfront_chat_sessions',
            ['axis_session_id' => $session_id, 'userid' => $USER->id],
            'id, thread_name, chat_mode, timecreated, message_count, cmid',
            IGNORE_MISSING
        );
        if (!$session) {
            return ['session_id' => $session_id, 'thread_name' => '', 'messages' => '[]'];
        }

        $msgs_tbl = 'local_edzaiaxisfront_chat_messages';
        $messages = [];
        if ($DB->get_manager()->table_exists($msgs_tbl)) {
            $rows = $DB->get_records($msgs_tbl, ['sessionid' => $session->id], 'timecreated ASC', 'id, role, message, timecreated');
            foreach ($rows as $r) {
                $messages[] = ['role' => $r->role, 'message' => $r->message, 'time' => (int) $r->timecreated];
            }
        }

        return [
            'session_id'  => $session_id,
            'thread_name' => $session->thread_name ?? '',
            'messages'    => json_encode($messages),
        ];
    }

    public static function get_session_messages_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'session_id'  => new \external_value(PARAM_TEXT),
            'thread_name' => new \external_value(PARAM_TEXT),
            'messages'    => new \external_value(PARAM_RAW, 'JSON array of {role, message, time}'),
        ]);
    }

    // =========================================================================
    //  rename_thread  — lets user rename a chat session thread
    // =========================================================================

    public static function rename_thread_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'session_id' => new \external_value(PARAM_TEXT),
            'name'       => new \external_value(PARAM_TEXT),
        ]);
    }

    public static function rename_thread(string $session_id, string $name): bool
    {
        global $DB, $USER;

        ['session_id' => $session_id, 'name' => $name]
            = self::validate_parameters(self::rename_thread_parameters(), compact('session_id', 'name'));

        $session = $DB->get_record(
            'local_edzaiaxisfront_chat_sessions',
            ['axis_session_id' => $session_id, 'userid' => $USER->id],
            'id',
            IGNORE_MISSING
        );
        if (!$session) return false;

        if (!$DB->get_manager()->field_exists('local_edzaiaxisfront_chat_sessions', 'thread_name')) {
            return false;
        }
        $clean = mb_substr(trim($name), 0, 80);
        $DB->set_field('local_edzaiaxisfront_chat_sessions', 'thread_name', $clean, ['id' => $session->id]);
        return true;
    }

    public static function rename_thread_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  get_support_right_panel  — FAQ stream and popular questions for support
    // =========================================================================

    public static function get_support_right_panel_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    public static function get_support_right_panel(): array
    {
        global $DB;

        self::validate_context(\context_system::instance());

        // Show active KB items as "highlights" so the student can see what topics
        // are available in the knowledge base before asking a question.
        // Ordered by most recently added (newest first), limited to 8.
        $kb_rows = $DB->get_records_select(
            'local_edzaiaxisfront_kb_items',
            "is_active = 1 AND status = 'ready'",
            [],
            'timecreated DESC',
            'title, doc_type, timecreated',
            0, 8
        );

        $faqs = [];
        foreach ($kb_rows as $row) {
            $faqs[] = [
                'q' => (string) $row->title,
                'a' => ucfirst(str_replace('_', ' ', (string) $row->doc_type)),
            ];
        }

        // Most common support topics (last 30 days)
        $month_ago = time() - (30 * DAYSECS);
        $recent_support = (int) $DB->count_records_select(
            'local_edzaiaxisfront_chat_sessions',
            "chat_mode = 'support' AND timecreated > ?",
            [$month_ago]
        );

        return [
            'faqs_json'     => json_encode($faqs),
            'support_count' => $recent_support,
        ];
    }

    public static function get_support_right_panel_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'faqs_json'     => new \external_value(PARAM_RAW),
            'support_count' => new \external_value(PARAM_INT),
        ]);
    }

    // =========================================================================
    //  get_study_activity_stream  — recent study events for right panel
    // =========================================================================

    public static function get_study_activity_stream_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'limit' => new \external_value(PARAM_INT, '', VALUE_DEFAULT, 12),
        ]);
    }

    public static function get_study_activity_stream(int $limit = 12): array
    {
        global $DB, $USER;

        self::validate_context(\context_system::instance());

        $sql = "SELECT cs.id, cs.cmid, cs.axis_session_id, cs.thread_name, cs.message_count, cs.tokens_used,
                       cs.timecreated, cm.module, cm.instance, cm.course AS courseid
                  FROM {local_edzaiaxisfront_chat_sessions} cs
             LEFT JOIN {course_modules} cm ON cm.id = cs.cmid
                 WHERE cs.userid = ? AND cs.chat_mode = 'study'
                   AND cs.cmid > 0 AND cs.message_count > 0
              ORDER BY cs.timecreated DESC";

        $rows = $DB->get_records_sql($sql, [$USER->id], 0, $limit);
        $events = [];
        foreach ($rows as $row) {
            $cm_name = $row->thread_name ?: "Content #{$row->cmid}";
            if (empty($row->thread_name) && !empty($row->module) && !empty($row->instance)) {
                $modname = $DB->get_field('modules', 'name', ['id' => $row->module]);
                if ($modname && $DB->get_manager()->table_exists($modname)) {
                    $resolved = $DB->get_field($modname, 'name', ['id' => $row->instance]);
                    if ($resolved) $cm_name = $resolved;
                }
            }
            // Course name
            $course_name = '';
            if (!empty($row->courseid)) {
                $course_name = $DB->get_field('course', 'shortname', ['id' => $row->courseid]) ?: '';
            }
            $events[] = [
                'cm_name'         => $cm_name,
                'axis_session_id' => $row->axis_session_id,
                'cmid'            => (int) $row->cmid,
                'course_name'   => $course_name,
                'message_count' => (int) $row->message_count,
                'tokens_used'   => (int) $row->tokens_used,
                'time_label'    => userdate((int) $row->timecreated, get_string('strftimedate', 'langconfig')),
                'timecreated'   => (int) $row->timecreated,
            ];
        }

        return ['events_json' => json_encode($events)];
    }

    public static function get_study_activity_stream_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'events_json' => new \external_value(PARAM_RAW),
        ]);
    }

    // =========================================================================
    //  get_token_usage_summary  — for analysis mode right panel
    // =========================================================================

    public static function get_token_usage_summary_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    public static function get_token_usage_summary(): array
    {
        global $DB, $USER;

        self::validate_context(\context_system::instance());

        $today       = date('Y-m-d');
        $this_month  = date('Y-m');
        $next_reset  = date('Y-m-01', strtotime('first day of next month'));

        // Today's usage
        $today_row = $DB->get_record('local_edzaiaxisfront_token_usage',
            ['userid' => $USER->id, 'day_date' => $today], 'tokens_day, msg_count', IGNORE_MISSING);
        $today_tokens = (int) ($today_row->tokens_day ?? 0);
        $today_msgs   = (int) ($today_row->msg_count  ?? 0);

        // This month's total
        $month_tokens = (int) ($DB->get_field_select(
            'local_edzaiaxisfront_token_usage',
            'SUM(tokens_day)',
            'userid = ? AND month_year = ?',
            [$USER->id, $this_month]
        ) ?: 0);
        $month_msgs = (int) ($DB->get_field_select(
            'local_edzaiaxisfront_token_usage',
            'SUM(msg_count)',
            'userid = ? AND month_year = ?',
            [$USER->id, $this_month]
        ) ?: 0);

        // Total lifetime
        $total_tokens = (int) ($DB->get_field_select(
            'local_edzaiaxisfront_chat_sessions',
            'SUM(tokens_used)',
            'userid = ?',
            [$USER->id]
        ) ?: 0);

        // Per-user limit (from user_limits override or site config)
        $override = $DB->get_record('local_edzaiaxisfront_user_limits', ['userid' => $USER->id],
            'token_monthly_limit', IGNORE_MISSING);
        $site_limit    = (int) (get_config('local_edzaiaxisfront', 'monthly_token_limit') ?: 0);
        $monthly_limit = (int) ($override->token_monthly_limit ?? $site_limit);

        $pct_used = ($monthly_limit > 0) ? min(100, round(($month_tokens / $monthly_limit) * 100)) : -1;

        return [
            'today_tokens'   => $today_tokens,
            'today_msgs'     => $today_msgs,
            'month_tokens'   => $month_tokens,
            'month_msgs'     => $month_msgs,
            'total_tokens'   => $total_tokens,
            'monthly_limit'  => $monthly_limit,
            'pct_used'       => $pct_used,
            'next_reset'     => $next_reset,
            'this_month'     => $this_month,
        ];
    }

    public static function get_token_usage_summary_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'today_tokens'   => new \external_value(PARAM_INT),
            'today_msgs'     => new \external_value(PARAM_INT),
            'month_tokens'   => new \external_value(PARAM_INT),
            'month_msgs'     => new \external_value(PARAM_INT),
            'total_tokens'   => new \external_value(PARAM_INT),
            'monthly_limit'  => new \external_value(PARAM_INT),
            'pct_used'       => new \external_value(PARAM_INT),
            'next_reset'     => new \external_value(PARAM_TEXT),
            'this_month'     => new \external_value(PARAM_TEXT),
        ]);
    }

    // =========================================================================
    //  save_glossary_edit / save_flashcards_edit / save_quiz_edit  — teacher editing
    // =========================================================================

    public static function save_glossary_edit_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'       => new \external_value(PARAM_INT),
            'terms_json' => new \external_value(PARAM_RAW, 'JSON array of {term, definition, context_note}'),
        ]);
    }

    public static function save_glossary_edit(int $cmid, string $terms_json): bool
    {
        global $DB, $USER;

        ['cmid' => $cmid, 'terms_json' => $terms_json]
            = self::validate_parameters(self::save_glossary_edit_parameters(), compact('cmid', 'terms_json'));

        $context = \context_module::instance($cmid);
        require_capability('local/edzaiaxisfront:managecontent', $context);

        $terms = json_decode($terms_json, true);
        if (!is_array($terms)) throw new \moodle_exception('Invalid terms JSON');

        $now = time();
        $DB->delete_records('local_edzaiaxisfront_glossary', ['cmid' => $cmid]);
        foreach ($terms as $i => $t) {
            $DB->insert_record('local_edzaiaxisfront_glossary', (object)[
                'cmid'         => $cmid,
                'term'         => mb_substr(trim($t['term'] ?? ''), 0, 255),
                'definition'   => trim($t['definition'] ?? ''),
                'context_note' => trim($t['context_note'] ?? ''),
                'position'     => $i,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }

        // Sync to axis-ai (best-effort — local save already succeeded)
        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid], 'axis_content_item_id');
        if ($config && !empty($config->axis_content_item_id)) {
            try {
                $client = new axis_client();
                $client->replace_glossary($config->axis_content_item_id, $terms, $USER->id);
            } catch (\Throwable $e) {
                debugging('Axis AI glossary sync failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return true;
    }

    public static function save_glossary_edit_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    public static function save_flashcards_edit_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'       => new \external_value(PARAM_INT),
            'cards_json' => new \external_value(PARAM_RAW, 'JSON array of {front, back}'),
        ]);
    }

    public static function save_flashcards_edit(int $cmid, string $cards_json): bool
    {
        global $DB, $USER;

        ['cmid' => $cmid, 'cards_json' => $cards_json]
            = self::validate_parameters(self::save_flashcards_edit_parameters(), compact('cmid', 'cards_json'));

        $context = \context_module::instance($cmid);
        require_capability('local/edzaiaxisfront:managecontent', $context);

        $cards = json_decode($cards_json, true);
        if (!is_array($cards)) throw new \moodle_exception('Invalid cards JSON');

        $now = time();
        $DB->set_field('local_edzaiaxisfront_flashcards', 'is_active', 0, ['cmid' => $cmid]);
        foreach ($cards as $i => $c) {
            $existing = $DB->get_record('local_edzaiaxisfront_flashcards',
                ['cmid' => $cmid, 'position' => $i], 'id', IGNORE_MISSING);
            if ($existing) {
                $DB->update_record('local_edzaiaxisfront_flashcards', (object)[
                    'id'           => $existing->id,
                    'front'        => trim($c['front'] ?? ''),
                    'back'         => trim($c['back'] ?? ''),
                    'is_active'    => 1,
                    'timemodified' => $now,
                ]);
            } else {
                $DB->insert_record('local_edzaiaxisfront_flashcards', (object)[
                    'cmid'         => $cmid,
                    'front'        => trim($c['front'] ?? ''),
                    'back'         => trim($c['back'] ?? ''),
                    'position'     => $i,
                    'is_active'    => 1,
                    'timecreated'  => $now,
                    'timemodified' => $now,
                ]);
            }
        }

        // Sync to axis-ai (best-effort — local save already succeeded)
        $config = $DB->get_record('local_edzaiaxisfront_cm_config', ['cmid' => $cmid], 'axis_content_item_id');
        if ($config && !empty($config->axis_content_item_id)) {
            try {
                $client = new axis_client();
                $client->replace_flashcards($config->axis_content_item_id, $cards, $USER->id);
            } catch (\Throwable $e) {
                debugging('Axis AI flashcards sync failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return true;
    }

    public static function save_flashcards_edit_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    public static function save_quiz_edit_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'           => new \external_value(PARAM_INT),
            'questions_json' => new \external_value(PARAM_RAW),
        ]);
    }

    public static function save_quiz_edit(int $cmid, string $questions_json): bool
    {
        global $DB, $USER;

        ['cmid' => $cmid, 'questions_json' => $questions_json]
            = self::validate_parameters(self::save_quiz_edit_parameters(), compact('cmid', 'questions_json'));

        $context = \context_module::instance($cmid);
        require_capability('local/edzaiaxisfront:managecontent', $context);

        $questions = json_decode($questions_json, true);
        if (!is_array($questions)) throw new \moodle_exception('Invalid questions JSON');

        $now = time();
        $DB->set_field('local_edzaiaxisfront_quiz_questions', 'is_active', 0, ['cmid' => $cmid]);
        foreach ($questions as $i => $q) {
            $DB->insert_record('local_edzaiaxisfront_quiz_questions', (object)[
                'cmid'          => $cmid,
                'question_text' => trim($q['question_text'] ?? ''),
                'answer'        => trim($q['answer'] ?? ''),
                'options_json'  => json_encode($q['options'] ?? []),
                'difficulty'    => $q['difficulty'] ?? 'medium',
                'blooms_level'  => $q['blooms_level'] ?? 'remember',
                'question_type' => $q['question_type'] ?? 'mcq',
                'is_active'     => 1,
                'timecreated'   => $now,
                'timemodified'  => $now,
            ]);
        }
        return true;
    }

    public static function save_quiz_edit_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  save_faq_edit  — teacher editing of the FAQ output (stored locally)
    // =========================================================================

    public static function save_faq_edit_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'cmid'  => new \external_value(PARAM_INT),
            'faqs'  => new \external_value(PARAM_RAW, 'JSON array of {question, answer} objects'),
        ]);
    }

    public static function save_faq_edit(int $cmid, string $faqs): bool
    {
        global $DB;

        ['cmid' => $cmid, 'faqs' => $faqs]
            = self::validate_parameters(self::save_faq_edit_parameters(), ['cmid' => $cmid, 'faqs' => $faqs]);

        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:managecourse', $context);

        // Validate + normalise the incoming FAQ list.
        $decoded = json_decode($faqs, true);
        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception('faqs must be a JSON array');
        }
        $clean = [];
        foreach ($decoded as $item) {
            $q = trim((string) ($item['question'] ?? ''));
            $a = trim((string) ($item['answer'] ?? ''));
            if ($q !== '') {
                $clean[] = ['question' => $q, 'answer' => $a];
            }
        }
        $content = json_encode(array_values($clean));

        // Stored as a text output (output_type='faq'), same table the sync writes to.
        // Flag is_teacher_edited so future syncs don't clobber the teacher's version.
        $now = time();
        $existing = $DB->get_record('local_edzaiaxisfront_outputs', ['cmid' => $cmid, 'output_type' => 'faq']);
        if ($existing) {
            $DB->update_record('local_edzaiaxisfront_outputs', (object)[
                'id'                => $existing->id,
                'content'           => $content,
                'is_teacher_edited' => 1,
                'timemodified'      => $now,
            ]);
        } else {
            $DB->insert_record('local_edzaiaxisfront_outputs', (object)[
                'cmid'              => $cmid,
                'output_type'       => 'faq',
                'content'           => $content,
                'is_teacher_edited' => 1,
                'is_visible'        => 1,
                'word_count'        => 0,
                'timecreated'       => $now,
                'timemodified'      => $now,
            ]);
        }
        return true;
    }

    public static function save_faq_edit_returns(): \external_value
    {
        return new \external_value(PARAM_BOOL);
    }

    // =========================================================================
    //  get_course_ai_cmids  — learner-safe: which cmids in a course have
    //  AI content the CURRENT user is allowed to see (for course-page badges)
    // =========================================================================

    public static function get_course_ai_cmids_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function get_course_ai_cmids(int $courseid): array
    {
        global $DB;

        ['courseid' => $courseid] = self::validate_parameters(
            self::get_course_ai_cmids_parameters(), compact('courseid'));

        $context = \context_course::instance($courseid);
        self::validate_context($context);

        if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
            return ['cmids' => []];
        }

        $configs = $DB->get_records(
            'local_edzaiaxisfront_cm_config',
            ['courseid' => $courseid, 'status' => 'ready'],
            '',
            'cmid, enabled_features, visible_features, generated_features'
        );

        $site_features = self::get_site_features();
        $cmids = [];
        foreach ($configs as $c) {
            // Per-activity capability + availability check — same gating as the
            // student panel (get_cm_outputs), so no hidden/unauthorised content leaks.
            try {
                $ctx = \context_module::instance($c->cmid);
            } catch (\Exception $e) {
                continue;
            }
            if (!has_capability('local/edzaiaxisfront:viewstudent', $ctx)) {
                continue;
            }
            $enabled   = json_decode($c->enabled_features   ?? '[]', true) ?: [];
            $visible   = json_decode($c->visible_features   ?? '[]', true) ?: [];
            $generated = json_decode($c->generated_features ?? '[]', true) ?: [];
            $show = array_intersect($site_features, $enabled, $visible, $generated);
            if (!empty($show)) {
                $cmids[] = (int) $c->cmid;
            }
        }

        return ['cmids' => array_values($cmids)];
    }

    public static function get_course_ai_cmids_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'cmids' => new \external_multiple_structure(new \external_value(PARAM_INT)),
        ]);
    }
}
