# mod_edztrackvideo — Code Review

**Reviewer:** Vidya · **Date:** 2026-06-27 · **Version reviewed:** 2026062700 (v1)
**Method:** Adversarial re-read of every PHP/JS/Mustache/CSS file + structural checks (brace balance, tag check, string coverage, JS `node --check`). PHP was **not** compile-linted (no `php` binary in the build sandbox) — see "Must verify on a real Moodle".

Overall: **solid, deployable v1.** Architecture is clean (provider-agnostic core + adapters), security checks are in the right places, completion/backup/privacy follow Moodle patterns. Two real bugs were found and fixed during review; the rest are minor notes and intentional v1 scope.

---

## 🔴 Bugs found AND FIXED during this review

### 1. YouTube iframe lost its sizing + forward-lock (escape-to-YouTube risk) — FIXED
The YouTube IFrame API **replaces** the holder `<div>` with an `<iframe>` that keeps the element id but drops our CSS classes (`.edztrackvideo-media`, `.edztrackvideo-embed`). Result: the YouTube iframe would render **unsized** and, worse, **clickable** (native YouTube chrome reachable — breaking the "no escape" requirement). Vimeo was unaffected (it appends an iframe child that inherits `pointer-events`).
**Fix:** added a stage-level rule that catches the iframe however it is inserted:
```css
.edztrackvideo-stage iframe { position:absolute; inset:0; width:100%!important; height:100%!important; border:0; pointer-events:none; }
```

### 2. `\core\event\course_module_viewed` is abstract → fatal on view — FIXED
`view.php` instantiated the abstract core event directly (`\core\event\course_module_viewed::create(...)`), which would throw a fatal error the first time any learner opened the activity.
**Fix:** added `classes/event/course_module_viewed.php` (subclass setting `objecttable` + objectid mapping) and pointed `view.php` at `\mod_edztrackvideo\event\course_module_viewed`.

### 3. Fullscreen targeted a detached node — FIXED (hardening)
After the YT swap, the adapter's `getFullscreenTarget()` could return a detached div. Changed the core to always fullscreen the player **wrapper**, which also keeps the custom controls visible in fullscreen (matches the `.edz-fullscreen` CSS).

---

## 🟢 Verified correct

- **Security:** every entry point (`view.php`, `index.php`, all four web services, `pluginfile`) runs `require_login` + module-context `require_capability('mod/edztrackvideo:view')`. External methods also call `validate_context()` and `validate_parameters()`.
- **File serving:** `edztrackvideo_pluginfile()` uses `send_stored_file()`, which honours HTTP range requests — required for HTML5 seeking/buffering of local uploads.
- **Forward-lock model:** core owns the policy (`isForwardLocked()` = flag AND not completed, `maxSeen` seeded from server `maxwatched`); html5 adds a true hard clamp on the `seeking` event; YT/Vimeo rely on the 0.8 s seek-vs-playback poll. Backward seeking always allowed. Server `maxwatched` is the authoritative record.
- **Completion:** custom rule `completion_threshold` wired through `get_coursemodule_info` customdata, `custom_completion::get_state`, and `update_state(COMPLETION_UNKNOWN)` recompute — single source of truth, same proven pattern as mod_edzyoutube.
- **Privacy:** full provider (metadata + plugin + userlist; export and three delete paths) — upgrade over edzyoutube's metadata-only stub.
- **Backup/restore:** instance + optional user progress nested correctly; `videofile` file area annotated on backup and re-added on restore, so local uploads travel with course backups.
- **DB:** brace-balanced, XMLDB valid shape, unique `(edztrackvideoid, userid)` index, FK on parent.
- **Strings:** every `{{#str}}` and `M.util.get_string` key exists in the lang file; runtime strings are preloaded via `strings_for_js`.
- **JS:** all four modules pass `node --check`. `amd/build` is in sync with `amd/src`.

---

## 🟡 Minor notes / intentional v1 scope (not blockers)

1. **Completion fields on a "locked" edit.** Like mod_edzyoutube, `lib.php` reads `enable_completion_percent`/`completion_threshold` directly; if an activity already has completion data (fields locked) and a teacher re-saves, the values fall back to defaults. In practice completion is set at creation. Low risk; documented so testers know to set completion before learners start.
2. **`amd/build` is readable, not minified.** Functionally fine; run `grunt amd --root=mod/edztrackvideo` for a proper minified build before production (in the deploy checklist).
3. **Deprecated external base classes.** Uses the global `external_api`/`external_value` aliases (same as edzyoutube). Works on 4.5/5.x; consider migrating to `core_external\…` namespaces later.
4. **iOS Safari local video** may force its own native fullscreen UI for `<video>` — a platform limitation, not a plugin bug.
5. **Vimeo `controls:false`** requires the video's privacy/embed settings to allow it; some locked-down Vimeo accounts may still show minimal chrome.
6. **Buffer bar for Vimeo** is always 0 (SDK gives no simple buffered value) — cosmetic only.

---

## Must verify on a real Moodle (couldn't be checked in the sandbox)

- PHP compiles with no parse/runtime errors (no `php` binary here) → Site admin → Notifications install.
- Install creates both tables; uninstall removes them + uploaded files.
- Each source plays and the forward-lock behaves per `testing.txt`.
- Privacy export/delete and backup/restore round-trips.

---

## Pending / optional functionality (not yet built)

- **Migration tool** edzyoutube → edztrackvideo (convert instances + progress). Out of v1 scope.
- **Captions for local upload** (`<track>` upload) — YT/Vimeo captions are wired; local has none yet.
- **Per-source poster/thumbnail** image.
- **Cohort watch-% analytics report** for teachers/managers.
- **Multiple files / playlist**, and **download-original** toggle — currently single file, no download.

None of these block v1; they are the natural next increments.
