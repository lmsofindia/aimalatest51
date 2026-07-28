# local_edzallcourse — Functionality

**Plugin:** `local_edzallcourse` — "EDZ Catalogue"
**Type:** Moodle local plugin
**Target:** Moodle 4.4+ (5.x compatible)
**Purpose:** A fast, public-facing course catalogue that replaces Moodle's `/course/index.php` with a branded, category-drilldown browsing experience. One codebase serves both AIMA and GAIL via language strings + a terminology helper.

---

## 1. Overview

Learners (and guests) land on the catalogue and browse courses by category. Navigation is a **hybrid drilldown**: root programmes sit in a left rail; selecting one reveals a chip row of its sub-categories and shows every course beneath it (recursively). Selecting a chip stacks another chip row below and narrows the grid, level by level, down to a single leaf category. A search box, sort control and numbered pagination operate on whatever category scope is currently selected.

The plugin computes nothing bespoke in SQL that Moodle already provides — it builds on `core_course_category` and `core_course_list_element`, which are cache-aware and already enforce course visibility, guest access and enrolment rules.

---

## 2. User roles & what they see

| Role | Experience |
|---|---|
| Guest (if allowed) | Browse catalogue, view course pages; "View" / "Enrol" CTAs. |
| Authenticated user / student | Same, plus enrolment-aware CTA ("Go to course" when already enrolled). |
| Teacher | Same as student. |
| Manager / admin (`moodle/course:update`) | Same, plus a small **edit pencil** on each course card linking to that course's settings. |

Capability to view the catalogue: `local/edzallcourse:view` (guest/user/student/teacher/manager by default).
Capability reserved for management affordances: `local/edzallcourse:manage`.

---

## 3. Core features

1. **Hybrid category drilldown**
   - Left rail lists **root categories** (top-level, `parent = 0`) with a recursive course count each, shown as `(n)`.
   - Clicking a root selects it: a **level-1 chip row** appears (its direct children), and the grid shows all courses under the root, recursively.
   - Each chip row is prefixed with its parent name + level number and includes an **"All &lt;parent&gt;"** chip to stay at that level.
   - Selecting a chip that has children **stacks a new chip row below**; selecting a leaf shows only that leaf's courses.
   - Every chip carries a recursive `(n)` count. Chip rows sit on a soft coloured band.

2. **Recursive vs direct scope** (admin setting)
   - Default **recursive**: a selected category shows its own courses plus all descendants'.
   - **Direct** mode shows only courses placed directly in the selected category (the legacy behaviour).

3. **Course cards**
   - Course image (overview file) or a generated gradient + pattern fallback when none.
   - Category badge, title (links to course), 2-line summary, primary teacher (course contact), start date.
   - Enrolment-aware CTA: "Enrol now" / "Go to course" (enrolled) / "View" (guest).
   - Admin/manager edit pencil (capability-gated per course).

4. **Search** — debounced text search over course full/short name and summary, scoped to the current category selection.

5. **Sort** — Most popular (enrolments), Newest, Name A–Z, Start date.

6. **Numbered pagination** — server-side `limit`/`offset`; "Showing X–Y of N"; prev · 1 2 3 · next; page reflected in the URL.

7. **Deep-linkable URL state** — `category`, `page`, `q`, `sort` live in the query string via the History API, so any view is shareable and the browser back button works. First load renders server-side (works with JavaScript disabled; crawlable).

8. **Course-index takeover** — a `before_http_headers` hook redirects `/course/index.php` (optionally carrying `categoryid`) to the catalogue. (Admin bypass to be revisited later; unchanged from current behaviour for now.)

---

## 4. Screens

- **Catalogue page** (`/local/edzallcourse/index.php`)
  - Hero header (title + description; strings, brand-aware).
  - Top bar: search + sort.
  - Left rail: root categories.
  - Main: stacked chip rows (drilldown) → course grid → numbered pagination.
  - States: skeleton cards while loading, empty state, error toast (`core/notification`).

---

## 5. Settings inventory (`settings.php`)

| Setting | Type | Default | Purpose |
|---|---|---|---|
| `enable` | checkbox | 1 | Master on/off. When off, the redirect and page are inactive. |
| `perpage` | text (int) | 12 | Courses per page. |
| `defaultsort` | select | popular | popular / new / az / start. |
| `scope` | select | recursive | recursive / direct. |
| `showteacher` | checkbox | 1 | Show primary teacher on cards. |
| `showstartdate` | checkbox | 1 | Show start date on cards. |
| `showsummary` | checkbox | 1 | Show summary on cards. |
| `takeovercourseindex` | checkbox | 1 | Redirect `/course/index.php` to the catalogue. |
| `herotitle` | text | (lang default) | Hero heading override. |
| `herotext` | textarea | (lang default) | Hero description override. |

Every setting has a sensible default so a fresh install renders correctly.

---

## 6. Non-goals (this build)

- Custom-field faceted filters (Level / Mode / Department) — planned for a later phase; the query layer is built to accept them.
- Global Search (`\core_search`) integration — optional future enhancement.
- Admin-specific bypass of the course-index redirect — to be designed later with the user.

---

## 7. Brand / multi-instance notes

- All user-visible copy is a language string; brand-specific wording ("Programme" vs "Course", "Department" vs "Category") is resolved through `helper\terminology`, which reads overridable config.
- Colours come from theme CSS variables with plugin defaults, so AIMA and GAIL each inherit their own palette without code changes.
