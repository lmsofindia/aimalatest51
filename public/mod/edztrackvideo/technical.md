# mod_edztrackvideo — Technical Design

**Component:** `mod_edztrackvideo`
**Version:** `2026062700` · requires `2024100700` (Moodle 4.5+)
**Frankenstyle prefix:** `mod_edztrackvideo` (DB tables `edztrackvideo`, `edztrackvideo_progress`)

---

## 1. Architecture overview

The plugin keeps the proven `mod_edzyoutube` server design (single main table + per-user progress, `progress_manager`, custom completion, three AJAX endpoints) and adds:

1. **A `sourcetype` discriminator** (`youtube` | `vimeo` | `upload`) on the main table.
2. **A Moodle file area** (`videofile`) for local uploads + a `pluginfile` callback to serve them.
3. **A provider-agnostic JS player core** with three pluggable adapters.
4. **Speed-policy fields** (`allow_speed`, `max_speed`).

The single most important refactor relative to `mod_edzyoutube` is on the client: the YouTube-specific `player.js` is split into a **core** (UI, tracking, lock, resume, completion reporting, speed policy) and **adapters** that all expose the same small interface. The core never references YouTube/Vimeo/HTML5 APIs directly.

---

## 2. Database schema (`db/install.xml`)

### Table `edztrackvideo`
| Field | Type | Notes |
|---|---|---|
| id | int(10) PK seq | |
| course | int(10) NOT NULL | |
| name | char(255) NOT NULL | |
| intro | text NULL | |
| introformat | int(4) NOT NULL | |
| sourcetype | char(16) NOT NULL DEFAULT 'youtube' | youtube \| vimeo \| upload |
| externalurl | text NULL | original YouTube/Vimeo URL (empty for upload) |
| videoid | char(64) NULL | extracted YouTube id or Vimeo numeric id |
| videohash | char(64) NULL | Vimeo private hash (unlisted videos), else empty |
| enable_completion_percent | int(1) NULL DEFAULT 0 | |
| completion_threshold | int(4) NOT NULL DEFAULT 100 | 1–100 |
| no_forward_seek_first_view | int(1) NOT NULL DEFAULT 1 | |
| allow_speed | int(1) NOT NULL DEFAULT 0 | |
| max_speed | number(4,2) NOT NULL DEFAULT 1.00 | 1.00 / 1.25 / 1.50 / 2.00 |
| timecreated | int(10) NOT NULL | |
| timemodified | int(10) NOT NULL | |

Local file is NOT a DB column — it lives in the file area `mod_edztrackvideo / videofile / 0` in the module context.

### Table `edztrackvideo_progress`
| Field | Type | Notes |
|---|---|---|
| id | int(10) PK seq | |
| edztrackvideoid | int(10) NOT NULL | FK → edztrackvideo.id |
| userid | int(10) NOT NULL | |
| maxwatched | number(10,2) NOT NULL DEFAULT 0 | high-water mark (s) |
| last_position | number(10,2) NOT NULL DEFAULT 0 | |
| duration | number(10,2) NOT NULL DEFAULT 0 | |
| first_view_done | int(1) NOT NULL DEFAULT 0 | |
| completed | int(1) NOT NULL DEFAULT 0 | internal mirror of completion |
| dismissed | int(1) NOT NULL DEFAULT 0 | resume dismissed |
| lastupdate | int(10) NOT NULL DEFAULT 0 | |
| last_viewed_on | int(10) NULL | |

Unique index `(edztrackvideoid, userid)`; foreign key on `edztrackvideoid`.

> `dismissed` is a real column here (mod_edzyoutube tracked it only via timestamp). Cleaner and avoids re-showing resume after explicit dismissal.

---

## 3. PHP class & file map

```
mod/edztrackvideo/
├── version.php
├── mod_form.php                         # source selector + filemanager + speed + completion rules
├── view.php                             # student view; resolves source -> player config
├── lib.php                              # add/update/delete, supports, completion hooks,
│                                        #   video-id extraction (YT+Vimeo), pluginfile serving,
│                                        #   get_coursemodule_info, completion rule descriptions
├── index.php                            # course-level listing of instances
├── styles.css                           # redesigned player CSS (theme-var aware)
├── classes/
│   ├── external.php                     # get_progress / update_progress / mark_dismissed / get_config
│   ├── progress_manager.php             # progress read/update + completion recompute
│   ├── source_resolver.php             # builds the per-source player payload (url, type, ids, file url)
│   ├── completion/custom_completion.php # watchpercent rule
│   ├── privacy/provider.php             # full export+delete provider
│   └── output/renderer.php              # render_view -> mustache
├── db/
│   ├── install.xml
│   ├── access.php                       # capabilities
│   ├── services.php                     # 4 WS functions
│   └── upgrade.php                      # (empty baseline for v1)
├── amd/
│   ├── src/
│   │   ├── player.js                    # CORE: UI, tracking, lock, resume, completion, speed
│   │   └── adapters/
│   │       ├── youtube.js               # YouTube IFrame API adapter
│   │       ├── vimeo.js                 # Vimeo Player SDK adapter
│   │       └── html5.js                 # native <video> adapter (true hard lock)
│   └── build/ (mirrors src, minified)
├── templates/view.mustache
├── lang/en/edztrackvideo.php
├── pix/ (icon, monologo)
└── backup/moodle2/                      # backup + restore activity tasks/steplibs
```

---

## 4. JS core ↔ adapter interface

The core (`player.js`) constructs the right adapter from `config.sourcetype` and talks to it ONLY through this contract. Each adapter is an AMD module exporting a factory `create(holderEl, cfg, callbacks)` returning an object:

```
adapter.ready: Promise            // resolves when media is loaded & duration known
adapter.play()                    // start playback
adapter.pause()
adapter.getCurrentTime() -> sec
adapter.getDuration() -> sec
adapter.seekTo(sec)               // core only calls within allowed range
adapter.clampForward(maxSec)      // html5: install hard seeking clamp; yt/vimeo: no-op (core handles correction)
adapter.setVolume(0..100)
adapter.mute() / unmute() / isMuted()
adapter.setRate(rate)             // capped by core before calling
adapter.getAvailableRates() -> []
adapter.toggleCaptions(on) -> bool
adapter.requestNativeFullscreenTarget() -> el|null
adapter.destroy()
```

Callbacks the core passes in: `onStateChange(state)`, `onTimeUpdate(sec)` (html5 fires natively; yt/vimeo also polled), `onEnded()`, `onError(e)`.

**Forward-lock division of labour**
- Core owns the *policy*: `isForwardLocked()` = flag on AND not completed; `maxSeen` high-water mark seeded from server `maxwatched`.
- Core's 0.8s poll does seek-vs-playback detection for ALL sources (kept from edzyoutube) and pulls back YouTube/Vimeo.
- `html5.js` additionally listens to the native `seeking` event and clamps `video.currentTime = Math.min(target, maxSeen + 0.9)` while locked — instant, no visible jump.

**Speed policy**
- Core computes `effectiveRates`: if `!allow_speed` → `[1.0]` and the speed control is hidden; else rates `[0.75,1,1.25,...] filtered to ≤ max_speed`. Core never calls `setRate` above `max_speed`.

---

## 5. AJAX / external API (`classes/external.php`, `db/services.php`)

Identical contract to edzyoutube, renamed + one addition:

| Function | Type | Args | Returns |
|---|---|---|---|
| `mod_edztrackvideo_get_progress` | read | edztrackvideoid | last_position, maxwatched, duration, lastupdate, last_viewed_on, completed, show_resume, no_forward_seek_first_view |
| `mod_edztrackvideo_update_progress` | write | edztrackvideoid, current_time, duration | status, last_position, maxwatched, completed |
| `mod_edztrackvideo_mark_dismissed` | write | edztrackvideoid | status |
| `mod_edztrackvideo_get_config` | read | edztrackvideoid | full player config (sourcetype, ids, fileurl, flags, speed) — used by the numeric-id fallback init path |

All `ajax => true`, capability `mod/edztrackvideo:view`, `require_login` + `validate_parameters`. `update_progress` recomputes completion through the Completion API (single source of truth) exactly as edzyoutube did.

---

## 6. Source resolution (`classes/source_resolver.php`, used by `view.php`)

Produces the JS `config` object:

- **youtube** → `{sourcetype:'youtube', videoid, posterurl:null}`
- **vimeo** → `{sourcetype:'vimeo', videoid, videohash}`
- **upload** → resolve the stored file via `get_file_storage()`, build a `moodle_url` via `moodle_url::make_pluginfile_url(context, 'mod_edztrackvideo', 'videofile', 0, '/', filename)`, return `{sourcetype:'upload', fileurl, mimetype}`.

`lib.php::mod_edztrackvideo_pluginfile()` serves the file with `require_login`, context + capability check, `send_stored_file()`. Supports HTTP range requests (Moodle's `send_stored_file` handles ranges) so HTML5 seeking/buffering works.

### Server file + Direct URL sources (added v1.1, version 2026062701)
- **Admin settings** (`settings.php`): `videobasedir` (`admin_setting_configdirectory`) and `enable_directurl` (`admin_setting_configcheckbox`). Read via `get_config('mod_edztrackvideo', …)`.
- **DB:** new `serverpath` char(255) column (relative path under `videobasedir`). Added in `install.xml` and migrated in `upgrade.php` (`if ($oldversion < 2026062701)`).
- **`sourcetype`** now also accepts `serverfile` and `directurl`. The form only offers `serverfile` when `videobasedir` is set, and `directurl` when `enable_directurl` is on; `lib.php::edztrackvideo_prepare_record()` re-checks `enable_directurl` server-side and falls back to youtube if it was disabled.
- **`serverfile`** picker = a `select` populated by `edztrackvideo_list_server_files()` (recursive scan, video extensions, capped at 500). Stored value validated by `edztrackvideo_resolve_server_file()` which does a `realpath()` confinement check (rejects `..` and anything outside the base dir) — used in form validation, on save, and in `serve.php`.
- **Serving:** `serve.php?id=<cmid>` resolves the instance's `serverpath`, runs `require_login` + `require_capability('mod/edztrackvideo:view')`, then `send_file()` (byte-serving / HTTP range, so seek/resume work). The filename is never taken from the request — only from the stored instance — so users cannot request arbitrary paths.
- **`directurl`** stores the URL in `externalurl`; `source_resolver` returns it directly as `fileurl` (no Moodle access gate — documented trade-off).
- **Player:** `serverfile`/`directurl`/`upload` all map to the `html5` adapter (true hard lock). Renderer/template use `is_filevideo` (`<video>`) vs `is_embed` (iframe).
- **Backup:** `serverpath` added to the backup nested element (the relative path travels; the actual server file is environment-specific and must exist on the target server).

### Video-ID extraction (`lib.php`)
- YouTube: reuse edzyoutube regex set (`youtu.be/`, `v=`, `embed/`, `shorts/`).
- Vimeo: `vimeo.com/(\d+)(?:/([0-9a-z]+))?`, `player.vimeo.com/video/(\d+)`, capture optional private hash.

---

## 7. Completion (`classes/completion/custom_completion.php` + `lib.php`)

Same design as edzyoutube: single custom rule `completion_threshold` ("watch ≥ X%"). `get_state` reads `edztrackvideo_progress`, computes `maxwatched/duration*100 ≥ threshold`. `lib.php` exposes the rule via `get_coursemodule_info` customdata and `mod_edztrackvideo_get_completion_active_rule_descriptions`.

---

## 8. Privacy (`classes/privacy/provider.php`)

Implements `metadata\provider`, `core_userlist_provider`, `plugin\provider`. Declares the `edztrackvideo_progress` table fields. Exports per-user progress rows in `get_users_in_context` / `export_user_data`; deletes in `delete_data_for_*`. (edzyoutube only declared a metadata string — this is an upgrade.)

---

## 9. Backup / restore (`backup/moodle2/`)

Standard activity backup: `backup_edztrackvideo_stepslib` (nestes the instance + optional user progress in the `userinfo` branch), `restore_edztrackvideo_stepslib`, plus the two activity task classes. **File area `videofile` is annotated** so local uploads travel with course backups.

---

## 10. Build phases

| Phase | Scope | State |
|---|---|---|
| P1 | Spec docs | this commit |
| P2 | Scaffold: version, install.xml, access, services, lib (incl. pluginfile + id extraction), mod_form (source selector + filemanager + speed), view, lang | — |
| P3 | JS core + 3 adapters + redesigned mustache + styles.css + amd build sync | — |
| P4 | external.php, progress_manager, source_resolver, custom_completion, privacy provider, renderer, backup/restore | — |
| P5 | Lint/verify, deploy checklist | — |

---

## 11. Hard-won-lesson checklist applied

- **Moodle mod mount is read-only via Read/Write tools** for some setups → all file writes verified via bash where needed; this folder is freshly created so Write works.
- **AMD build is not auto-generated** → `amd/build/*.js` written as readable (non-mangled) copies of `src` in v1 so the plugin runs without grunt; a real `grunt amd` pass is listed in the deploy checklist.
- **Purge caches after deploy** (opcache + Moodle caches) — in checklist.
- **`send_stored_file` for range requests** so HTML5 video seeks.
- **Completion recompute via `completion_info::update_state(COMPLETION_UNKNOWN)`** — not manual writes.
- **Privacy provider required** — full provider, not null.

---

## 12. Future (out of v1 scope)
- Migration tool: convert `mod_edzyoutube` instances + progress → `edztrackvideo`.
- Captions upload (`<track>`) for local video.
- Per-source poster/thumbnail.
- Analytics report of cohort watch-percentage.
