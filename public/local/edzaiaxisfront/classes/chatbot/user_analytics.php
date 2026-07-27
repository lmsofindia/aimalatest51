<?php
/**
 * User Analytics — pulls learning data entirely from Moodle's own DB.
 *
 * No calls to axis-ai for this data.  Aggregates:
 *  - Enrolled courses + completion status
 *  - Grade summary per course
 *  - Badges / certificates issued
 *  - XP / activity points (block_xp if installed, else activity-based estimate)
 *  - Upcoming calendar events
 *  - Recent chat threads (from local_edzaiaxisfront_chat_sessions)
 *  - Course activity completion stats
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzaiaxisfront\chatbot;

defined('MOODLE_INTERNAL') || die();

class user_analytics {

    private int $userid;

    public function __construct(int $userid) {
        $this->userid = $userid;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /** Thin public wrapper — for chatbot_api init data call. */
    public function get_points_public(): array { return $this->get_points(); }
    public function get_streak_public(): int   { return $this->get_login_streak(); }

    /**
     * Full dashboard payload for "My Analysis" panel.
     */
    public function get_analysis(): array {
        return [
            'courses'           => $this->get_enrolled_courses_summary(),
            'certificates'      => $this->get_certificates(),
            'points'            => $this->get_points(),
            'overall_progress'  => $this->get_overall_progress(),
            'upcoming_events'   => $this->get_upcoming_events(5),
            'recent_threads'    => $this->get_recent_chat_threads(5),
            'streak_days'       => $this->get_login_streak(),
        ];
    }

    /**
     * Enrolled courses for the Study with AI left panel.
     * Includes AI readiness flag.
     */
    public function get_enrolled_courses_for_chat(): array {
        global $DB;

        $courses = enrol_get_users_courses($this->userid, true, ['id', 'fullname', 'shortname', 'summary'], 'fullname ASC');
        $results = [];

        foreach ($courses as $course) {
            // Check if any CM in this course has AI content ready
            // A course is "AI-ready" only if at least one activity actually generated
            // content (status can be 'ready' with empty generated_features). Mirror
            // get_course_ai_cms so we never show a course whose activity list is empty.
            $has_ai = false;
            $cfgs = $DB->get_records_select(
                'local_edzaiaxisfront_cm_config',
                "courseid = ? AND status = 'ready'",
                [$course->id],
                '',
                'id, generated_features, axis_content_item_id'
            );
            foreach ($cfgs as $cfg) {
                $gen = json_decode($cfg->generated_features ?? '[]', true) ?: [];
                if (!empty($gen) && !empty($cfg->axis_content_item_id)) {
                    $has_ai = true;
                    break;
                }
            }

            // Hide courses with no usable AI content from the Study course list.
            if (!$has_ai) {
                continue;
            }

            // Completion
            $completion = $DB->get_record('course_completions', [
                'userid'   => $this->userid,
                'course'   => $course->id,
            ]);

            $results[] = [
                'id'         => (int) $course->id,
                'fullname'   => $course->fullname,
                'shortname'  => $course->shortname,
                'has_ai'     => (bool) $has_ai,
                'completed'  => !empty($completion->timecompleted),
                'progress'   => $this->course_completion_pct($course->id),
            ];
        }

        return $results;
    }

    /**
     * Upcoming events for the user (calendar).
     */
    public function get_upcoming_events(int $limit = 10): array {
        global $DB;

        $now    = time();
        $future = $now + (30 * DAYSECS);  // next 30 days

        $sql = "SELECT e.id, e.name, e.eventtype, e.timestart, e.timeduration,
                       e.courseid, c.fullname AS coursename
                FROM {event} e
                LEFT JOIN {course} c ON c.id = e.courseid
                WHERE e.userid = :userid
                  AND e.timestart >= :now
                  AND e.timestart <= :future
                  AND e.visible = 1
                ORDER BY e.timestart ASC";

        $rows = $DB->get_records_sql($sql, [
            'userid'  => $this->userid,
            'now'     => $now,
            'future'  => $future,
        ], 0, $limit);

        $events = [];
        foreach ($rows as $row) {
            $events[] = [
                'id'         => (int) $row->id,
                'name'       => $row->name,
                'eventtype'  => $row->eventtype,
                'timestart'  => (int) $row->timestart,
                'date_label' => userdate($row->timestart, get_string('strftimedate', 'langconfig')),
                'time_label' => userdate($row->timestart, get_string('strftimetime', 'langconfig')),
                'coursename' => $row->coursename ?? '',
                'days_away'  => (int) ceil(($row->timestart - $now) / DAYSECS),
            ];
        }
        return $events;
    }

    /**
     * Recent chat threads from plugin sessions.
     */
    public function get_recent_chat_threads(int $limit = 10): array {
        global $DB;

        // course_modules has no name column — fetch base session data first,
        // then resolve the CM name from the module's own instance table.
        $sql = "SELECT cs.id, cs.cmid, cs.chat_mode, cs.tokens_used, cs.timecreated,
                       cs.message_count, cs.axis_session_id,
                       COALESCE(cs.thread_name, '') AS thread_name,
                       cm.module, cm.instance, cm.course AS courseid
                FROM {local_edzaiaxisfront_chat_sessions} cs
                LEFT JOIN {course_modules} cm ON cm.id = cs.cmid
                WHERE cs.userid = :userid
                ORDER BY cs.timecreated DESC";

        $rows = $DB->get_records_sql($sql, ['userid' => $this->userid], 0, $limit);
        $threads = [];
        foreach ($rows as $row) {
            // Support sessions have cmid = 0 — no course module to resolve.
            if (empty($row->cmid) || (int) $row->cmid === 0) {
                $cm_name = 'Support Chat';
            } else {
                // Resolve CM name via module instance table.
                $cm_name = "Content #{$row->cmid}";
                if (!empty($row->module) && !empty($row->instance)) {
                    $modname = $DB->get_field('modules', 'name', ['id' => $row->module]);
                    if ($modname && $DB->get_manager()->table_exists($modname)) {
                        $cm_name = $DB->get_field($modname, 'name', ['id' => $row->instance]) ?: $cm_name;
                    }
                }
            }
            // Resolve course name.
            $coursename = !empty($row->courseid)
                ? ($DB->get_field('course', 'fullname', ['id' => $row->courseid]) ?: '')
                : '';
            // Prefer user-set or auto thread_name over resolved CM name
            $display_name = !empty($row->thread_name) ? $row->thread_name : $cm_name;
            $threads[] = [
                'id'              => (int) $row->id,
                'cmid'            => (int) $row->cmid,
                'chat_mode'       => $row->chat_mode,
                'cm_name'         => $display_name,
                'coursename'      => $coursename,
                'tokens_used'     => (int) $row->tokens_used,
                'message_count'   => (int) ($row->message_count ?? 0),
                'axis_session_id' => $row->axis_session_id ?? '',
                'date_label'      => userdate($row->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
            ];
        }
        return $threads;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function get_enrolled_courses_summary(): array {
        global $DB;

        $courses  = enrol_get_users_courses($this->userid, true, ['id', 'fullname', 'shortname'], 'fullname ASC');
        $total    = count($courses);
        $complete = 0;
        $in_prog  = 0;
        $grades   = [];

        foreach ($courses as $course) {
            $comp = $DB->get_record('course_completions', ['userid' => $this->userid, 'course' => $course->id]);
            if (!empty($comp->timecompleted)) {
                $complete++;
            } else {
                $in_prog++;
            }

            // Final grade for this course
            $grade_item = $DB->get_record('grade_items', [
                'courseid'  => $course->id,
                'itemtype'  => 'course',
            ]);
            if ($grade_item) {
                $grade = $DB->get_record('grade_grades', [
                    'itemid' => $grade_item->id,
                    'userid' => $this->userid,
                ]);
                if ($grade && $grade->finalgrade !== null) {
                    $grades[] = (float) $grade->finalgrade;
                }
            }
        }

        $avg_grade = $grades ? round(array_sum($grades) / count($grades), 1) : null;

        return [
            'total'     => $total,
            'completed' => $complete,
            'in_progress' => $in_prog,
            'avg_grade' => $avg_grade,
        ];
    }

    private function get_certificates(): array {
        global $DB;

        // Standard Moodle badges.
        // The badge image is NOT stored as a column — it lives in Moodle's file API.
        // We fetch type + courseid so we can construct the correct context for the URL.
        $badges = $DB->get_records_sql(
            "SELECT bi.id, bi.badgeid, bi.dateissued,
                    b.name, b.description, b.type, b.courseid
             FROM {badge_issued} bi
             JOIN {badge} b ON b.id = bi.badgeid
             WHERE bi.userid = ?
             ORDER BY bi.dateissued DESC",
            [$this->userid]
        );

        $results = [];
        foreach ($badges as $b) {
            // Resolve the correct context for the badge image pluginfile URL.
            // BADGE_TYPE_SITE = 1, BADGE_TYPE_COURSE = 2.
            if ((int) $b->type === 2 && !empty($b->courseid)) {
                $ctx = \context_course::instance((int) $b->courseid, IGNORE_MISSING);
            } else {
                $ctx = \context_system::instance();
            }
            $image_url = (string) \moodle_url::make_pluginfile_url(
                $ctx->id, 'badges', 'badgeimage', (int) $b->badgeid, '/', 'f1'
            );

            $results[] = [
                'id'          => (int) $b->id,
                'name'        => $b->name,
                'description' => $b->description,
                'dateissued'  => (int) $b->dateissued,
                'date_label'  => userdate($b->dateissued, get_string('strftimedate', 'langconfig')),
                'image_url'   => $image_url,
            ];
        }
        return $results;
    }

    private function get_points(): array {
        global $DB;

        // block_xp support (common gamification plugin)
        if ($DB->get_manager()->table_exists('block_xp')) {
            $xp = $DB->get_field('block_xp', 'xp', ['userid' => $this->userid]);
            if ($xp !== false) {
                $level = $DB->get_field('block_xp', 'lvl', ['userid' => $this->userid]);
                return [
                    'source'    => 'xp',
                    'points'    => (int) $xp,
                    'level'     => (int) ($level ?: 1),
                    'label'     => 'XP Points',
                ];
            }
        }

        // Fallback: count completed activities as points
        $completed = $DB->count_records_select(
            'course_modules_completion',
            'userid = ? AND completionstate = 1',
            [$this->userid]
        );

        return [
            'source' => 'activities',
            'points' => $completed * 10,   // 10 pts per completed activity
            'level'  => max(1, (int) floor($completed / 5) + 1),
            'label'  => 'Activity Points',
        ];
    }

    private function get_overall_progress(): array {
        global $DB;

        $courses = enrol_get_users_courses($this->userid, true, ['id']);
        if (!$courses) return ['pct' => 0, 'completed' => 0, 'total' => 0];

        $total_activities = 0;
        $completed_activities = 0;

        foreach ($courses as $course) {
            $cms = $DB->get_records('course_modules', ['course' => $course->id, 'visible' => 1, 'completion' => 1]);
            $total_activities += count($cms);
            foreach ($cms as $cm) {
                $comp = $DB->get_record('course_modules_completion', [
                    'coursemoduleid' => $cm->id,
                    'userid'         => $this->userid,
                    'completionstate'=> 1,
                ]);
                if ($comp) $completed_activities++;
            }
        }

        $pct = $total_activities > 0
            ? (int) round(($completed_activities / $total_activities) * 100)
            : 0;

        return [
            'pct'       => $pct,
            'completed' => $completed_activities,
            'total'     => $total_activities,
        ];
    }

    private function course_completion_pct(int $courseid): int {
        global $DB;

        $total = $DB->count_records('course_modules', ['course' => $courseid, 'visible' => 1, 'completion' => 1]);
        if (!$total) return 0;

        $done = $DB->count_records('course_modules_completion', [
            'userid'          => $this->userid,
            'completionstate' => 1,
        ]);

        // Filter only CMs for this course
        $done = (int) $DB->get_field_sql(
            "SELECT COUNT(*) FROM {course_modules_completion} cmc
             JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
             WHERE cmc.userid = ? AND cm.course = ? AND cmc.completionstate = 1",
            [$this->userid, $courseid]
        );

        return (int) round(($done / $total) * 100);
    }

    // =========================================================================
    //  NEW: Rich dashboard data for the redesigned My Analysis panel
    // =========================================================================

    /**
     * Full enterprise dashboard payload — single call replaces the old
     * three-call approach (getUserAnalysis + getUpcomingEvents + getChatThreads).
     */
    public function get_dashboard_data(): array {
        $courses_detail = $this->get_course_detail_list();
        $chat_stats     = $this->get_chat_stats();
        $due_items      = $this->get_due_items();
        $recommendations= $this->get_recommendations($courses_detail, $due_items, $chat_stats);
        $points         = $this->get_points();
        $progress       = $this->get_overall_progress();
        $badges         = $this->get_certificates();
        $certs          = $this->get_certificates_from_plugin();
        $events         = $this->get_upcoming_events(8);

        $total     = count($courses_detail);
        $completed = count(array_filter($courses_detail, fn($c) => $c['completed']));
        $in_prog   = $total - $completed;
        $raw_grades = array_filter(array_column($courses_detail, 'grade'), fn($g) => $g !== null);
        $avg_grade  = $raw_grades
            ? round(array_sum($raw_grades) / count($raw_grades), 1)
            : -1;

        return [
            'courses_total'        => $total,
            'courses_completed'    => $completed,
            'courses_inprog'       => $in_prog,
            'avg_grade'            => (float) $avg_grade,
            'courses_json'         => json_encode(array_values($courses_detail)),
            'badge_count'          => count($badges),
            'badges_json'          => json_encode($badges),
            'cert_count'           => count($certs),
            'certs_json'           => json_encode($certs),
            'points'               => (int) $points['points'],
            'points_label'         => $points['label'],
            'points_level'         => (int) $points['level'],
            'progress_pct'         => (int) $progress['pct'],
            'progress_completed'   => (int) $progress['completed'],
            'progress_total'       => (int) $progress['total'],
            'streak_days'          => (int) $this->get_login_streak(),
            'due_soon_json'        => json_encode($due_items),
            'chat_stats_json'      => json_encode($chat_stats),
            'recommendations_json' => json_encode($recommendations),
            'upcoming_events_json' => json_encode($events),
        ];
    }

    // ── Private helpers (new) ─────────────────────────────────────────────────

    /**
     * Per-course detail list with grade and progress.
     */
    private function get_course_detail_list(): array {
        global $DB;
        $courses = enrol_get_users_courses($this->userid, true, ['id', 'fullname', 'shortname']);
        $list    = [];
        foreach ($courses as $course) {
            $comp     = $DB->get_record('course_completions', ['userid' => $this->userid, 'course' => $course->id]);
            $progress = $this->course_completion_pct($course->id);

            // Normalised grade as percentage
            $grade = null;
            $gi    = $DB->get_record('grade_items', ['courseid' => $course->id, 'itemtype' => 'course']);
            if ($gi && $gi->grademax > 0) {
                $gg = $DB->get_record('grade_grades', ['itemid' => $gi->id, 'userid' => $this->userid]);
                if ($gg && $gg->finalgrade !== null) {
                    $grade = round(($gg->finalgrade / $gi->grademax) * 100, 1);
                }
            }

            $list[] = [
                'id'        => (int) $course->id,
                'fullname'  => $course->fullname,
                'shortname' => $course->shortname,
                'completed' => !empty($comp->timecompleted),
                'progress'  => $progress,
                'grade'     => $grade,
            ];
        }
        return $list;
    }

    /**
     * Assignments and quizzes due within the next 14 days, not yet submitted.
     */
    private function get_due_items(): array {
        global $DB;
        $now     = time();
        $horizon = $now + (14 * DAYSECS);
        $items   = [];

        foreach (enrol_get_users_courses($this->userid, true, ['id']) as $course) {
            $cname = $DB->get_field('course', 'fullname', ['id' => $course->id]);

            // Assignments
            if ($DB->get_manager()->table_exists('assign')) {
                $assigns = $DB->get_records_select('assign',
                    "course = ? AND duedate > ? AND duedate <= ?",
                    [$course->id, $now, $horizon]
                );
                foreach ($assigns as $a) {
                    $submitted = $DB->record_exists('assign_submission', [
                        'assignment' => $a->id, 'userid' => $this->userid, 'status' => 'submitted',
                    ]);
                    if (!$submitted) {
                        $cm = get_coursemodule_from_instance('assign', $a->id, $course->id);
                        $items[] = [
                            'type'       => 'assignment',
                            'name'       => $a->name,
                            'coursename' => $cname,
                            'duedate'    => (int) $a->duedate,
                            'days_left'  => max(0, (int) ceil(($a->duedate - $now) / DAYSECS)),
                            'cmid'       => $cm ? (int) $cm->id : 0,
                        ];
                    }
                }
            }

            // Quizzes
            if ($DB->get_manager()->table_exists('quiz')) {
                $quizzes = $DB->get_records_select('quiz',
                    "course = ? AND timeclose > ? AND timeclose <= ?",
                    [$course->id, $now, $horizon]
                );
                foreach ($quizzes as $q) {
                    $finished = $DB->record_exists('quiz_attempts', [
                        'quiz' => $q->id, 'userid' => $this->userid, 'state' => 'finished',
                    ]);
                    if (!$finished) {
                        $cm = get_coursemodule_from_instance('quiz', $q->id, $course->id);
                        $items[] = [
                            'type'       => 'quiz',
                            'name'       => $q->name,
                            'coursename' => $cname,
                            'duedate'    => (int) $q->timeclose,
                            'days_left'  => max(0, (int) ceil(($q->timeclose - $now) / DAYSECS)),
                            'cmid'       => $cm ? (int) $cm->id : 0,
                        ];
                    }
                }
            }
        }

        usort($items, fn($a, $b) => $a['duedate'] - $b['duedate']);
        return array_slice($items, 0, 8);
    }

    /**
     * Chat/AI session usage analytics from our local tables.
     */
    private function get_chat_stats(): array {
        global $DB;

        $total_sessions  = (int) $DB->count_records('local_edzaiaxisfront_chat_sessions', ['userid' => $this->userid]);
        $total_msgs      = (int) ($DB->get_field_sql(
            'SELECT COALESCE(SUM(message_count),0) FROM {local_edzaiaxisfront_chat_sessions} WHERE userid = ?',
            [$this->userid]
        ) ?: 0);
        $study_sessions  = (int) $DB->count_records('local_edzaiaxisfront_chat_sessions', ['userid' => $this->userid, 'chat_mode' => 'study']);
        $support_sessions= (int) $DB->count_records('local_edzaiaxisfront_chat_sessions', ['userid' => $this->userid, 'chat_mode' => 'support']);
        $active_days     = (int) ($DB->count_records_select(
            'local_edzaiaxisfront_token_usage',
            'userid = ? AND msg_count > 0',
            [$this->userid]
        ) ?: 0);

        // Weekly message counts from token_usage — last 8 weeks
        $weekly = [];
        for ($i = 7; $i >= 0; $i--) {
            $ws = strtotime("monday -{$i} weeks");
            $we = $ws + (7 * DAYSECS);
            $cnt = (int) ($DB->get_field_sql(
                "SELECT COALESCE(SUM(msg_count),0) FROM {local_edzaiaxisfront_token_usage}
                 WHERE userid = ? AND day_date >= ? AND day_date < ?",
                [$this->userid, date('Y-m-d', $ws), date('Y-m-d', $we)]
            ) ?: 0);
            $weekly[] = ['label' => date('M j', $ws), 'count' => $cnt];
        }

        // Top studied content items (by session count).
        // course_modules has no name column — resolve via module instance table.
        $sql = "SELECT cs.cmid, COUNT(*) AS cnt
                FROM {local_edzaiaxisfront_chat_sessions} cs
                WHERE cs.userid = ? AND cs.cmid IS NOT NULL AND cs.cmid > 0
                GROUP BY cs.cmid
                ORDER BY cnt DESC";
        $top_cms_raw = $DB->get_records_sql($sql, [$this->userid], 0, 5);
        $top_cms = [];
        foreach ($top_cms_raw as $r) {
            $cm = $DB->get_record('course_modules', ['id' => $r->cmid], 'id, module, instance', IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $modname = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            $cmname  = ($modname && $DB->get_manager()->table_exists($modname))
                ? ($DB->get_field($modname, 'name', ['id' => $cm->instance]) ?: "Content #{$r->cmid}")
                : "Content #{$r->cmid}";
            $top_cms[] = ['cmname' => $cmname, 'count' => (int) $r->cnt];
        }

        return [
            'total_sessions'   => $total_sessions,
            'total_messages'   => $total_msgs,
            'study_sessions'   => $study_sessions,
            'support_sessions' => $support_sessions,
            'active_days'      => $active_days,
            'weekly_msgs'      => $weekly,
            'top_cms'          => $top_cms,
        ];
    }

    /**
     * Adaptive learning recommendations ranked by priority.
     */
    private function get_recommendations(array $courses, array $due_items, array $chat_stats): array {
        $recs = [];

        // 1. Urgent due items (highest priority)
        foreach (array_slice($due_items, 0, 2) as $item) {
            $urgency   = $item['days_left'] <= 1 ? 'urgent' : ($item['days_left'] <= 3 ? 'high' : 'medium');
            $emoji     = $item['type'] === 'assignment' ? '📝' : '🎯';
            $days_text = $item['days_left'] == 0 ? 'due today'
                : "due in {$item['days_left']} day" . ($item['days_left'] != 1 ? 's' : '');
            $recs[] = [
                'type'        => 'due_soon',
                'priority'    => $urgency,
                'icon'        => $emoji,
                'title'       => ucfirst($item['type']) . ' — ' . $days_text,
                'description' => $item['name'] . ' · ' . $item['coursename'],
                'cmid'        => $item['cmid'],
            ];
        }

        // 2. Low-grade courses
        foreach ($courses as $c) {
            if (count($recs) >= 4) break;
            if ($c['grade'] !== null && $c['grade'] < 60 && !$c['completed']) {
                $recs[] = [
                    'type'        => 'improve_grade',
                    'priority'    => 'high',
                    'icon'        => '📈',
                    'title'       => 'Grade needs attention',
                    'description' => $c['fullname'] . ' — ' . $c['grade'] . '% · Chat with AI to improve',
                    'cmid'        => 0,
                ];
                break; // one at a time
            }
        }

        // 3. Low-progress courses
        foreach ($courses as $c) {
            if (count($recs) >= 4) break;
            if (!$c['completed'] && $c['progress'] >= 0 && $c['progress'] < 25) {
                $recs[] = [
                    'type'        => 'continue_learning',
                    'priority'    => 'medium',
                    'icon'        => '🚀',
                    'title'       => 'Continue your learning',
                    'description' => $c['fullname'] . ' — ' . $c['progress'] . '% complete, keep going!',
                    'cmid'        => 0,
                ];
                break;
            }
        }

        // 4. First-time AI nudge
        if ($chat_stats['total_sessions'] === 0) {
            $recs[] = [
                'type'        => 'start_chatting',
                'priority'    => 'low',
                'icon'        => '💬',
                'title'       => 'Try AI-powered study',
                'description' => 'Get summaries, flashcards & personalised explanations — just ask!',
                'cmid'        => 0,
            ];
        }

        // 5. Positive nudge if nothing critical
        if (empty($recs) || count(array_filter($recs, fn($r) => in_array($r['priority'], ['urgent','high']))) === 0) {
            $recs[] = [
                'type'        => 'celebrate',
                'priority'    => 'low',
                'icon'        => '🎉',
                'title'       => 'Excellent progress!',
                'description' => 'You\'re on top of everything. Keep exploring with AI assistance.',
                'cmid'        => 0,
            ];
        }

        return array_slice($recs, 0, 5);
    }

    /**
     * Certificates from the mod_customcert plugin (if installed).
     */
    private function get_certificates_from_plugin(): array {
        global $DB;
        $certs = [];
        if ($DB->get_manager()->table_exists('customcert_issues')) {
            $sql = "SELECT ci.id, cc.name, ci.timecreated
                    FROM {customcert_issues} ci
                    JOIN {customcert} cc ON cc.id = ci.customcertid
                    WHERE ci.userid = ?
                    ORDER BY ci.timecreated DESC";
            foreach ($DB->get_records_sql($sql, [$this->userid]) as $r) {
                $certs[] = [
                    'id'         => (int) $r->id,
                    'name'       => $r->name,
                    'dateissued' => (int) $r->timecreated,
                    'date_label' => userdate($r->timecreated, get_string('strftimedate', 'langconfig')),
                ];
            }
        }
        return $certs;
    }

    // =========================================================================
    //  (original login streak — kept as-is)
    // =========================================================================

    private function get_login_streak(): int {
        global $DB;

        // Count consecutive days with logins from logstore_standard_log
        if (!$DB->get_manager()->table_exists('logstore_standard_log')) return 0;

        $sql = "SELECT DISTINCT " . $DB->sql_substr('FROM_UNIXTIME(timecreated)', 1, 10) . " AS day
                FROM {logstore_standard_log}
                WHERE userid = ? AND action = 'loggedin'
                ORDER BY day DESC";

        try {
            $days = array_keys($DB->get_records_sql($sql, [$this->userid], 0, 30));
            if (!$days) return 0;

            $streak = 1;
            $today  = date('Y-m-d');
            if ($days[0] !== $today) return 0;

            for ($i = 1; $i < count($days); $i++) {
                $prev = date('Y-m-d', strtotime($days[$i - 1] . ' -1 day'));
                if ($days[$i] === $prev) {
                    $streak++;
                } else {
                    break;
                }
            }
            return $streak;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
