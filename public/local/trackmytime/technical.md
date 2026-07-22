# local_trackmytime — Technical Reference

**Component:** `local_trackmytime` · **Version:** 2026070200 · **Maturity:** STABLE 1.0.0

Standalone fork of `local_gemui_timetracker`. The four theme couplings resolved:

| Coupling in gemui_timetracker | Resolved here |
|-------------------------------|---------------|
| Heartbeat injected by `theme_gemui_page_init()` | Own hook `classes/hook/output/before_http_headers.php` (db/hooks.php) injects `local_trackmytime/timetracker` on `mod-*-view` |
| Performance page lang strings in theme pack | Own `lang/en/local_trackmytime.php` |
| Heatmap AMD `theme_gemui/performance` | Dropped — heatmap is server-rendered (title-attr tooltips, no JS) |
| Styling in theme `_performance.scss` | Own `styles.css` (compiled, neutral, scoped to `body.local-trackmytime-performance`) |
| Reads theme setting `theme_gemui/showstudyheatmap` | Own `local_trackmytime/showstudyheatmap` setting |

## File map
```
local/trackmytime/
├── version.php
├── settings.php                 # enabletracking, showstudyheatmap
├── lib.php                      # thin (injection is via db/hooks.php)
├── db/
│   ├── install.xml              # 1 table: trackmytime (dead gemui_performance dropped)
│   ├── services.php             # local_trackmytime_record_session
│   └── hooks.php                # registers before_http_headers listener
├── classes/
│   ├── external/record_session.php     # writes {trackmytime}
│   ├── hook/output/before_http_headers.php  # SELF-INJECTS the heartbeat
│   └── privacy/provider.php
├── amd/src/timetracker.js       # heartbeat client (+ build/*.min.js)
├── pages/performance.php        # server-rendered performance page
├── templates/local_trackmytime/performance.mustache
├── lang/en/local_trackmytime.php
└── styles.css
```

## The key fix — self-injected heartbeat
`before_http_headers` callback: skip if `enabletracking === '0'`, skip guests, require pagetype `mod-*-view` + `id` (cmid) > 0, then `$PAGE->requires->js_call_amd('local_trackmytime/timetracker', 'init', [$cmid])`. The AMD calls `local_trackmytime_record_session` every 60s (pauses on hidden tab, beacon on unload).

## Data sources
- Sessions: `{trackmytime}`.
- Grades: `grade_item::fetch_course_item()` + `grade_grade` (correct core API — the original's `grade_get_course_grade()` bug is not present here).
- Badges: `{badge_issued}` + `{badge}`.

## AMD build
`amd/build/timetracker.min.js` kept in sync manually (terser). Re-minify after editing src.

## Deploy
Copy to `<moodle>/local/trackmytime/`, Site admin → Notifications (creates the table), `purge_caches.php`.

## v1.1.0 — reporting API
- File: `classes/reporting.php` (`\local_trackmytime\reporting`), all-static.
- Reads only `{trackmytime}`; every method guards with a cached `table_exists()` check.
- Windowing helper builds `AND timestart >= ? AND timestart < ?` fragments (nullable bounds).
- `time_series` and `usage_heatmap` bucket in PHP (recordset) for DB portability + tz fidelity;
  aggregate methods (`*_time`, `course_rankings`) use SQL `SUM/GROUP BY`.
- No new tables, tasks, services, capabilities or caches. Version 2026070800 / release 1.1.0.
