# local_edzfaculty — Functionality

**Faculty / Teacher Dashboard for the AIMA academic Moodle instance** (reusable on the GAIL instance via terminology config). A single, action-first command centre that surfaces everything a professor needs to teach and intervene — grading, discussions, at-risk students, live classes, exams, per-course stats and quick actions — without navigating Moodle's scattered default pages.

Built to the approved clickable wireframe (delivered separately as `faculty-dashboard-wireframe.html`).

## Who it's for
- **Editing teachers / teachers** (the professor) — primary user. They never enrol as students.
- **Managers / admins** — can view any faculty member's dashboard (`viewall`) for oversight.

## Roles & access
| Capability | Who | Purpose |
|---|---|---|
| `local/edzfaculty:view` | teacher, editingteacher | see own faculty dashboard |
| `local/edzfaculty:viewall` | manager, admin | view any teacher's dashboard (impersonated read-only) |
| `local/edzfaculty:managesettings` | admin | plugin settings |

A student who somehow reaches `/local/edzfaculty/` with no teaching role sees an access notice, not the dashboard.

## Screens
Single dashboard page `/local/edzfaculty/index.php`, three stacked zones matching the wireframe:

### Zone 0 — Course Focus (per-course command bar)
- Course selector (dropdown, defaults to "All courses").
- Four stat cards, rescoped by the selector: **Live classes upcoming · Assignments to review · Discussion new posts · Quizzes to grade**.
- Quick-action buttons: **Review Assignments · Check Attendance · Post in Discussion · Upload Course Content · Schedule Live Class · Create Assessment** — each deep-links into the correct Moodle page for the selected course.

### Zone 1 — Needs Your Attention (triage)
- KPI tiles: To Grade · Unanswered Questions · At-Risk Students · Next Live Class.
- Tabbed worklist: **Grading** (oldest-first, per section, AI-assist badge) · **Discussions** (unanswered questions aging) · **At-Risk** (auto-flagged, click → student 360) · **Overdue** (non-submitters, unpublished content, unbuilt exams).
- **Today & Upcoming** timeline: live classes (Join), office hours, exam windows, assessment dates.

### Zone 2 — My Courses
- Course cards: content-delivered %, student count, avg score, at-risk count. Click → sets Course Focus.

### Zone 3 — Insight
- Section performance comparison (bar chart, `core/chartjs`).
- Engagement — This Month, **filterable per course**: active students, content views, assessments taken, avg score, each with a trend vs last month.
- AI insight line per course scope.

### Student 360 (modal)
Attendance, avg score, last login, missed deadlines, forum posts, score trend; AI "why flagged" reasons; recent activity; actions: send nudge, open full record.

## Settings inventory
- **Terminology**: `academic` (Students / Sections / Semesters — AIMA default) or `corporate` (Learners / Batches / Programs — GAIL).
- **At-risk thresholds**: min attendance %, max days since login, min score, score-drop %, missed-deadline count.
- **Cache TTL** (default 6h) and **manual refresh throttle** (default 30 min).
- **Live-class source**: auto-detect `mod_bigbluebuttonbn` / `mod_zoom` / calendar events.
- **Show AI features**: master toggle for AI-assist badges & insight text (Phase 2 wires the models).

## Phasing
- **Phase 1 (this build)** — scaffold, DB cache, capabilities, settings, full dashboard render with real Moodle queries for courses, grading queue (assign), discussions, timeline (calendar), per-course focus, engagement + at-risk from cache; scheduled task; AJAX; AMD interactivity; privacy provider. Installable & demoable.
- **Phase 2** — AI grading assist + plagiarism/AI-writing flags (axis-ai hook), one-click nudge messaging, precise quiz manual-grading counts, deep Zoom/BBB attendance auto-sync.
- **Phase 3** — configurable-report export, mobile polish, block-ified widgets (To Grade, At-Risk) for the standard Dashboard.
