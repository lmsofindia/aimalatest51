<?php
/**
 * Web service function and service definitions.
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    // ── Teacher / Dashboard ────────────────────────────────────────────────

    'local_edzaiaxisfront_get_course_cms' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_course_cms',
        'description'   => 'Get all supported course modules with their AI status.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_cm_config' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_cm_config',
        'description'   => 'Get the AI configuration for a single course module.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_save_wizard_config' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'save_wizard_config',
        'description'   => 'Save wizard step 3: enabled features and generation parameters.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_submit_ingest' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'submit_ingest',
        'description'   => 'Trigger axis-ai ingest for one or more course modules.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_job_status' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_job_status',
        'description'   => 'Get the current job status and progress for a CM.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_toggle_output_visibility' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'toggle_output_visibility',
        'description'   => 'Show or hide an output type for students.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_save_summary_edit' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'save_summary_edit',
        'description'   => 'Save a teacher-edited summary.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_trigger_regenerate' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'trigger_regenerate',
        'description'   => 'Trigger regeneration of specific output tasks.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // ── Student panel ──────────────────────────────────────────────────────

    'local_edzaiaxisfront_get_cm_outputs' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_cm_outputs',
        'description'   => 'Get all visible AI outputs for a course module (student view).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_create_chat_session' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'create_chat_session',
        'description'   => 'Create a new AI chat session for a course module.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_send_chat_message' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'send_chat_message',
        'description'   => 'Send a message in a chat session and get the AI reply.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_end_chat_session' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'end_chat_session',
        'description'   => 'End a chat session.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // ── Admin: Knowledge Base ──────────────────────────────────────────────

    'local_edzaiaxisfront_ingest_kb_url' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'ingest_kb_url',
        'description'   => 'Ingest a URL into the knowledge base (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_list_kb_items' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'list_kb_items',
        'description'   => 'List all knowledge base items (admin only).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_toggle_kb_item' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'toggle_kb_item',
        'description'   => 'Enable or disable a knowledge base item (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_delete_kb_item' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'delete_kb_item',
        'description'   => 'Delete a knowledge base item (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_ingest_kb_text' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'ingest_kb_text',
        'description'   => 'Ingest plain-text content into the knowledge base (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_ingest_kb_file' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'ingest_kb_file',
        'description'   => 'Ingest a base64-encoded file into the knowledge base (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_update_kb_item' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'update_kb_item',
        'description'   => 'Update title / doc_type of a knowledge base item (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_kb_backfill_active' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'kb_backfill_active',
        'description'   => 'Backfill is_active=true on existing Qdrant KB chunks (one-time migration, admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // ── Admin: Token usage reports ─────────────────────────────────────────

    'local_edzaiaxisfront_get_token_usage_report' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_token_usage_report',
        'description'   => 'Get per-user token usage report for a month (admin only).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_user_daily_usage' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_user_daily_usage',
        'description'   => 'Get daily token usage breakdown for one user (admin only).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // ── Admin: Per-user limit overrides ────────────────────────────────────

    'local_edzaiaxisfront_save_user_override' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'save_user_override',
        'description'   => 'Save or update a per-user token/limit override (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_delete_user_override' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'delete_user_override',
        'description'   => 'Remove a per-user token/limit override (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_list_user_overrides' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'list_user_overrides',
        'description'   => 'List all per-user token/limit overrides (admin only).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_sync_tenant_settings' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'sync_tenant_settings',
        'description'   => 'Push current Moodle rate-limit settings to axis-ai tenant (admin only).',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_search_users' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'search_users',
        'description'   => 'Search Moodle users by name/username/email for the override user-picker (admin only).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // ── Chatbot panel (all logged-in users) ───────────────────────────────────

    'local_edzaiaxisfront_get_chatbot_init_data' => [
        'classname'     => 'local_edzaiaxisfront\external\chatbot_api',
        'methodname'    => 'get_chatbot_init_data',
        'description'   => 'Get welcome panel init data: user stats, mode availability.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_enrolled_courses' => [
        'classname'     => 'local_edzaiaxisfront\external\chatbot_api',
        'methodname'    => 'get_enrolled_courses',
        'description'   => 'Get enrolled courses with AI readiness flags for chatbot Study mode.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_user_analysis' => [
        'classname'     => 'local_edzaiaxisfront\external\chatbot_api',
        'methodname'    => 'get_user_analysis',
        'description'   => 'Get full learning analytics dashboard data for My Analysis panel.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_upcoming_events' => [
        'classname'     => 'local_edzaiaxisfront\external\chatbot_api',
        'methodname'    => 'get_upcoming_events',
        'description'   => 'Get upcoming calendar events for the current user.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_chat_threads' => [
        'classname'     => 'local_edzaiaxisfront\external\chatbot_api',
        'methodname'    => 'get_chat_threads',
        'description'   => 'Get recent chat sessions for the current user.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_analysis_dashboard' => [
        'classname'     => 'local_edzaiaxisfront\external\chatbot_api',
        'methodname'    => 'get_analysis_dashboard',
        'description'   => 'Get the full My Analysis dashboard data in a single call.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_course_ai_cms' => [
        'classname'     => 'local_edzaiaxisfront\external\chatbot_api',
        'methodname'    => 'get_course_ai_cms',
        'description'   => 'Get AI-ready course modules for a specific course (Study mode).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // ── Thread history & naming ────────────────────────────────────────────────

    'local_edzaiaxisfront_get_session_messages' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_session_messages',
        'description'   => 'Get stored messages for a chat thread.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_rename_thread' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'rename_thread',
        'description'   => 'Rename a chat session thread.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // ── Right panel context feeds ──────────────────────────────────────────────

    'local_edzaiaxisfront_get_support_right_panel' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_support_right_panel',
        'description'   => 'Get FAQ stream and support stats for the support right panel.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_study_activity_stream' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_study_activity_stream',
        'description'   => 'Get user study activity stream for the study right panel.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_get_token_usage_summary' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_token_usage_summary',
        'description'   => 'Get token usage summary for the analysis right panel.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // ── Teacher content editing ────────────────────────────────────────────────

    'local_edzaiaxisfront_get_teacher_cm_outputs' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_teacher_cm_outputs',
        'description'   => 'Get all generated AI outputs for a CM for teacher editing (ignores visibility).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_save_glossary_edit' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'save_glossary_edit',
        'description'   => 'Save teacher-edited glossary terms for a CM.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_save_flashcards_edit' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'save_flashcards_edit',
        'description'   => 'Save teacher-edited flashcards for a CM.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'local_edzaiaxisfront_save_quiz_edit' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'save_quiz_edit',
        'description'   => 'Save teacher-edited quiz questions for a CM.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'local_edzaiaxisfront_save_faq_edit' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'save_faq_edit',
        'description'   => 'Save teacher-edited FAQ items for a CM.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'local_edzaiaxisfront_get_course_ai_cmids' => [
        'classname'     => 'local_edzaiaxisfront\external\api',
        'methodname'    => 'get_course_ai_cmids',
        'description'   => 'List cmids in a course that have learner-visible AI content (for course-page badges).',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];

$services = [
    'Edz AI Axis Front Services' => [
        'functions'       => array_keys($functions),
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled'         => 1,
        'shortname'       => 'local_edzaiaxisfront',
        // Let a token issued for THIS service fetch files via webservice/pluginfile.php.
        // axis-ai downloads Moodle resource files (PDF/video) using the apitoken; that
        // download path is gated by this flag, NOT by any external function. So no
        // extra functions are needed for file access — just this flag + an admin token.
        'downloadfiles'   => 1,
        'uploadfiles'     => 0,
    ],
];
