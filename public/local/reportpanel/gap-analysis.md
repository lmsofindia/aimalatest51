# local_reportpanel — Gap Analysis vs Edwiser Reports & LMSACE Reports

Drafted 2026-07-08. Compares what `local_reportpanel` covers **today** against
Edwiser Reports (`local_edwiserreports-free`) and LMSACE Reports (`report_lmsace_reports`).

## What the panel already has (for reference)
Quiz report (attempts, high/low/avg grade), Certificate report (issued counts),
Consolidated **user** report (completion% + grade + cert + badge, with charts + PDF/CSV),
Consolidated **course** report (per-learner status, 6 stat cards, completion doughnut,
grade-distribution bar), Badges/Leaderboard/Log/Site/Team as **links out**. Self vs full
mode. HR Manager role. Branded PDF/CSV export. Charts are **snapshots**, server-side core API.

Its strengths (ahead of both references): one-row consolidated user view, branded
PDF/CSV, self-service mode, capability-scoped HR role.

---

## The gaps, grouped by data domain

### A. Time & engagement tracking — **biggest gap, we have none**
| Missing element | In Edwiser | In LMSACE | Source available? |
|---|---|---|---|
| Time spent on site / per course | ✅ (Learner, Student Engagement blocks) | — | **Maybe — we have `local_trackmytime`**; else logstore |
| Site visits / course visits / module views | — | ✅ (Site user visits, Course visits) | logstore_standard_log |
| Active vs inactive users over time (by month/period) | ✅ (Active Users block) | ✅ (Active & inactive by month) | logstore |
| Inactive users list (not logged in N days) | ✅ | ✅ (not accessed in 7 days) | user.lastaccess |
| Hourly site-usage heatmap (avg usage across week) | ✅ (Site Access Info) | — | logstore |
| Daily site activity feed | ✅ (Daily Activities) | — | logstore |
| Live users (currently online) | ✅ (Live Users block) | — | sessions / lastaccess |

### B. Site-wide overview KPIs — we delegate this to a link, own nothing
| Missing element | Edwiser | LMSACE |
|---|---|---|
| Site performance overview (total enrolments, activity + course completions, new registrations, visits) | partial | ✅ |
| New user registrations over period | ✅ | ✅ |
| User registration breakdown (registered / deleted / confirmed / unconfirmed) | — | ✅ |
| Enrolment stats (users in 0 courses, users in >1 course, not-accessed users) | — | ✅ |
| Storage / plugin / infra info | — | ✅ (low value for us) |

### C. Trend charts over time — ours are snapshots only
| Missing element | Edwiser | LMSACE |
|---|---|---|
| Enrolment trend over a date range | ✅ | ✅ |
| Completion-rate trend over a date range | ✅ | ✅ |
| Active-users trend | ✅ | ✅ |
> Our charts are point-in-time (doughnut, grade bars). No time-series / date-range picker anywhere.

### D. Ranking / "popular" reports — none
| Missing element | Edwiser | LMSACE |
|---|---|---|
| Top N courses by enrolment | ✅ (Popular Courses) | ✅ (Top 10) |
| Top N courses by completion | — | ✅ (Top 10) |
| Highest-scoring learners in a course | — | ✅ (Top 20 high scores) |
| Most-visited courses (site & per-user) | ✅ | ✅ (10 most visited) |

### E. Activity coverage — we cover quiz + cert only
| Missing element | Edwiser | LMSACE |
|---|---|---|
| Assignment submissions / results | — | ✅ (My Quizzes & Assignments) |
| Activity completion counts (done / overdue) | ✅ | ✅ (My Activities: count, completed, overdue) |
| Course modules & grades (all mods, not just quiz) | — | ✅ |
| Forum / resource participation | ✅ (engagement) | ✅ (My Activities) |

### F. Cohort & group dimension — none (**critical for HR/L&D**)
| Missing element | Edwiser | LMSACE |
|---|---|---|
| Cohort membership reporting | — | ✅ (Cohorts & Groups) |
| Group membership reporting | — | ✅ |
| Cohort-scoped filtering of any report | — | partial |
> We filter by category→course→user, never by **cohort/department** — the exact axis an
> HR/L&D manager reports on. This gap matters more than any single chart.

### G. Per-user access history — thin
| Missing element | Edwiser | LMSACE |
|---|---|---|
| Login count / login history | ✅ | ✅ (User Logins & Scores) |
| Last-access per course | ✅ | ✅ |

### H. Self-serve custom report builder — none
| Missing element | Edwiser | LMSACE |
|---|---|---|
| Build-your-own report editor | ✅ (`customreportedit.php`) | — |
> Moodle 5.x ships a native Custom Report Builder — we could surface/embed it rather than build one.

### I. Role-specific landing views — partial
| Missing element | Edwiser | LMSACE |
|---|---|---|
| Dedicated **teacher** engagement dashboard | ✅ (Student Engagement block) | via course reports |
| Dedicated **student** progress+time block | ✅ (Learner block) | ✅ (User reports) |
> We have self vs full mode, but no distinct teacher-scoped or manager-scoped **dashboard** surface.

---

## Priority read (what actually matters for our product)

1. **Cohort/department dimension (F)** — prerequisite for the HR/L&D Manager story. Highest value.
2. **Time & engagement tracking (A)** — the single largest missing data domain; check whether
   `local_trackmytime` already captures this before touching logstore.
3. **Site-wide overview KPIs + trends (B, C)** — turns the hub from a link farm into a real dashboard.
4. **Assignment + activity-completion coverage (E)** — closes the "quiz+cert only" gap; feeds compliance reports.
5. **Ranking reports (D)** — cheap wins, good demo value.
6. **Custom report builder (H)** — prefer surfacing Moodle's native one over building our own.

Deliberately deprioritised: infra/storage stats (B), live-users real-time block (nice-to-have),
building a bespoke report editor (H — use core).
