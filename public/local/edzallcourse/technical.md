# local_edzallcourse — Technical Design

**Component:** `local_edzallcourse`
**Moodle:** 4.4+ (`$plugin->requires = 2024042200`), tested pattern on 5.x
**No custom DB tables** — the catalogue reads core course/category data. State that matters is cached in MUC, not persisted.

---

## 1. Architecture at a glance

```
Browser
  │  (initial load)          (in-page updates)
  ▼                               ▼
index.php ──► renderable ──► renderer ──► Mustache        AMD catalogue.js
  │            catalogue_page          (shared templates)   │  core/ajax
  ▼                                        ▲                ▼
helper\catalogue  ◄──────────────────────── │        external\get_tree
  │  core_course_category (cached tree)      └──────  external\get_courses
  │  core_course_list_element (image,                       │
  │  get_course_contacts)                                   ▼
  ▼                                             helper\catalogue / helper\card
MUC cache (cattree, counts)                     (same code path as server render)
```

Single source of truth for view data: `helper\card` + the Mustache templates. Both the server (first paint) and the AJAX endpoints (subsequent updates) render the **same** templates, so markup never diverges.

---

## 2. Class map

| Class | Responsibility |
|---|---|
| `local_edzallcourse\helper\catalogue` | Core query orchestration: root categories, children of a node, recursive descendant id list, paginated course fetch (limit/offset), search, sort, counts. Caches the tree + counts in MUC. |
| `local_edzallcourse\helper\card` | Turn one `core_course_list_element` (or course id) into card view-data: image or gradient/pattern fallback, badge, teacher (course contact), start date, enrolment-aware CTA, edit URL (capability-gated). |
| `local_edzallcourse\helper\terminology` | Resolve brand wording (category/course/programme labels) from config + lang. |
| `local_edzallcourse\external\get_tree` | AJAX: given a category id, return the chip-row data (its children + counts) and the ancestor path. Typed params/returns. |
| `local_edzallcourse\external\get_courses` | AJAX: given category id + scope + q + sort + page, return paginated card data + pagination meta. |
| `local_edzallcourse\output\renderer` | `render_catalogue_page()` and helpers to render shared templates server-side. |
| `local_edzallcourse\output\renderable\catalogue_page` | Implements `renderable`+`templatable`; assembles initial page state (roots, initial chip rows, first page of cards, pagination). |
| `local_edzallcourse\hooks\hook_callbacks` | `before_http_headers` — course-index takeover redirect. |
| `local_edzallcourse\privacy\provider` | `null_provider` — stores no personal data. |

No global functions. `lib.php` holds only the navigation callback and `..._pluginfile()`.

---

## 3. Data layer (core APIs, not raw SQL)

- **Tree:** `core_course_category::get($id, IGNORE_MISSING, true)` → `->get_children()`. Roots = `core_course_category::top()->get_children()`. The tree is served from Moodle's `coursecattree` MUC cache and already filtered by user visibility.
- **Recursive descendant ids:** walk `get_children()` (memoised per-request) OR use `core_course_category::get_all_children_ids()` where available. Used to scope course queries in recursive mode.
- **Courses in scope (paginated):**
  - Recursive: `$category->get_courses(['recursive' => true, 'sort' => [...], 'limit' => $perpage, 'offset' => $offset])`.
  - Direct: `['recursive' => false, ...]`.
  - Returns `core_course_list_element[]`; total via `$category->get_courses_count(['recursive' => $scope])`.
- **Search:** `core_course_category::search_courses(['search' => $q], ['sort'=>..., 'limit'=>..., 'offset'=>..., 'idnumber'=>...])`, then constrained to the in-scope category id set. (Access-aware, cached.)
- **Course image:** `core_course_list_element::get_course_overviewfiles()` → first image; fallback to `helper\card::gradient()` + `pattern()` (deterministic by course id).
- **Teachers:** `core_course_list_element::get_course_contacts()` — respects the site's `$CFG->coursecontact` role config (no hardcoded role id).
- **Enrolment count / popularity:** batched — one query over the visible page's course ids joining `{enrol}`/`{user_enrolments}`; not per-card.
- **Enrolment state (CTA):** `is_enrolled($coursecontext, $USER)` per visible card (cheap, on the page's ids only).

### Sort mapping
| UI value | Core sort |
|---|---|
| popular | enrolment count desc (computed on page ids) → fallback `sortorder` |
| new | `timecreated DESC` |
| az | `fullname ASC` |
| start | `startdate ASC` |

---

## 4. External API (`db/services.php`)

| Function | Params | Returns |
|---|---|---|
| `local_edzallcourse_get_tree` | `categoryid:int` | `children[]{id,name,count,haschildren}`, `path[]{id,name}`, `label` (parent name), `level:int` |
| `local_edzallcourse_get_courses` | `categoryid:int, scope:alpha, q:text, sort:alpha, page:int` | `courses[]{...card fields}`, `total:int`, `page:int`, `perpage:int`, `totalpages:int`, `from:int`, `to:int` |

- Both `ajax => true`, `loginrequired => false` (guest catalogue), capability `local/edzallcourse:view` checked inside `execute()`.
- All params typed with `PARAM_INT` / `PARAM_ALPHA` / `PARAM_TEXT`; no `PARAM_RAW`, no `$_GET`.
- `get_courses` returns **structured data**; the JS renders `course_grid`/`course_card` via `core/templates` — same templates the server uses.

---

## 5. Templates (`templates/`)

| Template | Rendered by | Notes |
|---|---|---|
| `catalogue.mustache` | server (index) | Page shell: hero, controls, rail, chip-row container, grid container, pager container. Loads AMD. |
| `roots.mustache` | server | Left-rail root list with `(n)` counts. |
| `drilldown.mustache` | server + client | Stacked chip rows; coloured band; `(n)` counts; "All &lt;parent&gt;" chip. |
| `course_grid.mustache` | server + client | Grid wrapper + `{{#courses}} > course_card`. Empty state. |
| `course_card.mustache` | server + client | One card. Partial, reused. |
| `pagination.mustache` | server + client | Numbered pager + "Showing X–Y of N". |

Templates use `{{#str}}` for all copy so they localise correctly on both server and client renders.

---

## 6. AMD (`amd/src/catalogue.js`, built to `amd/build/`)

Single module, `init(config)` called from the page template. Responsibilities:
- Render/refresh the drilldown chip rows and grid via `core/templates` from `get_tree` / `get_courses` responses.
- Handle: root click, chip click (stack/trim rows), search (debounced ~250 ms), sort change, pager click.
- Maintain `{categoryid, scope, q, sort, page}` and mirror it to the URL (`history.pushState`); handle `popstate` for back/forward.
- Skeleton loading state on the grid; errors via `core/notification`.
- Dependencies: `core/ajax`, `core/templates`, `core/notification`, `core/str`. **Build kept in sync** (terser) — noted in `testing.txt`.

---

## 7. Caching (`db/caches.php`)

| Cache | Type | Keyed by | Contents | Invalidation |
|---|---|---|---|---|
| `treecounts` | application | `scope` | root/child structure + recursive counts | short TTL (e.g. 300s) + purge on caches clear |

Category structure itself already rides Moodle's core `coursecattree` cache; we only memoise our derived counts to avoid recomputing per request. Kept conservative (short TTL) so new/hidden courses appear promptly without a manual purge.

---

## 8. Capabilities (`db/access.php`)

| Capability | Context | Archetypes |
|---|---|---|
| `local/edzallcourse:view` | System | guest, user, student, teacher, manager = ALLOW |
| `local/edzallcourse:manage` | System | manager = ALLOW (reserved; edit affordances) |

Every entry point (`index.php`, both external classes) calls `require_capability('local/edzallcourse:view', context_system::instance())` before any data read. The per-card edit pencil is gated by `has_capability('moodle/course:update', context_course::instance($id))`.

---

## 9. Hook (`db/hooks.php` + `classes/hooks/hook_callbacks.php`)

`core\hook\output\before_http_headers`: if enabled AND `takeovercourseindex` AND `$SCRIPT === '/course/index.php'`, redirect to `/local/edzallcourse/` carrying `optional_param('categoryid', 0, PARAM_INT)` as `?category=`. Guards `during_initial_install()` / upgrade. (No `$_GET`.)

---

## 10. Settings storage

`get_config('local_edzallcourse', $key)` with null-coalesced defaults everywhere (`?: default`), since unset returns `false`. Defaults also declared in `settings.php`.

---

## 11. Build phases

1. **Scaffold** — version, settings, lib, db (access/services/caches/hooks), lang, privacy, capabilities. Page renders empty shell.
2. **Data + server render** — `helper\catalogue` + `helper\card`, renderable + templates, `index.php`. Full catalogue works server-side (JS off): roots, first chip row, first page recursive, pager.
3. **External API** — `get_tree` + `get_courses`, `db/services.php`.
4. **AMD** — `catalogue.js`: drilldown, search, sort, pagination, URL state; build synced.
5. **Polish** — styles (theme vars, coloured chip rows, `(n)` counts, skeletons, a11y), terminology, edit pencil, empty/error states.
6. **QA** — `testing.txt` matrix; php lint; deploy checklist.

---

## 12. Removed from the legacy plugin

`ajax.php`, `subcat_ajax.php`, `src/allencourse.js` (orphan mega-menu), `amd/src/ui.js`, `templates/catinfo.mustache`, `templates/catfilterinfo.mustache`, both global `get_course_gradient_color()`/`get_course_pattern_datauri()` functions, the debug `echo "rashid"; die();` block, and `service::search_courses()` / `service::get_courses_by_category()` raw SQL. Replaced by the above.
