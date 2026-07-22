# quizaccess_edproctoring — Technical Specification

**Plugin component:** quizaccess_edproctoring
**Moodle install path:** `<moodleroot>/quizaccess/edproctoring/`
**Phase 1 scope:** Core proctoring — capture, violation detection, trust score, reports, GDPR

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│  STUDENT BROWSER                                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  proctoring_session.js (AMD orchestrator)            │  │
│  │    ├── face_detector.js  (face-api.js TensorFlow)    │  │
│  │    ├── capture_manager.js (interval + burst)         │  │
│  │    ├── tab_monitor.js (Visibility + Fullscreen API)  │  │
│  │    ├── violation_reporter.js (AJAX queue + retry)    │  │
│  │    └── camera_overlay.js (UI indicator)              │  │
│  └──────────────────┬───────────────────────────────────┘  │
│                     │ core/ajax                             │
└─────────────────────┼───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│  MOODLE PHP (quizaccess_edproctoring)                       │
│  ┌──────────────┐  ┌──────────────────┐  ┌──────────────┐  │
│  │ rule.php     │  │ external/        │  │ helper/      │  │
│  │ (access rule)│  │ save_snapshot    │  │ image_store  │  │
│  │              │  │ log_violation    │  │ trust_score  │  │
│  │              │  │ get_session      │  │ violation_   │  │
│  │              │  │ upload_baseimg   │  │ classifier   │  │
│  └──────────────┘  └────────┬─────────┘  └──────┬───────┘  │
│                             │                   │           │
│  ┌──────────────────────────▼───────────────────▼────────┐  │
│  │  Moodle DB + Moodledata File API                      │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                      │ cURL (Phase 2 only)
┌─────────────────────▼───────────────────────────────────────┐
│  FastAPI Microservice (localhost:8765) — Phase 2            │
│  POST /verify  — face match against base image              │
│  POST /detect  — face detection + count (fallback)          │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Database Schema

### 2.1 `quizaccess_edproctoring` — Per-quiz settings

Stores the access rule configuration for each quiz.

```xml
TABLE: quizaccess_edproctoring
  id              BIGINT(10)   NOT NULL AUTO_INCREMENT PRIMARY KEY
  quizid          BIGINT(10)   NOT NULL UNIQUE     -- FK: quiz.id
  enabled         TINYINT(1)   NOT NULL DEFAULT 1
  capture_interval INT(6)      NOT NULL DEFAULT 30  -- seconds
  require_fullscreen TINYINT(1) NOT NULL DEFAULT 0
  detect_tabs     TINYINT(1)   NOT NULL DEFAULT 1
  enable_face_detect TINYINT(1) NOT NULL DEFAULT 1
  enable_face_recog  TINYINT(1) NOT NULL DEFAULT 0  -- Phase 2
  base_img_uploader  TINYINT(1) NOT NULL DEFAULT 0  -- 0=either,1=student,2=admin
  require_consent TINYINT(1)   NOT NULL DEFAULT 1
  auto_submit     TINYINT(1)   NOT NULL DEFAULT 0
  auto_submit_threshold INT(4) NOT NULL DEFAULT 5   -- critical violations
  grace_period    INT(4)       NOT NULL DEFAULT 5   -- seconds before FACE_ABSENT fires
  block_mobile    TINYINT(1)   NOT NULL DEFAULT 1
  notify_teacher  TINYINT(1)   NOT NULL DEFAULT 1
  notify_threshold INT(4)      NOT NULL DEFAULT 60  -- trust score
  timecreated     BIGINT(10)   NOT NULL DEFAULT 0
  timemodified    BIGINT(10)   NOT NULL DEFAULT 0

INDEXES:
  quizid (UNIQUE)
```

### 2.2 `quizaccess_edproctoring_session` — One record per quiz attempt

```xml
TABLE: quizaccess_edproctoring_session
  id              BIGINT(10)   NOT NULL AUTO_INCREMENT PRIMARY KEY
  quizid          BIGINT(10)   NOT NULL
  cmid            BIGINT(10)   NOT NULL
  userid          BIGINT(10)   NOT NULL
  attemptid       BIGINT(10)   NOT NULL  -- FK: quiz_attempts.id
  status          VARCHAR(20)  NOT NULL DEFAULT 'active'
                               -- active | completed | abandoned | suspended
  consent_given   TINYINT(1)   NOT NULL DEFAULT 0
  consent_time    BIGINT(10)   NOT NULL DEFAULT 0
  started_at      BIGINT(10)   NOT NULL DEFAULT 0
  ended_at        BIGINT(10)   NOT NULL DEFAULT 0
  trust_score     DECIMAL(5,2) NULL       -- computed on completion
  total_snapshots INT(6)       NOT NULL DEFAULT 0
  total_violations INT(6)      NOT NULL DEFAULT 0
  critical_violations INT(6)   NOT NULL DEFAULT 0
  warning_violations  INT(6)   NOT NULL DEFAULT 0
  timecreated     BIGINT(10)   NOT NULL DEFAULT 0
  timemodified    BIGINT(10)   NOT NULL DEFAULT 0

INDEXES:
  quizid
  userid
  attemptid (UNIQUE)
  status
```

### 2.3 `quizaccess_edproctoring_snap` — Captured webcam images

```xml
TABLE: quizaccess_edproctoring_snap
  id              BIGINT(10)   NOT NULL AUTO_INCREMENT PRIMARY KEY
  sessionid       BIGINT(10)   NOT NULL   -- FK: edproctoring_session.id
  userid          BIGINT(10)   NOT NULL
  attemptid       BIGINT(10)   NOT NULL
  pathnamehash    VARCHAR(40)  NOT NULL   -- Moodle file API pathnamehash
  capture_type    VARCHAR(20)  NOT NULL   -- interval | burst | pre_quiz | manual
  timecaptured    BIGINT(10)   NOT NULL
  elapsed_seconds INT(6)       NOT NULL DEFAULT 0  -- seconds into attempt
  quiz_page       INT(4)       NULL       -- quiz page when snapshot was taken
  face_detected   TINYINT(1)   NULL       -- NULL=not checked, 0=no, 1=yes
  face_count      TINYINT(4)   NULL
  brightness_score DECIMAL(4,2) NULL      -- 0.00–1.00
  face_match_score DECIMAL(5,2) NULL      -- Phase 2: 0.00–100.00
  is_violation    TINYINT(1)   NOT NULL DEFAULT 0
  timecreated     BIGINT(10)   NOT NULL DEFAULT 0

INDEXES:
  sessionid
  userid
  timecaptured
  is_violation
```

### 2.4 `quizaccess_edproctoring_violation` — Violation events

```xml
TABLE: quizaccess_edproctoring_violation
  id              BIGINT(10)   NOT NULL AUTO_INCREMENT PRIMARY KEY
  sessionid       BIGINT(10)   NOT NULL   -- FK: edproctoring_session.id
  userid          BIGINT(10)   NOT NULL
  attemptid       BIGINT(10)   NOT NULL
  snap_id         BIGINT(10)   NULL       -- FK: edproctoring_snap.id (burst snapshot)
  violation_type  VARCHAR(50)  NOT NULL   -- see taxonomy below
  severity        VARCHAR(10)  NOT NULL   -- critical | warning | info
  severity_weight DECIMAL(4,2) NOT NULL   -- deducted from trust score
  details         TEXT         NULL       -- JSON: extra context (face_count, etc.)
  quiz_page       INT(4)       NULL
  elapsed_seconds INT(6)       NULL
  dismissed       TINYINT(1)   NOT NULL DEFAULT 0  -- teacher marked as false positive
  dismissed_by    BIGINT(10)   NULL
  dismissed_time  BIGINT(10)   NULL
  timecreated     BIGINT(10)   NOT NULL DEFAULT 0

INDEXES:
  sessionid
  userid
  violation_type
  severity
  timecreated
```

### 2.5 `quizaccess_edproctoring_baseimg` — Reference face images (Phase 2)
*(Table created in Phase 1 install.xml but unused until Phase 2)*

```xml
TABLE: quizaccess_edproctoring_baseimg
  id              BIGINT(10)   NOT NULL AUTO_INCREMENT PRIMARY KEY
  userid          BIGINT(10)   NOT NULL
  pathnamehash    VARCHAR(40)  NOT NULL   -- Moodle file API pathnamehash
  uploaded_by     BIGINT(10)   NOT NULL   -- userid of uploader
  image_type      VARCHAR(20)  NOT NULL DEFAULT 'photo'
                               -- photo | id_card | pan_card | passport | other
  is_active       TINYINT(1)   NOT NULL DEFAULT 1
  verified        TINYINT(1)   NOT NULL DEFAULT 0  -- admin verified
  timecreated     BIGINT(10)   NOT NULL DEFAULT 0
  timemodified    BIGINT(10)   NOT NULL DEFAULT 0

INDEXES:
  userid
  is_active
```

---

## 3. Violation Taxonomy & Trust Score

### 3.1 Violation Types

| Type | Severity | Weight | Trigger |
|------|----------|--------|---------|
| FACE_ABSENT | critical | 10 | No face detected for > grace_period consecutive seconds |
| MULTIPLE_FACES | critical | 15 | face_count > 1 |
| FACE_MISMATCH | critical | 20 | face match score < threshold (Phase 2) |
| TAB_SWITCH | warning | 8 | Page Visibility API hidden event |
| FULLSCREEN_EXIT | warning | 5 | Fullscreen change event, not fullscreen |
| COPY_PASTE | warning | 3 | Ctrl+C/V/X keydown or contextmenu on quiz body |
| LOW_LIGHT | info | 2 | face-api.js confidence < 0.4 AND brightness < 0.3 |
| CAMERA_BLOCKED | critical | 12 | MediaStream track ends or becomes inactive |

### 3.2 Trust Score Formula

```
trust_score = max(0, 100 − Σ(violation_i.severity_weight))
```

- Every violation instance contributes its full weight. No per-violation cap.
- Total deduction is capped at 100 (score floor is 0).
- Dismissed violations are excluded from the calculation.
- Calculated once on session completion. Stored on `session.trust_score`.
- Recalculated if teacher dismisses a violation (via AJAX update).

### 3.3 Trust Score Bands

| Score | Band | Report colour | Action |
|-------|------|---------------|--------|
| 80–100 | Low Risk | Green | No action required |
| 60–79 | Review | Amber | Teacher notification sent (if enabled) |
| 0–59 | High Risk | Red | Teacher notification sent, attempt flagged |

---

## 4. Class Map

```
quizaccess/edproctoring/
├── rule.php
│   └── class quizaccess_edproctoring_rule extends quiz_access_rule
│       ├── make() — static factory reading quizaccess_edproctoring table
│       ├── is_preflight_check_required() — true always (consent + camera)
│       ├── add_preflight_check_form_fields() — inject camera permission iframe
│       ├── validate_preflight_check() — verify consent checkbox ticked
│       ├── current_attempt_finished() — mark session completed, calc trust score
│       ├── add_settings_form_fields() — quiz edit form settings
│       ├── validate_settings_form_fields() — validate per-quiz config
│       └── save_settings() — upsert quizaccess_edproctoring table
│
├── classes/
│   ├── privacy/
│   │   └── provider.php
│   │       └── implements core_privacy\local\metadata\provider
│   │                    core_privacy\local\request\plugin_provider
│   │                    core_privacy\local\request\core_userlist_provider
│   │
│   ├── task/
│   │   ├── cleanup_images.php
│   │   │   └── class cleanup_images extends \core\task\scheduled_task
│   │   │       └── execute() — delete snaps + files older than retention_days
│   │   └── recalculate_trust_scores.php
│   │       └── execute() — recompute trust score for any completed session with null score
│   │
│   ├── external/
│   │   ├── save_snapshot.php
│   │   │   └── class save_snapshot extends external_api
│   │   │       ├── execute_parameters() — sessionid, imagedata(base64), capturetype,
│   │   │       │                           elapsed, quizpage, facedetected, facecount,
│   │   │       │                           brightness, isviolation
│   │   │       └── execute() — decode base64, store via file API, insert snap record
│   │   │
│   │   ├── log_violation.php
│   │   │   └── class log_violation extends external_api
│   │   │       ├── execute_parameters() — sessionid, violationtype, severity,
│   │   │       │                          weight, details(json), quizpage, elapsed, snapid
│   │   │       └── execute() — insert violation record, update session counters
│   │   │
│   │   ├── get_session.php
│   │   │   └── class get_session extends external_api
│   │   │       └── execute() — return session status, violation counts, settings
│   │   │
│   │   ├── complete_session.php
│   │   │   └── execute() — mark session completed, compute trust score, send notification
│   │   │
│   │   └── dismiss_violation.php  (teacher-side, Phase 1 report)
│   │       └── execute() — mark violation dismissed, recompute trust score
│   │
│   ├── helper/
│   │   ├── image_store.php
│   │   │   └── class image_store
│   │   │       ├── store_snapshot(sessionid, userid, base64, capturetype) → pathnamehash
│   │   │       ├── get_snapshot_url(pathnamehash, contextid) → URL
│   │   │       ├── delete_session_images(sessionid) — delete from filestore + snap table
│   │   │       └── delete_user_images(userid) — GDPR purge
│   │   │
│   │   ├── trust_score.php
│   │   │   └── class trust_score
│   │   │       ├── calculate(sessionid) → float — query violations, apply formula
│   │   │       └── get_band(score) → string (low_risk|review|high_risk)
│   │   │
│   │   ├── violation_classifier.php
│   │   │   └── class violation_classifier
│   │   │       ├── VIOLATION_WEIGHTS — const array, all types → weight
│   │   │       ├── VIOLATION_SEVERITIES — const array
│   │   │       └── validate_type(type) → bool
│   │   │
│   │   ├── session_manager.php
│   │   │   └── class session_manager
│   │   │       ├── get_or_create(quizid, cmid, userid, attemptid) → session record
│   │   │       ├── mark_completed(sessionid) — set status, ended_at, calc trust score
│   │   │       ├── record_consent(sessionid) — set consent_given + consent_time
│   │   │       └── send_notification(sessionid) — message API to quiz teachers
│   │   │
│   │   └── mobile_detector.php
│   │       └── class mobile_detector
│   │           └── is_mobile(useragent) → bool
│   │
│   ├── form/
│   │   └── preflight_form.php
│   │       └── class preflight_form extends moodleform
│   │           └── definition() — consent checkbox, hidden camera_verified field
│   │
│   ├── event/
│   │   ├── session_started.php
│   │   │   └── class session_started extends \core\event\base
│   │   ├── violation_logged.php
│   │   │   └── class violation_logged extends \core\event\base
│   │   └── session_completed.php
│   │       └── class session_completed extends \core\event\base
│   │
│   └── output/
│       ├── renderer.php
│       │   └── class renderer extends \plugin_renderer_base
│       │       ├── render_attempts_report(data) → HTML
│       │       ├── render_attempt_detail(data) → HTML
│       │       └── render_consent_screen(quizname, retentiondays, privacyurl) → HTML
│       └── renderable/
│           ├── attempts_report.php — implements renderable + templatable
│           └── attempt_detail.php  — implements renderable + templatable
```

---

## 5. AMD Module Map

| File | Purpose |
|------|---------|
| `amd/src/proctoring_session.js` | Orchestrator. Init on quiz page load. Coordinates all modules. Handles cleanup on quiz submit. |
| `amd/src/face_detector.js` | Wraps face-api.js. Loads TinyFaceDetector model. Runs detection every 2s on canvas from video stream. Returns {faceCount, brightness, confidence}. |
| `amd/src/capture_manager.js` | Manages interval timer (default 30s) and burst capture (3 shots × 2s apart). Converts canvas to PNG base64. Calls save_snapshot external API. |
| `amd/src/tab_monitor.js` | Listens to `document.addEventListener('visibilitychange', ...)` and `document.addEventListener('fullscreenchange', ...)`. Emits events to violation_reporter. |
| `amd/src/violation_reporter.js` | AJAX queue for violation logging. Retries on failure (max 3). Calls log_violation external API. Updates warning count in overlay. Triggers auto-submit if threshold hit. |
| `amd/src/camera_overlay.js` | Renders the camera indicator UI. Shows live thumbnail, recording dot, violation count badge. Toast notification on each violation. Re-enter-fullscreen button. |
| `amd/src/preflight_check.js` | Drives the pre-flight wizard: camera permission → live preview → face detection check → base image check (Phase 2). Enables "Start Quiz" only when all steps pass. |
| `amd/src/copy_paste_monitor.js` | `keydown` listener for Ctrl+C/V/X, `contextmenu` listener on quiz body. Emits violation event. |

All AMD modules follow the `define(['module/dep'], function(dep) { return { ... }; })` pattern.
face-api.js is bundled into `amd/src/vendor/face-api.min.js` (not loaded from CDN — avoids CSP issues).

---

## 6. External API Endpoints

Registered in `db/services.php`. Called via `core/ajax` AMD module.

| Method name | Parameters | Returns | Capability required |
|-------------|-----------|---------|-------------------|
| `quizaccess_edproctoring_save_snapshot` | sessionid, imagedata, capturetype, elapsed, quizpage, facedetected, facecount, brightness, isviolation | {snapid, success} | logged-in user (own session only) |
| `quizaccess_edproctoring_log_violation` | sessionid, violationtype, severity, weight, details, quizpage, elapsed, snapid | {violationid, warningcount, autosubmit} | logged-in user (own session only) |
| `quizaccess_edproctoring_get_session` | sessionid | {status, criticalcount, warningcount, settings} | logged-in user (own session only) |
| `quizaccess_edproctoring_complete_session` | sessionid, attemptid | {trustscore, band} | logged-in user (own session only) |
| `quizaccess_edproctoring_dismiss_violation` | violationid | {trustscore} | quizaccess/edproctoring:viewreport |

All external API classes validate that the requesting user owns the session (for student-facing calls) or has the report capability (for teacher calls) before touching any data.

---

## 7. Scheduled Tasks

Registered in `db/tasks.php`.

| Class | Schedule | Action |
|-------|----------|--------|
| `\quizaccess_edproctoring\task\cleanup_images` | Daily 03:00 | Find snaps where timecaptured < (now − retention_days×86400). Delete from Moodledata filestore. Delete snap records. |
| `\quizaccess_edproctoring\task\recalculate_trust_scores` | Hourly | Find completed sessions with null trust_score. Calculate and store. |

---

## 8. File Areas (Moodledata)

| Filearea | Component | Context | Purpose |
|---------|-----------|---------|---------|
| `snapshots` | quizaccess_edproctoring | Module context (cmid) | Webcam capture images |
| `base_images` | quizaccess_edproctoring | User context | Reference face images (Phase 2) |

Files served via `pluginfile.php` hook in `lib.php`. Access controlled: student can view own snapshots; teacher/admin can view all snapshots for quizzes they manage.

---

## 9. Capabilities (`db/access.php`)

| Capability | Default roles | Description |
|-----------|--------------|-------------|
| quizaccess/edproctoring:viewreport | teacher, editingteacher, manager | View proctoring reports |
| quizaccess/edproctoring:manage | editingteacher, manager | Configure proctoring on quiz |
| quizaccess/edproctoring:viewallreports | manager, admin | View all reports, all courses |
| quizaccess/edproctoring:deleteimages | manager, admin | Delete captured images |
| quizaccess/edproctoring:uploadbaseimage | editingteacher, manager, admin | Upload base image for other users |

---

## 10. Events (`db/events.php`)

| Event class | Observed event | Action |
|------------|----------------|--------|
| (observer) | `\mod_quiz\event\attempt_submitted` | Call complete_session(), compute trust score, send notification |
| (observer) | `\mod_quiz\event\attempt_abandoned` | Mark session abandoned |
| (emitter) | session_started | Fires when consent given + session created |
| (emitter) | violation_logged | Fires on each violation (for external plugin hooks) |
| (emitter) | session_completed | Fires when trust score computed |

---

## 11. Key PHP Files

| File | Role |
|------|------|
| `rule.php` | Core access rule. All quiz lifecycle hooks. |
| `settings.php` | Admin settings page registration. |
| `lib.php` | `quizaccess_edproctoring_pluginfile()` for serving images. Navigation hook for student base image profile link (Phase 2). |
| `report/attempts.php` | Teacher report — all attempts for a quiz. |
| `report/attempt_detail.php` | Teacher report — single attempt detail. |
| `report/admin.php` | Site-wide admin report. |
| `db/install.xml` | Full DB schema for all 5 tables. |
| `db/upgrade.php` | Schema migration stubs for future phases. |
| `db/access.php` | Capabilities. |
| `db/tasks.php` | Scheduled task registration. |
| `db/events.php` | Observer registration. |
| `db/services.php` | External API registration. |
| `db/caches.php` | Cache definitions (quiz settings cache, session cache). |

---

## 12. FastAPI Microservice Contract (Phase 2 Reference)

Service runs at configurable URL (default `http://localhost:8765`). Called from `helper/face_service.php` via cURL.

### POST /verify
Compare two images for face match.
```json
Request:
{
  "image1_path": "/path/to/moodledata/snapshot.png",
  "image2_path": "/path/to/moodledata/baseimage.png",
  "model": "ArcFace",
  "distance_metric": "cosine"
}

Response:
{
  "verified": true,
  "distance": 0.23,
  "threshold": 0.68,
  "model": "ArcFace",
  "detector_backend": "opencv",
  "similarity_metric": "cosine"
}
```

### POST /detect
Detect faces in a single image (fallback if face-api.js unavailable).
```json
Request: { "image_path": "/path/to/image.png" }
Response: { "face_count": 1, "faces": [{"confidence": 0.98, "bbox": [...]}] }
```

### GET /health
Returns `{"status": "ok", "model": "ArcFace"}` — used by admin settings page to verify service connectivity.

---

## 13. Cache Definitions (`db/caches.php`)

| Cache | Type | TTL | Purpose |
|-------|------|-----|---------|
| `settings` | application | 1 hour | Per-quiz settings (avoid repeated DB reads per attempt) |
| `session` | session | 30 min | Current user's active session record |

---

## 14. Privacy Provider

`classes/privacy/provider.php` implements:

- `get_metadata()` — declares all 5 DB tables and their fields as user data. Declares Moodledata file areas.
- `get_contexts_for_userid()` — returns module contexts where user has proctoring sessions + base images.
- `export_user_data()` — exports all sessions, snapshots metadata, violations, base image metadata (not the actual image bytes — too large).
- `delete_data_for_all_users_in_context()` — admin bulk delete by context.
- `delete_data_for_user()` — delete all records + files for a specific user. Used for GDPR erasure.
- `get_users_in_context()` — for core_userlist_provider.
- `delete_data_for_users()` — bulk delete for userlist.

---

## 15. install.xml Index

Tables created in Phase 1 install (all 5 tables, including Phase 2 ones):
1. `quizaccess_edproctoring` (settings)
2. `quizaccess_edproctoring_session`
3. `quizaccess_edproctoring_snap`
4. `quizaccess_edproctoring_violation`
5. `quizaccess_edproctoring_baseimg` (unused until Phase 2)

Reason: Creating Phase 2 tables in install.xml now avoids a complex upgrade migration later. The tables simply stay empty until Phase 2 code is deployed.

---

## Live Monitor (Option A) — technical — v2026062700

### No schema change
Reads existing tables only: `..._session` (status, counts, started_at), `..._snap` (latest `timecaptured`, `pathnamehash`), `..._violation` (non-dismissed `severity_weight`). Thumbnails served by the existing `quizaccess_edproctoring_pluginfile()` (snapshots filearea) — already permits `viewreport` holders to see any student's frames.

### New files
- `classes/helper/live_monitor.php` — `get_active_sessions(int $quizid = 0)`: joins active sessions ▸ user/quiz/course; one grouped query for latest snapshot per session (portable `JOIN (… MAX(timecaptured) GROUP BY sessionid)`, no per-row subquery, no N+1); one grouped query for provisional trust deductions; one grouped query for latest violation type. Returns sorted tile rows. `get_refresh_interval()` reads the `live_refresh` setting (min 3, default 9). `STALE_MULTIPLIER = 3`.
- `classes/external/get_live_sessions.php` — `external_api`. Param `cmid` (default 0). cmid>0 → module context + `viewreport`; cmid=0 → system context + `viewallreports`. Returns `{servertime, sessions[]}`.
- `report/live.php` — page; branches on `cmid` for the two scopes; `js_call_amd('quizaccess_edproctoring/live_monitor','init',[[cmid,refresh]])` (double-wrapped config per AMD positional-arg rule).
- `templates/live_monitor.mustache` — toolbar (count, live pulse, last-updated, manual refresh) + `#edp-live-grid` container + empty/noscript states. Tiles themselves are rendered by JS.
- `amd/src/live_monitor.js` (+ `build/` copy) — `define(['jquery','core/ajax','core/str'])`; polls the WS, sorts suspicious-first, rebuilds grid innerHTML, marks stale, **pauses while `document.hidden`**, manual refresh button, tile → `attempt_detail.php` in new tab.
- CSS appended to `styles.css` (grid, tile, band colours, stale state, pulse).

### Modified files
- `db/services.php` — register `quizaccess_edproctoring_get_live_sessions` (ajax, read, loginrequired).
- `settings.php` — `live_refresh` configtext; `admin_externalpage` under `reports` for the site-wide wall (capability `viewallreports`).
- `report/index.php` — "Open live monitor" button after the heading.
- `lang/en/quizaccess_edproctoring.php` — `livemonitor*`, `liverefresh*`, `openlivemonitor` strings.
- `version.php` — `2026060404 → 2026062700` (no `upgrade.php` block needed; no schema delta).

### Performance
Active-session count is small (concurrent attempts), so the wall is 4 grouped queries per poll regardless of cohort size. Poll pauses on hidden tabs. Site-wide query is bounded by `status='active'`.
