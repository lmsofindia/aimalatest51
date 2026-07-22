# local_trackmytime — Functionality Specification

**Plugin:** `local_trackmytime` · **Type:** Local · **Target:** Moodle 4.5+ · **Status:** 1.0.0

## 1. Purpose
A **theme-independent** time-on-task tracker with a learner Performance page. Standalone fork of `local_gemui_timetracker`, which only worked under the GemUI theme because the theme injected its heartbeat. Here the plugin **injects its own heartbeat** (via a core hook), so tracking works on stock Boost or any theme. The original is left untouched for the GemUI product.

## 2. Features
### F1 · Time tracking (heartbeat)
- On every course-module **view** page (`mod-*-view`), a lightweight AMD heartbeat records seconds-on-task to the `{trackmytime}` table.
- Pauses on tab-hidden (visibility API), flushes on unload via `navigator.sendBeacon`, caps a session at 1 hour, ignores <5s.
- Injected by the plugin's own `before_http_headers` hook — **no theme dependency**. Gated by the `enabletracking` setting (default ON).

### F2 · Performance & Achievements page
- `/local/trackmytime/pages/performance.php` — fully **server-rendered** (works without JS):
  - 26-week study heatmap (from `{trackmytime}`), with hover tooltips.
  - Grade overview (core gradebook — `grade_item`/`grade_grade`).
  - Badges earned (core `{badge_issued}`).
- Users see their own page; admins (with `moodle/user:viewdetails`) can view others via `?userid=`.

## 3. Settings
| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `enabletracking` | checkbox | 1 | Master switch for the heartbeat |
| `showstudyheatmap` | checkbox | 1 | Show the heatmap on the performance page |

## 4. Data
One table, `{trackmytime}` (userid, courseid, cmid, timestart, timeend, timespent, timecreated). No dashboard/announcement web services (those were GemUI-dashboard-specific and are omitted). One web service: `local_trackmytime_record_session` (write, AJAX).

## 5. Not included (independence boundary)
No `theme_gemui`, `format_gemui`, or other gemui dependency. The dead `gemui_performance` aggregate table was dropped. The GemUI dashboard KPI tiles remain part of the theme, not this plugin.

## v1.1.0 — Public reporting API (2026-07-08)
New: `\local_trackmytime\reporting` — a supported, versioned PHP API so other plugins
(first consumer: `local_reportpanel`) can read time-on-task analytics without touching
the `{trackmytime}` table directly. Methods: `course_time`, `course_time_by_user`,
`module_time`, `time_series`, `active_users`, `active_user_count`, `is_active`,
`usage_heatmap`, `course_rankings`, `total_time`, `format_seconds`, plus `is_available`
(safe no-op when the table is absent). Contract version `APIVERSION = 1`; add methods,
don't break signatures. No schema change; no new user data captured.
