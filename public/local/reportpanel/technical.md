# local_reportpanel — Technical design

Moodle 5.0+ (`$plugin->requires = 2024100700` baseline; matches sibling plugins).
PHP 8.1+. No new DB tables. Namespaced `local_reportpanel\*`. Mirrors the proven
architecture of `local_edzteams` (helper classes build template context; AJAX via
`classes/external/`; TCPDF export via a helper; capability checks at every entry).

## File map

```
local/reportpanel/
├── version.php
├── settings.php                      # heading + card URLs + on/off toggles
├── lib.php                           # local_reportpanel_extend_navigation()
├── index.php                         # landing grid (role-aware cards)
├── quiz.php                          # quiz reports (full + self)
├── certificate.php                   # certificate reports (full + self)
├── consolidated.php                  # consolidated user report (+ ?export=pdf|csv)
├── classes/
│   ├── helper/
│   │   ├── access.php                # is_full_mode(), card list builder, cap checks
│   │   ├── catalogue.php             # categories, courses-in-category, quizzes, certs
│   │   ├── quizreport.php            # quiz stats (full) + own attempts (self)
│   │   ├── certreport.php            # cert issued counts (full) + own certs (self)
│   │   ├── consolidated.php          # build consolidated rows for a user
│   │   └── export.php                # PDF (TCPDF) + CSV writers
│   ├── external/
│   │   ├── get_courses.php           # courses in a category (AJAX cascade)
│   │   └── search_users.php          # user typeahead (full mode only)
│   ├── output/
│   │   └── renderer.php              # render_from_template wrappers (optional)
│   └── privacy/
│       └── provider.php              # null_provider — plugin stores no user data
├── db/
│   ├── access.php                    # capabilities
│   └── services.php                  # AJAX function registration
├── amd/
│   ├── src/cascade.js                # category->course dropdown + redirect
│   ├── src/usersearch.js             # consolidated user typeahead
│   └── build/*.min.js                # kept in sync manually
├── templates/
│   ├── panel.mustache                # card grid
│   ├── quiz_full.mustache            # pickers + quiz table
│   ├── quiz_self.mustache            # own attempts table
│   ├── cert_full.mustache
│   ├── cert_self.mustache
│   ├── consolidated.mustache         # search/header + report table + export buttons
│   └── report_table.mustache         # shared table partial
├── lang/en/local_reportpanel.php
├── styles.css
├── pix/ (card icons fall back to core pix)
├── functionality.md / technical.md / readme.md / testing.txt / hrmanager.md
```

## Capabilities (`db/access.php`)

```
local/reportpanel:view      read   CONTEXT_SYSTEM  archetypes: user (CAP_ALLOW)
local/reportpanel:viewall   read   CONTEXT_SYSTEM  riskbitmask RISK_PERSONAL
                                                    archetypes: manager (CAP_ALLOW)
```

- `:view` — see the panel and self-scoped reports. Granted to authenticated users
  (`user` archetype) so every logged-in user gets self-service by default.
- `:viewall` — full mode (all-user data, selection forms, user search). Granted to
  `manager` archetype (so admins inherit it) and to the **HR Manager** role.

Every page calls `require_login()` then `require_capability('local/reportpanel:view',
$systemcontext)`. Full-mode-only branches additionally check `:viewall`.
`consolidated.php` enforces: in self mode the only viewable `userid` is `$USER->id`.

## Access helper (`classes/helper/access.php`)

- `is_full_mode(): bool` → `has_capability('local/reportpanel:viewall', system)`.
- `can_view_teams(): bool` → full mode `||` `has_capability('local/edzteams:viewteam')`.
- `cards(): array` → returns the ordered card definitions filtered by visibility,
  each: `{key, title, desc, icon, url, target, enabled}`. External URLs come from
  config (`get_config('local_reportpanel', 'url_leaderboard')` …) and are passed
  through `new moodle_url()`; relative defaults shipped in settings.

## Catalogue helper (`classes/helper/catalogue.php`)

- `categories(): array` — `core_course_category::make_categories_list()` filtered to
  those the viewer can see (full mode: all; respects `moodle/category:viewcourselist`).
- `courses_in_category(int $catid): array` — `get_courses($catid, 'c.fullname', ...)`.
- `quizzes_in_course(int $courseid)` / `certs_in_course(int $courseid)` — via
  `get_fast_modinfo()->get_instances_of('quiz' | 'customcert')` so we get the cmid
  for building report URLs and respect visibility.

## Quiz report helper (`classes/helper/quizreport.php`)

Full mode, per quiz in course (aggregate over finished attempts):
```sql
SELECT q.id, q.name, q.grade,
       COUNT(DISTINCT qa.userid)                       AS attempts_users,
       MAX(qg.grade) AS maxgrade, MIN(qg.grade) AS mingrade, AVG(qg.grade) AS avggrade
  FROM {quiz} q
  LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id AND qa.state = 'finished'
  LEFT JOIN {quiz_grades}   qg ON qg.quiz = q.id
 WHERE q.course = :courseid
 GROUP BY q.id, q.name, q.grade
```
Grades shown scaled to the quiz max. Quiz name links to
`/mod/quiz/report.php?id={cmid}&mode=overview`.

Self mode: the user's own row per quiz — final grade from `{quiz_grades}`, attempt
count + last attempt time from `{quiz_attempts}` (this user). Links to
`/mod/quiz/review.php` of the user's last finished attempt (fallback `view.php`).

## Cert report helper (`classes/helper/certreport.php`)

Full: per `{customcert}` in course, `COUNT({customcert_issues}.id)` issued; name links
`/mod/customcert/view.php?id={cmid}`. Self: rows from `{customcert_issues}` for
`$USER->id` joined to `{customcert}` + course, with issue date.

## Consolidated helper (`classes/helper/consolidated.php`)

For a target user, one row per enrolment (courses the user is enrolled in via
`enrol_get_users_courses($userid, true)`):
- **Completion %**: `\core_completion\progress::get_course_progress_percentage($course,
  $userid)` (null when completion tracking off → "—").
- **Completion date**: `{course_completions}.timecompleted`.
- **Grade**: final course grade — `grade_get_course_grade($userid, $courseid)` →
  formatted `str` + percentage.
- **Certificate date**: latest `{customcert_issues}.timecreated` for any customcert in
  that course issued to the user (null → "—").
- **Badges**: course badges earned — `{badge}` where `courseid = c.id` joined
  `{badge_issued}` for the user → comma-separated names ("—" if none).

Header fields: full name + email of target user.

## Export helper (`classes/helper/export.php`)

- `pdf(\stdClass $user, array $rows): void` — anonymous subclass of `\pdf` (Moodle
  TCPDF) with `Header()` (brand band: `{site} · Reports Hub`, report title right) and
  `Footer()` (rule + brand + `page / pages`). Meta block: user, email, generated
  datetime + printer name. Landscape A4 table. `Output(..., 'D'); exit;`.
- `csv(\stdClass $user, array $rows): void` — `\csv_export_writer`; same columns;
  streams and exits.

## AJAX (`classes/external/` + `db/services.php`)

| function | class | cap | use |
|---|---|---|---|
| `local_reportpanel_get_courses` | `external\get_courses` | `local/reportpanel:viewall` | category→course cascade |
| `local_reportpanel_search_users` | `external\search_users` | `local/reportpanel:viewall` | consolidated user typeahead |

`search_users` mirrors the vetted `local_edzteams\external\search_users` (name/email
LIKE, excludes guest, `validate_context`, returns id/fullname/email, capped at 20).

## AMD

- `cascade.js` — on category change, `Ajax.call` get_courses, repopulate the course
  `<select>`; on course change, redirect `quiz.php?courseid=` / `certificate.php?courseid=`
  (server renders the table — keeps it simple and bookmarkable).
- `usersearch.js` — typeahead on the consolidated search box; on pick, sets a hidden
  `userid` and submits.
- Build sync: after editing `amd/src/*`, run `grunt amd` (or hand-minify into
  `amd/build/`). Noted in testing.txt.

## Theming

No hardcoded brand hex in CSS. Card accents use Bootstrap/theme vars
(`var(--bs-primary)`, `var(--bs-body-color)`, `var(--bs-border-color)`), so the panel
matches `theme_edzcorp`. PDF uses a neutral professional blue band (TCPDF can't read
CSS vars).

## Build phases

1. Scaffold + panel grid (this file's "panel" path) — version, settings, caps, lang,
   lib nav, index.php, panel.mustache, styles, access helper, privacy provider.
2. Quiz reports (quiz.php, quizreport+catalogue helpers, cascade AJAX/AMD, templates).
3. Certificate reports (certificate.php, certreport helper, reuse cascade, templates).
4. Consolidated report (consolidated.php, consolidated+export helpers, usersearch
   AJAX/AMD, templates, PDF+CSV).
5. Privacy, hrmanager.md, lint, deploy checklist.

---

## v1.1.0 technical notes

### New files
- `classes/helper/courseconsolidated.php` — `build(int $courseid)` returns per-user rows
  + a `summary` array (status counts, grade buckets, completion rate, avg grade).
  Uses **bulk queries** (no per-user calls): completed-activity counts, course-completion
  timestamps, and course-level final grades are each one grouped query keyed by userid.
- `classes/helper/charts.php` — `status_doughnut()`, `grade_bar()`, `value_bar()`.
  Each returns a `\core\chart_*` object or `null` when there's nothing to plot.
- `courseconsolidated.php` — page: category→course cascade (server-side options +
  inline auto-submit JS), stat cards, charts, table, PDF/CSV export (sesskey-guarded).
- `templates/course_consolidated.mustache`.

### Changed files
- `classes/helper/consolidated.php` — `build()` now also returns `summary`
  (`statuscount`, `gradelabels`, `gradevalues`) for the user-report charts.
- `consolidated.php` — renders the two user-report charts; upgraded search inline JS
  (spinner + empty-state).
- `templates/consolidated.mustache` — charts row + redesigned full-width search box.
- `classes/helper/export.php` — added `course_pdf()`, `course_csv()`, `course_columns()`.
- `classes/helper/access.php` — new `courseconsolidated` card (requires `:viewall`).
- `lang/en/local_reportpanel.php` — course-report, chart, status, stat + search strings.
- `styles.css` — search box, stat cards, chart cards.
- `version.php` — 2026070800, release 1.1.0.

### Rendering choice
Charts are rendered with `$OUTPUT->render_chart($chart, false)` (table omitted — the page
already shows the full data table) and passed into the template as unescaped `{{{...}}}`.
No AMD build step required (core ships `core/chartjs`).

## v1.2.0 — Time & engagement (Domain A)
New classes:
- `classes/local/daterange.php` — preset date-range control → concrete [from,to] window + `<select>` menu.
- `classes/report/report_base.php` — abstract report source (get_data / get_columns / get_charts / get_title / get_key); the seam later reused by export/mailer/scheduler.
- `classes/report/engagement_report.php` — the engagement source; builds KPIs, trend, top-courses and heatmap payloads.
- `classes/helper/timetracking.php` — single seam onto `\local_trackmytime\reporting` (class_exists guard; empty/zero fallbacks).
- `classes/helper/charts.php` — added `time_line()` (\core\chart_line).
New page + template: `engagement.php` + `templates/engagement.mustache`.
Settings: `activedays` (PARAM_INT, default 7). Card added in `access::cards()` gated on `viewall`.
Chart sizing: `.rp-chart-wrap .chart-area/.chart-image` given definite height in styles.css (core chart render is unbounded otherwise). Version 2026070804 / release 1.2.0. No new DB tables/tasks/capabilities.

## v1.3.0 — Rankings (D) + Overview (B)
New shared helper `classes/helper/analytics.php` — static aggregate queries reused by both:
top_courses_by_enrolment / _by_completion, top_learners_by_completion, course_meta,
resolve_user_names, total_users, new_registrations, registration_breakdown,
enrolment_health, total_enrolments, course_completions, activity_completions,
certificates_issued (customcert table-guarded), day_count_series (PHP-bucketed, portable).
New report sources: `classes/report/rankings_report.php`, `classes/report/overview_report.php`.
New pages + templates: rankings.php + templates/rankings.mustache, overview.php +
templates/overview.mustache. Charts reuse charts::value_bar (rankings) and charts::time_line
(overview trends). access::cards(): `overview` (internal, replaces external `sitereports`)
and `rankings` added, both gated on viewall; `sitereports` removed from settings.php +
DEFAULT_URLS. No new DB tables/tasks/capabilities. Version 2026070805 / release 1.3.0.
Portability: all GROUP BY/COUNT standard; subquery COUNT wrappers for multicourse/enrolments.
