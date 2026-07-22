# block_edzleaderboard — EdzLeaderboard

A site-wide leaderboard block for the EdzCorp platform, driven by **Level Up!
(block_xp)**. Two selectable layouts, current-user highlighting, and a graceful
fallback when Level Up! is not installed.

## What it shows

- **Top 3** highlighted (podium in vertical, coloured panel in horizontal) with
  gold / silver / bronze medals and a crown on #1.
- **Ranks 4 → N** as a compact list (N is configurable, default 10).
- The **current user** highlighted with a "YOU" badge wherever they appear; if
  they are outside the shown list, their own rank is appended as a "Your rank" row.

## Layouts (per-block setting)

- **Vertical** — podium on top, ranked list below. Good for a sidebar / narrow column.
- **Horizontal** — top 3 on a coloured panel at left, ranks 4→N at right. Wide but
  short, so it does not run down a dashboard.

## Data source

All ranking data comes from Level Up! (`block_xp`), read in one place:
`classes/provider/xp_provider.php`. Site-wide XP is stored by Level Up! under a
single course id (the site course, **id 1**, matching the ladder URL
`/blocks/xp/index.php/ladder/1`). That id is exposed as a site setting
(*Site administration → Plugins → Blocks → EdzLeaderboard*) in case your Level Up!
is configured differently.

Points are cumulative (all-time). Weekly / monthly windows are **not** included in
v1 — Level Up! stores a running XP total, so a time window would require reading
`block_xp_log`. That can be added later behind the same provider without touching
the rest of the block.

### Swapping the source later

The block only ever calls three static methods on the provider:
`is_available()`, `get_ladder($courseid, $limit)`, `get_user_rank($courseid, $userid)`.
To move off Level Up! (native completions, grades, a custom engine…), implement
those three methods against the new source and point the block at it — nothing
else changes.

## Settings

- **Site setting:** `xpcourseid` (default 1) — the Level Up! course id for site-wide XP.
- **Per-block:** title, layout (vertical / horizontal), users to show (3–50).

## Scope & capabilities

- Placeable on the Dashboard (`/my`), the site front page, general pages, and inside
  courses. Rankings are **site-wide** regardless of placement (per the current spec).
- `block/edzleaderboard:addinstance`, `block/edzleaderboard:myaddinstance`.
- Stores no personal data of its own (null privacy provider).

## Install / deploy

1. Copy this folder to `MOODLE/blocks/edzleaderboard/`.
2. Clear PHP opcache, then run `admin/cli/upgrade.php` (new plugin → installs).
3. `admin/cli/purge_caches.php`.
4. Add the block to the Default Dashboard (`/my/indexsys.php` → Turn editing on →
   Add a block → EdzLeaderboard) and pick a layout.

Requires Level Up! (block_xp) for live data; without it the block shows a friendly
"not installed yet" message instead of erroring.
