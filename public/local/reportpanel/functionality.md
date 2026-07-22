# local_reportpanel — Functionality

A single, professional **Reports Hub** for an EDZLMS (Moodle 5.x) site. One landing
page shows a grid of cards; each card opens a report. Some reports are external
(existing Moodle / plugin pages), some are custom pages this plugin builds.

Access is **self-service**: every logged-in user sees the panel, but what each card
shows is scoped to the viewer's permissions. A dedicated **HR Manager** role (see
`hrmanager.md`) plus site admins get the full, all-user experience.

---

## 1. Roles & visibility model

| Viewer | What they get |
|---|---|
| **Site admin** | Everything. All 8 cards, full selection forms, all-user data. |
| **HR Manager** (role with `local/reportpanel:viewall`) | Same as admin for these reports. |
| **Team manager** (has `local/edzteams:viewteam`) | Team reports card + the self-scoped cards. |
| **Any logged-in user** | Self-scoped cards only (own quiz/cert/consolidated data). Site-wide admin cards hidden. |

The single capability that flips a viewer between **full mode** and **self mode** is:

```
local/reportpanel:viewall   (granted to admin + HR Manager)
```

- **Full mode** (`viewall` = yes): selection forms shown (category → course → quiz),
  user search shown, data spans all users.
- **Self mode** (`viewall` = no): no selection forms, no user search — the page
  shows only the current user's own records.

Each card also has its own visibility rule so the grid only shows what the viewer
can actually use (e.g. Site reports / Log report are hidden in self mode).

---

## 2. The 8 cards

Each card = icon + title + one-line description + a button.

| # | Card | Opens | Visible to | Mode behaviour |
|---|------|-------|-----------|----------------|
| 1 | **Leaderboard** | `block_xp` ladder (configurable URL) | Everyone | Same for all |
| 2 | **Site reports** | LMSACE reports (configurable URL) | Full mode only | — |
| 3 | **Log report** | Site log report (configurable URL) | Full mode only | — |
| 4 | **Team reports** | `local_edzteams` dashboard (configurable URL) | Full mode **or** team managers | — |
| 5 | **Quiz reports** | Custom page `quiz.php` | Everyone | Full: pick category→course→quiz→teacher report. Self: own attempts list. |
| 6 | **Certificate reports** | Custom page `certificate.php` | Everyone | Full: pick category→course→cert→issued list. Self: own certificates. |
| 7 | **Consolidated user report** | Custom page `consolidated.php` | Everyone | Full: search a user → full report. Self: own report only (no search). |
| 8 | **Badges & achievements** | Badges (configurable URL) | Everyone | Full: site badges. Self: own badges. |

URLs for cards 1–4 and 8 are admin-editable in plugin settings (relative defaults).

---

## 3. Quiz reports page (`quiz.php`)

**Full mode**
1. Select a course category (dropdown).
2. Select a course in that category (dropdown, AJAX-populated).
3. The page lists every quiz in that course, one per row:
   - Quiz name (clickable)
   - Attempts (number of distinct users who finished an attempt)
   - Highest grade
   - Lowest (min) grade
   - Average grade
4. Clicking a quiz name opens the standard quiz overview report:
   `/mod/quiz/report.php?id={cmid}&mode=overview`.

**Self mode**
- No category/course pickers.
- Lists the user's own quiz attempts across all enrolled courses: course, quiz,
  their grade, best/last, attempts, last attempt date. Quiz name links to the
  user's own attempt review (not the teacher report).

## 4. Certificate reports page (`certificate.php`)

Same structure as quiz reports, for `mod_customcert`.

**Full mode**: category → course → list of certificates in the course with
**issued count** (how many users were issued each cert). Clicking a cert name opens
`/mod/customcert/view.php?id={cmid}`.

**Self mode**: lists certificates the user has been **issued**, with issue date;
name links to the user's certificate view.

## 5. Consolidated user report (`consolidated.php`)

**Full mode**: a typable user search (AJAX) picks one user. On submit, a table of
that user's enrolments:

| Name | Email | Course | Completion % | Completion date | Grade | Certificate date | Badge(s) |

**Self mode**: no search — the table is the current user's own consolidated report.

Both modes offer **Download PDF** and **Download CSV**.

- **PDF**: branded header band (site name · Reports Hub) repeated on every page;
  meta block with user name, email, generated date/time and the person who printed
  it; footer rule with brand left and page x/y right. (Reuses the proven TCPDF
  pattern from `local_edzteams`.)
- **CSV**: one row per course enrolment, same columns.

---

## 6. Settings (Site admin → Plugins → Local plugins → Report panel)

- Panel page heading text.
- Card URLs (leaderboard, site reports, log report, team reports, badges) — each a
  text field with a relative default; admins can repoint them.
- Toggle each external card on/off.
- (CSS accent colour inherits from the active theme; no hardcoded brand colour.)

---

## 7. Out of scope (v1)

- No data is written by this plugin except transient export files (streamed, not
  stored). No new DB tables required — all reads are live joins against core +
  plugin tables (quiz, customcert, badges, completion, grades).
- No scheduled task / progress cache in v1 (volumes are per-user or per-course on
  demand). If a site needs site-wide aggregate dashboards later, add a cache then.

---

## v1.1.0 — Consolidated course report + charts (2026-07-08)

### New: Consolidated course report (`courseconsolidated.php`)
Full-mode only (`local/reportpanel:viewall`). Admin/manager picks a **category → course**
(same cascade as the quiz/certificate reports) and gets a table of **every enrolled
learner**: completion %, activity completion (X / Y tracked activities), final grade,
completion date and a status badge (Completed / In progress / Not started).

Above the table: six **summary stat cards** (enrolled, completed, in-progress,
not-started, completion rate, average grade) and two **charts**:
- Completion-status **doughnut** (completed / in-progress / not-started).
- Grade-distribution **bar** (0–39 / 40–59 / 60–74 / 75–89 / 90–100 bands).

PDF + CSV export of the full learner table (with summary line in the PDF header block).

### Enhanced: Consolidated user report (`consolidated.php`)
- Added two charts to each user's report: a completion-status **doughnut** across their
  enrolled courses, and a **grade-per-course** horizontal bar.
- **Redesigned user search**: full-width rounded search box with a leading magnifier
  icon, focus glow, inline loading spinner, roomier results dropdown, and an explicit
  "no matching users" state. Replaces the previous small 320px input.

All charts use Moodle's native chart API (`\core\chart_pie` / `\core\chart_bar`),
rendered server-side via `$OUTPUT->render_chart()` → `core/chartjs`. No bundled Chart.js.

## v1.2.0 — Time & engagement report (Domain A) (2026-07-08)

New card **Time & engagement** (`engagement.php`), full-mode (`viewall`) only — it is
site-wide analytics. Self-mode learners are pointed to their own
`local_trackmytime` performance page instead.

All time data is sourced through the supported `\local_trackmytime\reporting` API (never
a direct table read) behind a `class_exists` guard, so the Hub still renders a graceful
"time tracking unavailable" state if trackmytime isn't installed.

The report shows, for a chosen **date range** (last 7/30/90 days, this/last month, or a
custom range):
- Four KPI cards: total time on task, active users (last N days), inactive users, and
  average time per active user.
- An **engagement trend** line (daily minutes on task).
- **Top courses by time** (bar chart + table) with a CSV export.
- A **weekly usage heatmap** (day × hour intensity).

New setting: **Active-user window (days)** (`activedays`, default **7**) — the recency
threshold that defines "active".

Deferred to later increments (per build-plan-phase2.md): activity coverage (Domain E),
ranking reports (Domain D), and the own site-wide overview that will replace the LMSACE
"Site reports" card (Domain B).

## v1.3.0 — Rankings (Domain D) + Site overview (Domain B) (2026-07-08)

Two new full-mode (`viewall`) cards.

**Rankings** (`rankings.php`): four "top N" leaderboards over a date range + optional
category, with a Top-N selector (10/20/50): top courses by enrolment, top courses by
completions (in range), top courses by time on task (in range, via trackmytime), and
most-active learners by completions. Each block = horizontal bar chart + ranked table.

**Site overview** (`overview.php`): the plugin's own site dashboard — **replaces the
external LMSACE "Site reports" card** (card #2 repointed to overview.php; the
`sitereports` external setting/URL is retired). Shows eight period-aware KPI cards
(total users, active users last N days, new registrations, enrolments, course
completions, activity completions, certificates issued, time on task), three trend lines
(registrations, completions, time/day), a registration breakdown table
(confirmed/unconfirmed/suspended/deleted), an enrolment-health table (users in no
courses, users in >1 course, not-accessed-in-N-days) and a top-5-courses strip linking
to Rankings.

Both source aggregates from the new `helper/analytics.php` and time data from
`local_trackmytime` (graceful fallback if absent). Domain E (activity coverage) remains
the next increment.

## v1.4.0 — UI/UX polish + Activity coverage (Domain E) (2026-07-08)
UI: drill-down links; KPI period-over-period deltas; thousands separators; auto-apply
filters (AMD `local_reportpanel/filters`, remembers last filter); consistent subheader
(range chip + "as of"); CSV export on engagement/rankings/overview; responsive heatmap +
filter stacking; hub cards split into Manager vs My reports sections.
Domain E: `helper/activities.php` builds a per-activity table for a course (every visible
module) — completion done/total + overdue, average grade %, average time on task, plus
assignment submission stats (submitted / graded / late). Surfaced on the course
consolidated report. Generic for any module; assignment specialised. Pluggable by adding
a `<modname>_extra()` method.
