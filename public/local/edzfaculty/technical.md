# local_edzfaculty — Technical

- **Component:** `local_edzfaculty`
- **Moodle target:** 5.0+ (`$plugin->requires` = 2024100700 / adjust to site). PHP 8.1+.
- **Namespace root:** `local_edzfaculty\`

## Design stance
The dashboard is an **aggregation + surfacing** layer. Almost all data already lives in core Moodle; we read it and present it action-first. Only *derived* analytics (engagement rollups, at-risk scores) are precomputed into a cache table by a scheduled task — never on page load.

## DB schema (db/install.xml)

### `local_edzfaculty_cache`
Per (course, user) derived metrics. One row per enrolled student per teaching course.
| field | type | notes |
|---|---|---|
| id | int (10) PK AI | |
| courseid | int (10) | FK course, indexed |
| userid | int (10) | FK user (the student), indexed |
| attendancepct | number (5,2) | null if mod_attendance absent |
| avgscore | number (5,2) | raw best % from gradebook |
| scoretrend | number (6,2) | delta over last 3 graded items |
| lastaccess | int (10) | last course access (unix) |
| misseddeadlines | int (10) | overdue with no submission |
| forumposts | int (10) | posts this period |
| contentviews | int (10) | module views this period |
| riskscore | number (5,2) | 0–100 composite |
| risklevel | char (10) | ok / watch / critical |
| riskreasons | text | JSON array of reason strings |
| timemodified | int (10) | |
Unique key: (courseid, userid). Indexes: courseid, userid, risklevel.

### `local_edzfaculty_coursecache`
Per teaching course rollup (one row per course).
| field | type | notes |
|---|---|---|
| id | int PK AI | |
| courseid | int | unique |
| activestudentspct | number(5,2) | |
| contentviews | int | |
| assessmentstaken | int | |
| avgscore | number(5,2) | |
| activestudentsprev / avgscoreprev / ... | | previous period for trend |
| atriskcount | int | |
| contentdeliveredpct | number(5,2) | published vs total modules |
| timemodified | int | |

> `install.xml` should be regenerated with Moodle's XMLDB editor before shipping; the hand-written one here is structurally correct and installs, but XMLDB editor is the canonical source (Vidya lesson #5).

## Class map
```
classes/
  helper/dashboard_helper.php   # facade: getdata($teacherid) -> full dashboard model
  helper/courses.php            # teaching-course discovery (enrol + capability filter)
  helper/grading.php            # assign/quiz submissions awaiting grading, per course/section
  helper/discussions.php        # forum threads with unanswered student questions
  helper/timeline.php           # calendar events: live classes, exams, office hours
  helper/atrisk.php             # rule-based risk scoring (reads config thresholds)
  helper/engagement.php         # per-course engagement rollups (reads coursecache)
  helper/terminology.php        # academic|corporate label resolver
  external/get_course_focus.php # AJAX: stat cards + quick-action targets for a course
  external/get_engagement.php   # AJAX: engagement stats for a course scope
  external/refresh_cache.php    # AJAX: user-triggered cache rebuild (throttled 30 min)
  task/refresh_cache.php        # scheduled_task, 6h — rebuilds both cache tables site-wide
  output/renderer.php           # render_dashboard()
  output/renderable/dashboard.php # implements renderable, templatable -> export_for_template
  privacy/provider.php          # metadata + delete/export for cache tables
```

## Data sources (core tables)
- **Teaching courses:** `enrol_get_all_users_courses($teacherid, true)` filtered by `has_capability('mod/assign:grade', $coursectx, $teacherid)`.
- **Sections = groups:** `{groups}` / `{groups_members}` (Moodle groups model "sections/batches").
- **Grading (assign):** `{assign_submission}` status=submitted joined to `{assign_grades}` (null grade or grade older than submission).
- **Grading (quiz manual):** `{quiz_attempts}` + `{question_attempts}`/`{question_attempt_steps}` where state needs manual grade — Phase 2 for full precision; Phase 1 shows a count via `quiz_grades` gap.
- **Discussions:** `{forum_discussions}` + `{forum_posts}` — a thread whose last post is by a student and has no later teacher reply = "unanswered".
- **Grades:** `{grade_items}` + `{grade_grades}` — `finalgrade` raw, `rawgrademax` for %.
- **Attendance:** `{attendance_log}` + `{attendance_sessions}` if `mod_attendance` installed (feature-detect).
- **Engagement / last active:** `{logstore_standard_log}` — **batch only in the scheduled task**, never on page load (Vidya lesson #11).
- **Live classes:** `{event}` (calendar) + `mod_bigbluebuttonbn` / `mod_zoom` instances if present.

## At-risk scoring (helper/atrisk.php)
Composite 0–100 from weighted, config-thresholded signals:
`riskscore = w1*attendanceGap + w2*loginStaleness + w3*scoreDrop + w4*missedDeadlines + w5*lowScore`
Weights proportional (Vidya lesson #1); default 25/20/25/15/15. `risklevel`: ≥60 critical, ≥35 watch, else ok. `riskreasons` = human strings for the ones that tripped. All thresholds from settings.

## Caching (db/caches.php + task)
- Scheduled task `refresh_cache` every 6h rebuilds `local_edzfaculty_cache` + `local_edzfaculty_coursecache` for every teaching course.
- `refresh_cache` external endpoint lets a teacher rebuild *their* courses, throttled to once / 30 min via user preference `local_edzfaculty_lastrefresh`.
- MUC application cache `dashboard` keyed by teacherid for the assembled model (short TTL), invalidated on manual refresh.

## AJAX (db/services.php)
| methodname | args | returns |
|---|---|---|
| `local_edzfaculty_get_course_focus` | courseid (0=all) | live, assign, disc, quiz counts + quick-action URLs |
| `local_edzfaculty_get_engagement` | courseid (0=all) | 4 metrics + prev-period trend + ai insight string |
| `local_edzfaculty_refresh_cache` | — | {queued, throttleduntil} |
Each extends `core_external\external_api`, registered with capability `local/edzfaculty:view`, `ajax=true`, `loginrequired=true`. Frontend calls via `core/ajax`; charts via `core/chartjs`.

## Navigation (lib.php)
`local_edzfaculty_extend_navigation()` adds a "Faculty Dashboard" node (flat nav / primary) for users with `:view`. Optional admin setting to make it the teacher default landing page (redirect from `/my` when capability present and setting on).

## AMD
`amd/src/dashboard.js` — tab switching, course-focus selector (calls get_course_focus), engagement filter (get_engagement + chart repaint), student-360 modal (core/modal), manual refresh. Build sync: `grunt amd` then commit `amd/build/dashboard.min.js` (Vidya lesson #4).

## Build phases
Data+cache → Grading/Discussions engines → At-risk + engagement → Renderer/template/AMD → AI hooks (Phase 2). This repo = Phase 1 complete.
