# Track My Time (local_trackmytime)

Theme-independent time-on-task tracking + a learner Performance page for Moodle. Works on stock Boost or any theme — the plugin injects its own tracking heartbeat, so it does not depend on any theme.

Standalone fork of `local_gemui_timetracker` (which only worked under the GemUI theme). The two can coexist; they use different tables.

## Requirements
- Moodle 4.5+ (tested on 5.x), PHP 8.1+

## Install
1. Copy to `<moodle>/local/trackmytime/`.
2. Site admin → Notifications (creates the `trackmytime` table).
3. `php admin/cli/purge_caches.php`.

## Configure
Site admin → Plugins → Local plugins → Track My Time:
- **Enable time tracking** (default ON)
- **Show study heatmap** (default ON)

## Use
- Performance page: `/local/trackmytime/pages/performance.php`
- Tracking runs automatically on activity view pages once enabled.

## Uninstall
Site admin → Plugins → uninstall Track My Time (drops the `trackmytime` table).

## Changelog
- **1.0.0** — Initial standalone release. Self-injected heartbeat (no theme dependency); internalised performance-page strings/CSS; dropped dead aggregate table and dashboard-only web services; own settings.

## Changelog
- **1.1.0** — Added `\local_trackmytime\reporting` public API for cross-plugin time
  analytics (consumed by local_reportpanel's Time & engagement report). No schema change.

- **1.2.0** — Added `reporting::module_time_totals($cmids, $from, $to)` (bulk per-module
  time) for local_reportpanel's activity-coverage report. No schema change.
