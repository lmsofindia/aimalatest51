<?php

/**
 * External web service functions — Chatbot panel.
 *
 * Functions:
 *   get_chatbot_init_data   — enrolled courses + quick analytics for welcome panel
 *   get_enrolled_courses    — left-panel course list (Study mode)
 *   get_user_analysis       — My Analysis dashboard data (all from Moodle DB)
 *   get_upcoming_events     — calendar events (My Analysis left panel)
 *   get_chat_threads        — recent chat sessions grouped by mode
 *   get_course_ai_cms       — CMs with AI content ready for a specific course
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront\external;

use local_edzaiaxisfront\chatbot\user_analytics;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

class chatbot_api extends \external_api
{

    // =========================================================================
    //  get_chatbot_init_data
    // =========================================================================

    public static function get_chatbot_init_data_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    /**
     * Returns everything needed to render the welcome panel and first-load state:
     *  - user greeting
     *  - quick stats (courses, streak, badges)
     *  - whether chat modes are enabled at site level
     */
    public static function get_chatbot_init_data(): array
    {
        global $USER, $DB;

        self::validate_context(\context_system::instance());

        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('noguest');
        }

        $analytics = new user_analytics($USER->id);
        $courses   = enrol_get_users_courses($USER->id, true, ['id']);
        $points    = $analytics->get_points_public();
        $streak    = $analytics->get_streak_public();
        $badges    = $DB->count_records('badge_issued', ['userid' => $USER->id]);

        $site_chatbot  = (bool) get_config('local_edzaiaxisfront', 'feature_chatbot');
        $site_kb_chat  = (bool) get_config('local_edzaiaxisfront', 'feature_kb_chat');

        // Check if any course has AI-ready content
        $has_ai_courses = $DB->record_exists_select(
            'local_edzaiaxisfront_cm_config',
            "status = 'ready'"
        );

        return [
            'firstname'         => $USER->firstname,
            'course_count'      => count($courses),
            'badge_count'       => $badges,
            'points'            => $points['points'],
            'points_label'      => $points['label'],
            'streak_days'       => $streak,
            'study_enabled'     => $site_chatbot && $has_ai_courses,
            'support_enabled'   => $site_kb_chat,
            'analysis_enabled'  => true,
        ];
    }

    public static function get_chatbot_init_data_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'firstname'       => new \external_value(PARAM_TEXT),
            'course_count'    => new \external_value(PARAM_INT),
            'badge_count'     => new \external_value(PARAM_INT),
            'points'          => new \external_value(PARAM_INT),
            'points_label'    => new \external_value(PARAM_TEXT),
            'streak_days'     => new \external_value(PARAM_INT),
            'study_enabled'   => new \external_value(PARAM_BOOL),
            'support_enabled' => new \external_value(PARAM_BOOL),
            'analysis_enabled' => new \external_value(PARAM_BOOL),
        ]);
    }

    // =========================================================================
    //  get_enrolled_courses
    // =========================================================================

    public static function get_enrolled_courses_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    public static function get_enrolled_courses(): array
    {
        global $USER;

        self::validate_context(\context_system::instance());
        if (!isloggedin() || isguestuser()) throw new \moodle_exception('noguest');

        $analytics = new user_analytics($USER->id);
        $courses   = $analytics->get_enrolled_courses_for_chat();

        return $courses;
    }

    public static function get_enrolled_courses_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id'        => new \external_value(PARAM_INT),
                'fullname'  => new \external_value(PARAM_TEXT),
                'shortname' => new \external_value(PARAM_TEXT),
                'has_ai'    => new \external_value(PARAM_BOOL),
                'completed' => new \external_value(PARAM_BOOL),
                'progress'  => new \external_value(PARAM_INT),
            ])
        );
    }

    // =========================================================================
    //  get_user_analysis
    // =========================================================================

    public static function get_user_analysis_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    public static function get_user_analysis(): array
    {
        global $USER;

        self::validate_context(\context_system::instance());
        if (!isloggedin() || isguestuser()) throw new \moodle_exception('noguest');

        $analytics = new user_analytics($USER->id);
        $data = $analytics->get_analysis();

        // Flatten for external API return type constraints
        $courses  = $data['courses'];
        $progress = $data['overall_progress'];
        $points   = $data['points'];

        return [
            'courses_total'     => (int) $courses['total'],
            'courses_completed' => (int) $courses['completed'],
            'courses_inprog'    => (int) $courses['in_progress'],
            'avg_grade'         => $courses['avg_grade'] !== null ? (float)$courses['avg_grade'] : -1,
            'badge_count'       => count($data['certificates']),
            'badges_json'       => json_encode($data['certificates']),
            'points'            => (int) $points['points'],
            'points_label'      => $points['label'],
            'points_level'      => (int) $points['level'],
            'progress_pct'      => (int) $progress['pct'],
            'progress_completed' => (int) $progress['completed'],
            'progress_total'    => (int) $progress['total'],
            'streak_days'       => (int) $data['streak_days'],
        ];
    }

    public static function get_user_analysis_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'courses_total'      => new \external_value(PARAM_INT),
            'courses_completed'  => new \external_value(PARAM_INT),
            'courses_inprog'     => new \external_value(PARAM_INT),
            'avg_grade'          => new \external_value(PARAM_FLOAT),
            'badge_count'        => new \external_value(PARAM_INT),
            'badges_json'        => new \external_value(PARAM_RAW),
            'points'             => new \external_value(PARAM_INT),
            'points_label'       => new \external_value(PARAM_TEXT),
            'points_level'       => new \external_value(PARAM_INT),
            'progress_pct'       => new \external_value(PARAM_INT),
            'progress_completed' => new \external_value(PARAM_INT),
            'progress_total'     => new \external_value(PARAM_INT),
            'streak_days'        => new \external_value(PARAM_INT),
        ]);
    }

    // =========================================================================
    //  get_upcoming_events
    // =========================================================================

    public static function get_upcoming_events_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'limit' => new \external_value(PARAM_INT, 'Max events to return', VALUE_DEFAULT, 8),
        ]);
    }

    public static function get_upcoming_events(int $limit = 8): array
    {
        global $USER;

        ['limit' => $limit] = self::validate_parameters(
            self::get_upcoming_events_parameters(),
            compact('limit')
        );

        self::validate_context(\context_system::instance());
        if (!isloggedin() || isguestuser()) throw new \moodle_exception('noguest');

        $analytics = new user_analytics($USER->id);
        return $analytics->get_upcoming_events($limit);
    }

    public static function get_upcoming_events_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id'         => new \external_value(PARAM_INT),
                'name'       => new \external_value(PARAM_TEXT),
                'eventtype'  => new \external_value(PARAM_TEXT),
                'timestart'  => new \external_value(PARAM_INT),
                'date_label' => new \external_value(PARAM_TEXT),
                'time_label' => new \external_value(PARAM_TEXT),
                'coursename' => new \external_value(PARAM_TEXT),
                'days_away'  => new \external_value(PARAM_INT),
            ])
        );
    }

    // =========================================================================
    //  get_chat_threads
    // =========================================================================

    public static function get_chat_threads_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'mode'  => new \external_value(PARAM_ALPHA, 'study|support|all', VALUE_DEFAULT, 'all'),
            'limit' => new \external_value(PARAM_INT, '', VALUE_DEFAULT, 20),
        ]);
    }

    public static function get_chat_threads(string $mode = 'all', int $limit = 20): array
    {
        global $USER;

        ['mode' => $mode, 'limit' => $limit] = self::validate_parameters(
            self::get_chat_threads_parameters(),
            compact('mode', 'limit')
        );

        self::validate_context(\context_system::instance());
        if (!isloggedin() || isguestuser()) throw new \moodle_exception('noguest');

        $analytics = new user_analytics($USER->id);
        $threads   = $analytics->get_recent_chat_threads($limit);

        if ($mode !== 'all') {
            $threads = array_values(array_filter($threads, fn($t) => $t['chat_mode'] === $mode));
        }

        return $threads;
    }

    public static function get_chat_threads_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id'              => new \external_value(PARAM_INT),
                'cmid'            => new \external_value(PARAM_INT),
                'chat_mode'       => new \external_value(PARAM_ALPHA),
                'cm_name'         => new \external_value(PARAM_TEXT),
                'coursename'      => new \external_value(PARAM_TEXT),
                'tokens_used'     => new \external_value(PARAM_INT),
                'message_count'   => new \external_value(PARAM_INT,   '', VALUE_OPTIONAL),
                'axis_session_id' => new \external_value(PARAM_TEXT,  '', VALUE_OPTIONAL),
                'date_label'      => new \external_value(PARAM_TEXT),
            ])
        );
    }

    // =========================================================================
    //  get_analysis_dashboard  (single-call rich dashboard data)
    // =========================================================================

    public static function get_analysis_dashboard_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([]);
    }

    /**
     * Returns everything needed for the redesigned My Analysis dashboard
     * in a single round-trip: hero stats, per-course details, due items,
     * chat/AI intelligence, adaptive recommendations, and upcoming events.
     */
    public static function get_analysis_dashboard(): array
    {
        global $USER;

        self::validate_context(\context_system::instance());
        if (!isloggedin() || isguestuser()) throw new \moodle_exception('noguest');

        $analytics = new user_analytics($USER->id);
        return $analytics->get_dashboard_data();
    }

    public static function get_analysis_dashboard_returns(): \external_single_structure
    {
        return new \external_single_structure([
            'courses_total'        => new \external_value(PARAM_INT),
            'courses_completed'    => new \external_value(PARAM_INT),
            'courses_inprog'       => new \external_value(PARAM_INT),
            'avg_grade'            => new \external_value(PARAM_FLOAT),
            'courses_json'         => new \external_value(PARAM_RAW),
            'badge_count'          => new \external_value(PARAM_INT),
            'badges_json'          => new \external_value(PARAM_RAW),
            'cert_count'           => new \external_value(PARAM_INT),
            'certs_json'           => new \external_value(PARAM_RAW),
            'points'               => new \external_value(PARAM_INT),
            'points_label'         => new \external_value(PARAM_TEXT),
            'points_level'         => new \external_value(PARAM_INT),
            'progress_pct'         => new \external_value(PARAM_INT),
            'progress_completed'   => new \external_value(PARAM_INT),
            'progress_total'       => new \external_value(PARAM_INT),
            'streak_days'          => new \external_value(PARAM_INT),
            'due_soon_json'        => new \external_value(PARAM_RAW),
            'chat_stats_json'      => new \external_value(PARAM_RAW),
            'recommendations_json' => new \external_value(PARAM_RAW),
            'upcoming_events_json' => new \external_value(PARAM_RAW),
        ]);
    }

    // =========================================================================
    //  get_course_ai_cms
    // =========================================================================

    public static function get_course_ai_cms_parameters(): \external_function_parameters
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Returns AI-ready CMs for a specific course — used when user clicks a course
     * in Study-with-AI left panel to pick which activity to chat about.
     */
    public static function get_course_ai_cms(int $courseid): array
    {
        global $DB, $USER;

        ['courseid' => $courseid] = self::validate_parameters(
            self::get_course_ai_cms_parameters(),
            compact('courseid')
        );

        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/edzaiaxisfront:viewstudent', $context);

        $sql = "SELECT cm.id AS cmid, cm.module, m.name AS modname, cm.instance,
                       cfg.axis_content_item_id, cfg.generated_features, cfg.enabled_features,
                       cfg.visible_features
                FROM {local_edzaiaxisfront_cm_config} cfg
                JOIN {course_modules} cm ON cm.id = cfg.cmid
                JOIN {modules} m ON m.id = cm.module
                WHERE cfg.courseid = :courseid
                  AND cfg.status = 'ready'
                  AND cm.visible = 1";

        $rows = $DB->get_records_sql($sql, ['courseid' => $courseid]);
        $items = [];

        foreach ($rows as $row) {
            // Get the instance name dynamically
            $name = $DB->get_field($row->modname, 'name', ['id' => $row->instance]) ?: 'Activity ' . $row->cmid;

            $site_features    = self::get_site_features_static();
            $enabled          = json_decode($row->enabled_features  ?? '[]', true) ?: [];
            $visible          = json_decode($row->visible_features   ?? '[]', true) ?: [];
            $generated        = json_decode($row->generated_features ?? '[]', true) ?: [];
            $available        = array_values(array_intersect($site_features, $enabled, $visible, $generated));

            // Only surface activities whose AI generation actually produced content and
            // that have an ingested content item — otherwise the chatbot has nothing to
            // answer from (status can be 'ready' even when generation produced nothing).
            if (empty($generated) || empty($row->axis_content_item_id)) {
                continue;
            }

            $items[] = [
                'cmid'                => (int) $row->cmid,
                'name'                => $name,
                'modname'             => $row->modname,
                'content_item_id'     => $row->axis_content_item_id ?? '',
                'available_features'  => implode(',', $available),
            ];
        }

        return $items;
    }

    public static function get_course_ai_cms_returns(): \external_multiple_structure
    {
        return new \external_multiple_structure(
            new \external_single_structure([
                'cmid'               => new \external_value(PARAM_INT),
                'name'               => new \external_value(PARAM_TEXT),
                'modname'            => new \external_value(PARAM_ALPHA),
                'content_item_id'    => new \external_value(PARAM_TEXT),
                'available_features' => new \external_value(PARAM_TEXT),
            ])
        );
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private static function get_site_features_static(): array
    {
        $out = [];
        foreach (['summary', 'glossary', 'flashcards', 'quiz', 'faq', 'infographic', 'chatbot', 'kb_chat'] as $f) {
            if (get_config('local_edzaiaxisfront', "feature_{$f}")) $out[] = $f;
        }
        return $out;
    }
}
