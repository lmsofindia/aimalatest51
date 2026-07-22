# local_reportpanel — Reports Hub

A professional reports landing page for EDZLMS (Moodle 5.x). One grid of cards links
to leaderboards, site/log reports, team reports, and three custom report builders
(quiz, certificate, consolidated user) — with role-aware, self-service access.

## Install

1. Copy this folder to `MOODLE/local/reportpanel/`.
2. Site admin → **Notifications** → run the install.
3. `php admin/cli/purge_caches.php`.
4. Visit `/local/reportpanel/index.php`.

## Configure

Site admin → Plugins → Local plugins → **Report panel**:
- Panel heading text.
- Card URLs (leaderboard, site reports, log report, team reports, badges) — relative
  defaults are pre-filled; repoint them to match your site.
- Enable/disable each external card.

Defaults shipped:

| Card | Default URL |
|---|---|
| Leaderboard | `/blocks/xp/index.php/ladder/1` |
| Site reports | `/report/lmsace_reports/index.php` |
| Log report | `/report/log/index.php?id=0` |
| Team reports | `/local/edzteams/index.php` |
| Badges | `/badges/mybadges.php` |

## Roles

- **Admins** get the full experience automatically.
- Create an **HR Manager** role to give non-admins the full, all-user reports — see
  `hrmanager.md` for the exact steps and capabilities.
- All other logged-in users get self-service: they see only their own data.

## Surface the panel in the menu

The plugin adds a "Reports Hub" node to flat navigation. To pin it elsewhere, add a
custom menu item (Site admin → Appearance → Advanced theme settings → Custom menu
items):

```
Reports Hub|/local/reportpanel/index.php
```

or surface it from `theme_edzcorp`'s sidebar.

## Uninstall

Site admin → Plugins → Plugins overview → Report panel → Uninstall. The plugin
creates no DB tables and stores no user data, so uninstall leaves no residue.

## Changelog

- **1.0.0** — Initial release: 8-card panel; quiz, certificate and consolidated user
  reports (full + self modes); PDF/CSV export; configurable card URLs; HR Manager
  role guide.

- **1.2.0** — Added the **Time & engagement** report (Domain A): date-range KPIs, daily
  trend, top courses by time (CSV), and a weekly usage heatmap. Sources data from
  `local_trackmytime` via its reporting API. New setting `activedays` (default 7).
  Requires `local_trackmytime` 1.1.0+ for time data (degrades gracefully without it).

- **1.3.0** — Added **Rankings** (top courses by enrolment/completions/time, most-active
  learners) and **Site overview** (KPIs, trends, registration + enrolment-health tables).
  Site overview **replaces** the external LMSACE "Site reports" card — that external card
  and its URL setting are retired. New shared `helper/analytics.php`. Both full-mode only.

- **1.3.3** — UI polish: ranking + top-course lists now render as lightweight CSS meter
  bars (no Chart.js sizing quirks; a 1-row block looks like a 50-row one). Chart sizing
  CSS made global (charts no longer overflow their card). Shared filter-toolbar styling.
  Colour palette per visual: trend lines blue, bars purple, ranking blocks
  blue/green/purple/teal, heatmap green.

- **1.4.0** — Quick-win UI/UX pass + Domain E. Drill-down links (ranking/overview names
  → course & user reports), period-over-period KPI deltas (▲/▼), thousands separators,
  auto-apply filters (AMD) with last-filter memory, consistent subheader (range chip +
  "as of"), CSV export on all report pages, responsive heatmap/filters, and hub cards
  grouped into "Manager reports" vs "My reports". Domain E: per-activity coverage table
  on the course consolidated report (completion done/overdue, avg grade, avg time, and
  assignment submission stats) — generic across module types, assignment specialised.
