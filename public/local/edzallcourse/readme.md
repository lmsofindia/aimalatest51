# EDZ Catalogue (`local_edzallcourse`)

A branded, high-performance course catalogue for Moodle that replaces `/course/index.php` with a category **drilldown** browsing experience. Built on Moodle core course APIs (cache-aware, access-correct), with an external-API + AMD front end. One codebase serves multiple brands (AIMA / GAIL) through language strings and a terminology helper.

- **Type:** local plugin
- **Component:** `local_edzallcourse`
- **Requires:** Moodle 4.4+ (5.x compatible)
- **Maturity:** stable
- **Author:** EDZLMS

---

## Features

- Hybrid **drilldown navigation**: root programmes in a left rail → stacked chip rows per level → recursive course grid.
- Recursive or direct category scope (admin setting).
- Debounced **text search**, **sort** (popular / newest / A–Z / start date), and **numbered server-side pagination**.
- **Deep-linkable** URL state (category / page / search / sort); server-rendered first paint (SEO + works without JS).
- Enrolment-aware course cards; image or generated gradient fallback; primary teacher via the site's course-contact roles.
- Per-card **edit pencil** for users with `moodle/course:update`.
- No custom database tables; derived counts cached in MUC.

---

## Installation

1. Copy this folder to `MOODLE/local/edzallcourse/`.
2. Site admin → **Notifications** → run the upgrade.
3. `php admin/cli/purge_caches.php`.
4. Visit `/local/edzallcourse/index.php`.

> If upgrading from the legacy version, the component name is unchanged, so this installs as an in-place upgrade. There are no database tables to migrate.

## AMD build

`amd/build/*.min.js` must stay in sync with `amd/src/*.js`. After any JS change:

```bash
npx grunt amd --root=local/edzallcourse   # or: terser amd/src/catalogue.js -c -m -o amd/build/catalogue.min.js
php admin/cli/purge_caches.php
```

---

## Configuration

Site admin → Plugins → Local plugins → **EDZ Catalogue**:

| Setting | Default | Meaning |
|---|---|---|
| Enable | On | Master switch. |
| Courses per page | 12 | Page size. |
| Default sort | Most popular | Initial sort order. |
| Category scope | Recursive | Recursive (incl. sub-categories) or Direct only. |
| Show teacher / start date / summary | On | Card fields. |
| Take over course index | On | Redirect `/course/index.php` to the catalogue. |
| Hero title / text | (localised default) | Header overrides. |

---

## Capabilities

- `local/edzallcourse:view` — view the catalogue (guest/user/student/teacher/manager by default).
- `local/edzallcourse:manage` — reserved for management affordances.

---

## Uninstall

Site admin → Plugins → Plugins overview → **EDZ Catalogue** → Uninstall. No tables to drop; settings are removed automatically.

---

## Changelog

- **2025072900 (redesign)** — Full rebuild: hybrid drilldown navigation, core-API data layer, external API + AMD front end, recursive scope setting, numbered pagination, text search + sort, URL state, privacy provider, terminology helper. Removed legacy `ajax.php` / `subcat_ajax.php` / orphan mega-menu / raw-SQL service.
- **2025072814 (legacy)** — Original catalogue (radio → sub-category dropdown → grid).
