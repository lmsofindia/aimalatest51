# EdzCorp — change set, 22 Jul 2026

Four items. Two are bug fixes to `theme_edzcorp`; one enhances the front-page
"Popular topics" section; one is a brand-new leaderboard block.

---

## #1 — "Turn editing on" bounced to the home page (fixed)

**File:** `theme/edzcorp/layout/columns2.php`

**Cause.** When Moodle's own `edit_mode_link()` returns empty (it does on some
page loads — hence the intermittent "sometimes"), the theme built a fallback
edit-toggle link **hardcoded to `/course/view.php?id=<page course>`**. On
`/my/indexsys.php` (and other non-course pages) the page's course is the site
itself (id 1), and Moodle treats `/course/view.php?id=1` as the site home — so it
redirected you to the front page.

**Fix.** The fallback now toggles editing on `$PAGE->url` (the page you are
actually on) instead of a hardcoded course URL. No visual change.

---

## #3 — `Data & Science` showed as `Data &amp; Science` (fixed)

**File:** `theme/edzcorp/templates/frontpage.mustache`

**Cause.** In `lib.php`, course/category names go through `format_string()`, which
correctly turns `&` into `&amp;` (HTML-safe). The template then printed them with
double-brace `{{name}}`, which HTML-escaped a **second** time → `&amp;amp;`, shown
as `&amp;`.

**Fix.** Values that are already `format_string()` output are now printed with
triple-brace `{{{ }}}` (the standard Moodle idiom): the course card name, the
category tag, and the popular-topic name. Plain text (teacher name via `fullname()`)
stays double-brace.

---

## #4 — Popular topics: source + card layout (new options)

**Files:** `settings.php`, `lib.php`, `templates/frontpage.mustache`,
`scss/_frontpage.scss`, `lang/en/theme_edzcorp.php`, `version.php`

Two new settings under *Front page → Topics & recently launched courses*:

- **Topics source** — All top-level categories (default, = old behaviour) /
  All categories (incl. sub-categories) / One specific category (shown as a single
  featured tile; pick the category in the new "Specific category" selector).
- **Topics display style** — Buttons (pills, default) / Cards (icon tiles), with a
  **Cards per row** option (6 or 8 on wide screens, fewer on smaller screens).

Card style uses a curated set of Font Awesome icons with light pastel colour chips,
auto-assigned per card — no per-category setup. Cards render as light tiles on the
existing dark topics band so they pop.

**Bumps `version.php` (v1.6.9)** because settings.php changed — run the Moodle
upgrade after deploying.

---

## #2 — New block: EdzLeaderboard (`block_edzleaderboard`)

A site-wide leaderboard driven by Level Up! (block_xp). Two selectable layouts
(vertical podium+list / horizontal split), current-user highlight, graceful
fallback when Level Up! is absent. See `blocks/edzleaderboard/readme.md` for full
detail. Points are all-time (cumulative); weekly windows are a documented
fast-follow. The XP read is isolated in one provider so the source can be swapped
later without touching the rest of the block.

---

## Deploy order (per MoodleCoding checklist)

1. Copy `theme/edzcorp/` and `blocks/edzleaderboard/` into the Moodle tree.
2. Clear **PHP opcache** (restart PHP or hit an `opcache_reset()` script) — Moodle's
   purge does **not** clear it.
3. `php admin/cli/upgrade.php --non-interactive` (picks up both version bumps:
   theme settings + new block install).
4. `php admin/cli/purge_caches.php` (recompiles SCSS + Mustache).
5. Hard-refresh (Cmd/Ctrl+Shift+R).
6. Add the EdzLeaderboard block to the Default Dashboard and choose a layout.

## Quick test checklist

- [ ] `/my/indexsys.php` → Turn editing on → stays on the dashboard (no bounce home).
- [ ] Create a course named `Data & Science` → front page "Recently launched" shows
      `Data & Science` (single ampersand).
- [ ] Front page → set Topics style = Cards → 6/8 tiles per row with icons.
- [ ] Topics source = One specific category → single featured tile for the chosen category.
- [ ] Add EdzLeaderboard block (vertical + horizontal) → top 3 + list, "YOU" highlight.
- [ ] Temporarily with block_xp absent → block shows the friendly fallback message.
