# local_reportpanel — Phase 2 Build Plan (approved domains)

Drafted 2026-07-08. Scope confirmed with owner:
1. **Time & engagement** — source from `local_trackmytime` (we own the data). ✅
2. **Activity coverage** — generic to *any* activity in a course; start with **assignment + activity completion**. ✅
3. **Ranking reports** — add. ✅
4. **Site-wide overview** — build our own; **drop the LMSACE dependency**. ✅
5. Cohort/department dimension — **deferred** (later phase).

Charts / PDF / quick-mailer / scheduler engine = separate plan (`detailed-reports-plan.md`),
layered on after the data domains below exist.

---

## 0. Shared foundation (build first — everything else needs it)

- **Date-range control** (new AMD + PHP filter): last 7/30/90 days, this/last month,
  custom range. Domains A, D, B all require it; our current reports have no time axis.
- **Report data-provider interface** (`classes/report/report_base.php`): each report
  exposes `get_data($filters)`, `get_charts()`, `get_columns()`. Makes A–D uniform and
  is the same refactor the mailer/scheduler will need — do it once, here.
- **Time-series chart renderer** in `helper/charts.php`: a line/area chart from a
  `[date => value]` series. Mind the known sizing gotcha (definite width+height on the
  `.chart-area/.chart-image` parent) and the fact PDFs need a **server-side** image, not
  a canvas (see detailed-reports-plan §2).

---

## 1. Domain A — Time & engagement (from `local_trackmytime`)

**Data source — via API (decision locked):** `local_trackmytime` exposes a stable
**reporting API**; `local_reportpanel` consumes it — no direct cross-plugin table reads.

New in `local_trackmytime`: `classes/reporting.php` (`\local_trackmytime\reporting`),
a versioned static PHP class (server-side, not a web service — these are internal report
queries). Proposed surface:

```php
reporting::course_time(int $userid, int $courseid, ?int $from, ?int $to): int   // seconds
reporting::module_time(int $cmid, array $userids, ?int $from, ?int $to): array   // [userid=>secs]
reporting::time_series(array $filters): array        // [yyyymmdd => seconds]
reporting::active_users(int $sincedays = 7): array   // userids with a session in window
reporting::is_active(int $userid, int $days = 7): bool
reporting::usage_heatmap(?int $from, ?int $to): array // [dow][hour] => seconds
reporting::course_rankings(?int $from, ?int $to, int $limit = 10): array // [courseid=>secs]
```

On the reportpanel side, `helper/timetracking.php` calls this behind a
**`class_exists('\local_trackmytime\reporting')` guard** so the Hub degrades gracefully
("time data unavailable") if trackmytime isn't installed. Bump trackmytime's version and
document the API as a supported contract so future changes stay backward-compatible.

**Metrics/reports to add:**
- Time spent **per learner per course** — `SUM(timespent) GROUP BY userid, courseid`.
  → new column in the consolidated **user** and **course** reports.
- Time spent **per module** (`GROUP BY cmid`) — feeds the activity report (§2).
- **Engagement trend** — time-on-task per day/week (`GROUP BY day(timestart)`), line chart.
- **Active vs inactive** learners — has session in last N days vs not (list + count).
- **Site usage heatmap** — hour-of-day × day-of-week from `timestart`. Reuse the 26-week
  heatmap logic already in `trackmytime/pages/performance.php`.
- **Most-engaged courses** — rank by `SUM(timespent)` or session count (feeds §3 ranking).

**Honest coverage note:** trackmytime captures **module-view time** only. It does *not*
record logins or non-module page visits. So "login count / last-access" (gap G) and raw
site visits come from `user.lastaccess` + `logstore_standard_log`, not trackmytime — keep
those out of scope for now unless needed; flag in overview as "from core logs."

---

## 2. Domain E — Activity coverage (generic; start assignment + completion)

**Today:** quiz + certificate are hardcoded pages. **Goal:** a course-level activity
report that lists **every activity** in the chosen course with per-activity metrics.

**Design — pluggable activity providers:**
- `classes/activity/activity_provider_base.php` — interface: `get_metrics($cm, $users)`.
- **Default provider** (works for *any* module): uses `course_modules_completion`
  (completion state per user) + `grade_items/grade_grades` (grade if gradable) +
  time-on-task from §1 (join on `cmid`). Columns: completed / in-progress / not-started,
  overdue (via `completionexpected`), avg grade, avg time.
- **Assignment provider** (`mod_assign`, first specialised one): submissions count,
  submitted vs graded, on-time vs late (`assign_submission.timemodified` vs `duedate`),
  avg grade (`assign_grades`).
- Quiz stays as its richer existing provider; cert stays as-is. New modules = add a
  provider, no page rewrite.

**Report surface:** on the consolidated **course** report, add an "Activities" table:
one row per activity (name, type icon, completed X/Y, overdue, avg grade, avg time),
each drillable to per-learner detail. Start: assignment + generic completion; expand later.

---

## 3. Domain D — Ranking reports (cheap wins, chart-friendly)

New `rankings.php` (full-mode), category + date-range filtered, all bar-chart backed:
- **Top N courses by enrolment** (`user_enrolments` join `enrol`).
- **Top N courses by completion rate** (`course_completions` / enrolled).
- **Top N courses by time spent / engagement** (§1 trackmytime).
- **Top N / high scorers per course** (`grade_grades`, top 20) — generalises LMSACE's
  "high scores."
- **Most-active learners** — by completions, avg grade, or time (toggle).
- Configurable N (10/20/50). Each block: bar chart + table + PDF/CSV export.

---

## 4. Domain B — Site-wide overview (replaces the LMSACE link)

New `overview.php` — full-mode dashboard, becomes card #2 (repoint the "Site reports"
card from `/report/lmsace_reports/...` to `/local/reportpanel/overview.php`; can drop the
LMSACE default entirely). Built by **composing** the providers from §1–§3, so build it last.

**KPI stat cards (period-aware):** total users, active users (period), total enrolments,
course completions, activity completions, certificates issued, new registrations (period).

**Trend charts (date-range):** registrations, enrolments, completions, active users, and
total time-on-task — all as time-series lines.

**Health tables:** registration breakdown (confirmed / unconfirmed / suspended / deleted),
enrolment health (users in 0 courses, users in >1 course, not-accessed-in-N-days), and a
"Top courses" strip linking into §3.

PDF/CSV export of the whole overview (branded, reusing the edzteams TCPDF pattern).

---

## Build order (one at a time)

| Step | Deliverable | Depends on |
|---|---|---|
| **P2.0** | Shared: date-range control, `report_base`, time-series chart renderer | — |
| **P2.1** | Domain A time/engagement + `helper/timetracking.php` (+ time column in consolidated reports) | P2.0, trackmytime |
| **P2.2** | Domain E activity coverage (generic provider + assignment + completion) | P2.0, P2.1 (time join) |
| **P2.3** | Domain D ranking reports | P2.0, P2.1 |
| **P2.4** | Domain B site-wide overview + repoint card #2, drop LMSACE | P2.1–P2.3 |
| **later** | Domain F cohort/department dimension; then charts/PDF/mailer/scheduler engine | all above |

## Decisions — LOCKED (2026-07-08)
1. **trackmytime access:** ✅ **API** — `\local_trackmytime\reporting` PHP class,
   consumed by reportpanel behind a `class_exists` guard (see §1).
2. **Active/inactive threshold:** ✅ **7 days** (default; expose as a plugin setting so
   it's tunable later).
3. **Site overview:** ✅ **replace card #2** — repoint "Site reports" to
   `/local/reportpanel/overview.php` and retire the LMSACE default.

## First build unit = P2.0 + P2.1
Because A is API-gated, the first buildable slice is:
- **In `local_trackmytime`:** add `classes/reporting.php` + version bump + testing notes.
- **In `local_reportpanel`:** P2.0 shared foundation (date-range control, `report_base`,
  time-series chart renderer) + `helper/timetracking.php` consumer + a **7-day** active-user
  setting + first surfacing (time column in consolidated reports; engagement trend + heatmap
  + active/inactive on a new "Engagement" report or the overview stub).

Per Vidya's method, each plugin gets functionality.md + technical.md + readme.md +
testing.txt updated **before** code.
