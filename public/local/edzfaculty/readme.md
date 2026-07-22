# local_edzfaculty — Faculty / Teacher Dashboard

An action-first faculty dashboard for the AIMA academic Moodle instance (reusable on GAIL via terminology config). Surfaces grading, discussions, at-risk students, live classes, exams, per-course stats and quick actions in one command centre.

Built by **Vidya** (EDZLMS Moodle plugin agent). Phase 1.

## Requirements
- Moodle 5.0+ (Boost / edzcorp theme), PHP 8.1+.
- Optional integrations (feature-detected): `mod_attendance`, `mod_bigbluebuttonbn` or `mod_zoom`, `mod_forum`, `mod_assign`, `mod_quiz`.

## Install
1. Copy this folder to `MOODLE_ROOT/local/edzfaculty/`.
2. Site admin → **Notifications** → run the install (creates the two cache tables).
3. `php admin/cli/purge_caches.php`.
4. Prime the cache once:
   `php admin/cli/scheduled_task.php --execute="\local_edzfaculty\task\refresh_cache"`
5. Assign capabilities if your teacher role doesn't already have them (see below).
6. Visit `/local/edzfaculty/index.php` as a teacher.

## Capabilities
- `local/edzfaculty:view` — granted to `editingteacher` + `teacher` by default (db/access.php).
- `local/edzfaculty:viewall` — `manager`.
- `local/edzfaculty:managesettings` — `manager`/admin.

## Configuration
Site admin → Plugins → Local plugins → **Faculty Dashboard**:
- Terminology (academic / corporate)
- At-risk thresholds & weights
- Cache TTL + manual-refresh throttle
- Live-class source
- Show AI features toggle

## Uninstall
Site admin → Plugins → uninstall `local_edzfaculty`. Drops both cache tables (see db/uninstall.php). No core data is touched.

## Changelog
- **0.2.0** — Phase 2 + polish: one-click **nudge** messaging to at-risk students (message provider + AJAX + student-360 nudge box); **Zoom attendance auto-sync** from mod_zoom participant reports (new `attendancesource` setting: auto / mod_attendance / Zoom / off; `zoomminminutes` threshold); access + "viewing as" notices; Esc-to-close and focus handling on the modal; XSS-escaped modal output.
- **0.1.0** — Phase 1: scaffold, cache tables + scheduled task, capabilities, settings, full dashboard (Course Focus, triage worklist, timeline, courses, insight), AJAX endpoints, AMD, privacy provider.

## Roadmap
- 0.3+ — AI grading assist + integrity flags (axis-ai / Ravi), quiz manual-grade precision, instant webhook recordings via local_edzzoom, report export, optional Dashboard blocks.

## Related
Theme/UI polish → **Rupa** (theme edzcorp). Mobile wrapper → **Yatra**.
