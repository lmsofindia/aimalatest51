# mod_edztrackvideo — Functionality

**Plugin:** `mod_edztrackvideo` ("EDZ Track Video")
**Type:** Moodle activity module (`mod/`)
**Release:** 1.0 (Build 20260627) · version `2026062700`
**Moodle requirement:** 2024100700+ (Moodle 4.5 / 5.x)
**Maturity:** Beta (v1)
**Supersedes:** `mod_edzyoutube` (single-source YouTube). This is the source-neutral successor.

---

## 1. What this activity does

EDZ Track Video embeds **one video — from YouTube, Vimeo, or a locally uploaded file** — inside a **custom, locked-down player** so that learners watch the video *inside Moodle* with controlled viewing rules. It enforces no forward-skipping on first watch, automatic resume, optional playback-speed lock, and percentage-watched completion tracking.

It is designed for compliance/training scenarios where an organisation must be able to assert that a learner genuinely watched a required percentage of a video — regardless of where that video is hosted.

This is the multi-source successor to `mod_edzyoutube`. The hard-won viewing-control engine (forward-lock, seek-vs-playback detection, max-watched high-water mark, resume, percent completion) is preserved but refactored to be **provider-agnostic**, with thin per-source adapters.

---

## 2. Core features

### 2.1 Three video sources (one per activity)
The teacher picks the **source type** when creating the activity:

| Source | Input | How it plays | Lock strength |
|---|---|---|---|
| **YouTube** | Paste any YouTube URL (`watch?v=`, `youtu.be/`, `embed/`, `shorts/`) — the video ID is auto-extracted | YouTube IFrame API, native chrome hidden | Detect-and-rewind |
| **Vimeo** | Paste any Vimeo URL (`vimeo.com/123…`, `player.vimeo.com/video/123…`, private hash `vimeo.com/123/abcdef`) | Vimeo Player SDK | Detect-and-rewind |
| **Local upload** | Upload a video file (`.mp4`, `.webm`, `.ogv`, `.mov`) via the file picker | Native HTML5 `<video>` | **True hard lock** (seeking ahead is genuinely prevented, not just corrected) |
| **Server file** | Pick from videos an admin placed in a configured server folder (for files too big to upload via the browser) | Native HTML5 `<video>`, streamed through an access-controlled endpoint with HTTP range | **True hard lock** |
| **Direct URL** *(admin-enabled)* | Paste a direct URL to a video file (e.g. a web-served folder) | Native HTML5 `<video>` | **True hard lock** (client-side); file itself is not access-gated by Moodle |

The **Server file** source appears only when an administrator has set a base video folder; **Direct URL** appears only when an administrator has switched it on. Only one source per activity instance. The player UI, controls, tracking, and completion behave identically across all three — only the underlying playback adapter differs.

### 2.2 Contained ("no escape") playback
The video plays through a custom skin instead of the native provider chrome:

- **YouTube / Vimeo** iframes are rendered with native controls disabled and `pointer-events:none`, so the learner cannot click into the provider UI, context menu, "Watch on YouTube/Vimeo", share, or related-video affordances.
- **Local** HTML5 video is rendered with the native `controls` attribute removed and right-click/context-menu and `download` disabled.
- A custom control bar (play/pause, mute, volume, captions where supported, progress bar, speed, fullscreen, time display) sits *outside* the media element and drives playback through the adapter.
- A persistent **watermark** (the activity / plugin name) is overlaid on the player.

### 2.3 First-view forward-seek lock
When **"Do not allow forward seeking in first view"** (`no_forward_seek_first_view`) is enabled (default **on**):

- During the learner's first viewing, they **cannot skip ahead** of the furthest point they have legitimately watched (the "max-watched marker").
- **Backward seeking is always allowed** — learners can rewind and re-watch freely.
- The lock is released automatically once the learner meets the completion threshold (activity becomes complete). After that, free seeking is permitted.
- Enforced client-side AND anchored to server-stored progress, so it survives reloads — a learner cannot reload to reset the unlocked position.
- For **local video** the lock is *true*: the adapter clamps `currentTime` on every `seeking` event so the position can never move past the marker. For YouTube/Vimeo the lock is *detect-and-rewind* (same approach as `mod_edzyoutube`), because those APIs only allow correction after the fact.

### 2.4 Playback-speed policy
Per-activity speed control, defaulting to compliance-safe:

- **Default:** speed locked to **1.0×** — no speed control shown. (Speeding up is a sneaky way to "fast-forward" past a compliance requirement.)
- Teacher can tick **"Allow playback speed control"** and choose a **maximum speed** (`1.25×`, `1.5×`, or `2.0×`). The learner then sees a speed selector capped at that maximum.
- The cap is enforced in the adapter for all three sources.

### 2.5 Resume where you left off
- The learner's last position is stored server-side.
- On returning, if they've watched past a minimum point and are not yet complete, a **Resume** pill appears offering to jump back to roughly where they stopped (a couple of seconds before the last position).
- A **Dismiss** button declines the resume; dismissal is recorded.

### 2.6 Percentage-watched completion
- Completion is driven by a **custom completion rule**: *"Watch at least X% of the video."*
- Teacher enables **"Enable percent-based completion"** and sets a **threshold** (1–100, default 100%).
- Progress tracked by the *maximum* position reached (`maxwatched`) vs `duration`. When `maxwatched / duration × 100 ≥ threshold`, the activity is marked complete.
- Integrates with Moodle's Completion API, activity completion report, course completion, and restrictions, like any standard activity.
- "View" completion (`FEATURE_COMPLETION_TRACKS_VIEWS`) is also supported as an option.

### 2.7 Accurate progress detection
The tracker distinguishes a genuine forward **seek** from natural **playback**:

- Polls playback every ~0.8s and compares position advance against wall-clock elapsed time.
- Natural playback (≈1s of video per 1s real time) advances the high-water mark.
- A jump well beyond elapsed time (more than a 1.5s tolerance) past the furthest-watched point is treated as a forbidden forward seek and is blocked/pulled back, without disrupting normal playback.
- Local HTML5 additionally clamps in the native `seeking` event for an instant, jitter-free hard lock.

---

## 3. New player UI (redesign vs mod_edzyoutube)

The v1 player is a ground-up UI redesign addressing the feedback that the old player felt unpolished:

- **Two-layer progress bar with a max-watched marker.** Watched portion = solid brand colour; the furthest-watched point shows a marker; the locked-ahead region (when forward-lock is active) is rendered greyed/hatched with a small lock glyph, so the rule is *visible* rather than feeling like a bug.
- **Backward scrubbing is free**, forward scrubbing past the marker is refused with a non-blocking toast ("Fast-forward is disabled for this lesson") instead of a silent snap-back.
- **Completion ring / bar** that fills toward the threshold and turns into a checkmark when met — gives the learner a clear goal and payoff.
- **Resume pill** on load ("Resume from 4:12") instead of a silent jump.
- **Cleaner control chrome:** centered hover play/pause, remaining-time + total, mute with volume slider, captions toggle (YouTube/Vimeo where available), speed selector (when allowed), fullscreen.
- **Keyboard support:** space = play/pause, left/down = rewind, right/up = forward (blocked past marker when locked).
- **Mobile-friendly:** tap targets ≥ 44px, responsive control bar, loading skeleton, explicit error state if the video fails to load.
- **Theme-aware:** reads `--bs-primary` / `--bs-body-color` CSS vars so it matches the edzcorp theme instead of hardcoded colours.

---

## 4. Teacher-facing settings (Add/edit activity form)

| Setting | Field | Default | Notes |
|---|---|---|---|
| Name | `name` | — | Required. Activity title. |
| Description | `intro` | — | Standard intro editor; optional display on course page. |
| Video source | `sourcetype` | youtube | Select: YouTube / Vimeo / Upload a file / Server file (if configured) / Direct URL (if enabled). |
| Video URL | `externalurl` | — | Required when source = YouTube, Vimeo, or Direct URL. Accepts the URL formats in 2.1. |
| Video file | `videofile` (filemanager) | — | Required when source = Upload. One file, accepted video mimetypes. |
| Server video file | `serverpath` | — | Required when source = Server file. Dropdown of videos found in the admin folder (incl. subfolders). |

**Site-level admin settings** (Site admin → Plugins → Activity modules → EDZ Track Video):

| Setting | Key | Default | Notes |
|---|---|---|---|
| Server video folder | `videobasedir` | empty | Absolute server path holding large videos (placed via SFTP). Enables the Server file source. A path outside the web root is recommended. |
| Allow Direct video URL source | `enable_directurl` | off | Turns the Direct URL source on/off site-wide. Off by default because such files are not access-controlled by Moodle. |
| Do not allow forward seeking in first view | `no_forward_seek_first_view` | On | First-view forward-seek lock (see 2.3). |
| Allow playback speed control | `allow_speed` | Off | When off, speed is locked to 1.0×. |
| Maximum playback speed | `max_speed` | 1.0 | Select 1.25 / 1.5 / 2.0; enabled only when `allow_speed` ticked. |
| Enable percent-based completion | `enable_completion_percent` | Off | Under Completion conditions. |
| Completion threshold (%) | `completion_threshold` | 100 | 1–100; enabled only when the percent rule is ticked and completion is Automatic. |

---

## 5. Learner-facing behaviour

1. Opens the activity and sees the embedded player with the custom control bar, watermark, and (if returning) a Resume pill.
2. Plays using the on-screen controls. Native provider UI is not clickable.
3. On first view, can rewind freely but cannot jump ahead of the max-watched marker. Forward attempts get a clear toast.
4. Speed selector appears only if the teacher allowed it, capped at the configured max.
5. Once the configured percentage is reached, the activity is marked complete and (if the lock was on) free seeking unlocks; the completion ring shows a checkmark.

---

## 6. Permissions (capabilities)

| Capability | Default roles | Purpose |
|---|---|---|
| `mod/edztrackvideo:addinstance` | Editing teacher, Manager | Add the activity to a course. |
| `mod/edztrackvideo:view` | Student, Teacher, Editing teacher, Manager | View the activity / call progress web services. |
| `mod/edztrackvideo:manage` | Teacher, Editing teacher, Manager | Manage the activity. |

---

## 7. Data & privacy

Stores **per-user watched progress** in `edztrackvideo_progress`: max position, last position, duration, completion flag, dismissed flag, last-viewed timestamp. A full privacy provider is implemented (export + delete, user list). Uploaded video files live in the Moodle file area for the module context. YouTube/Vimeo playback is served by those providers under their own terms; no learner data is sent to them by the plugin beyond what the embed inherently requires.

---

## 8. Known limitations

- Quality picker is intentionally omitted — YouTube/Vimeo auto-manage quality; local serves the uploaded file as-is.
- Captions are best-effort for YouTube/Vimeo (provider-dependent) and use the file's embedded/track captions for local (if a `<track>` is added in a later version).
- Client-enforced controls are a strong deterrent for normal learners but, as with any client control, not a guarantee against a determined technical user; the server-side `maxwatched` high-water mark is the authoritative record. Local video's hard lock is materially stronger than the YouTube/Vimeo detect-and-rewind.
- Large local uploads are bound by the site's `maxbytes` / PHP upload limits and consume server storage + bandwidth.
- Requires learner browser network access to youtube.com / vimeo.com for those sources.

---

## 9. Migration note (mod_edzyoutube → mod_edztrackvideo)

This is shipped as a **separate new module**; existing `mod_edzyoutube` activities are untouched and keep working. A future optional migration tool could convert `edzyoutube` instances (sourcetype=youtube, externalurl=originalurl) and their progress rows into `edztrackvideo`. Out of scope for v1; flagged in `technical.md`.
