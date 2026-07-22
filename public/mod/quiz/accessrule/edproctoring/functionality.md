# quizaccess_edproctoring — Functionality Specification

**Plugin type:** quizaccess (Quiz Access Rule)
**Component name:** quizaccess_edproctoring
**Moodle install path:** `<moodleroot>/quizaccess/edproctoring/`
**Target Moodle version:** 4.3+ / 5.x
**Version:** 2025050100
**Status:** Phase 1 — Core Proctoring (no face recognition)

---

## 1. Purpose

quizaccess_edproctoring is a native Moodle quiz access rule that adds full-featured AI-assisted proctoring to any Moodle quiz without relying on external SaaS platforms. It captures webcam images, detects behavioural violations in real time (face absent, multiple faces, tab switching, fullscreen exit, copy-paste), scores each attempt with a Trust Score, and provides teachers with a rich report including an image gallery and violation timeline. Face recognition (identity verification against a base image) is added in Phase 2 via an optional on-premises FastAPI microservice.

---

## 2. Roles and Permissions

| Role | Capabilities | Notes |
|------|-------------|-------|
| Site Admin | All | Configure global defaults, manage all data, delete images, bulk GDPR purge |
| Teacher / Course Editor | viewreport, manage | Configure proctoring per quiz, view reports for own quizzes, upload base images for enrolled students |
| Student | (none specific) | Take proctored quizzes, upload own base image, give consent |
| Manager | viewallreports | View reports across all courses |

---

## 3. Feature List

### 3.1 Quiz Access Rule Integration
- Plugin appears in the Quiz settings form under "Extra restrictions on attempts" alongside other access rules (IP restriction, password, time delay).
- When enabled on a quiz, all attempts go through the proctoring flow.
- Settings are stored per-quiz and inherit site-level defaults.

### 3.2 Mobile / Tablet Blocking
- If "Block mobile access" is enabled (default: yes), students on mobile or tablet browsers are shown a clear error message and prevented from starting the quiz.
- Detection uses User-Agent sniffing (PHP server-side) plus `navigator.userAgent` in JavaScript.
- Teachers can override this per quiz.

### 3.3 Consent Screen
- Before the pre-flight check, students see a consent page explaining:
  - That their webcam will be active throughout the quiz.
  - What images are captured and how long they are stored.
  - Who can view the images (teacher, admin).
  - A link to the institution's privacy policy (configurable URL in site settings).
- Student must tick a checkbox and click "I Agree and Continue" to proceed.
- Consent is recorded with a timestamp in the session record.
- If consent is refused, the student cannot access the quiz (no attempt is created).

### 3.4 Pre-Flight Check (Camera & Face Verification)
A wizard shown after consent and before the quiz attempt is created. Steps:

1. **Camera Access** — Browser requests camera permission. If denied, the quiz is blocked with a message explaining the requirement.
2. **Camera Feed Test** — Live preview shown. Student confirms their face is clearly visible.
3. **Face Detection Check** — face-api.js runs and confirms:
   - Exactly one face detected.
   - Brightness score above threshold (configurable, default 30%).
   - Face occupies a minimum area of the frame (prevents tiny/distant faces).
4. **Base Image Check (Phase 2 gate)** — If face recognition is enabled:
   - Check if student has an active base image on record.
   - If not: inline upload prompt (camera snapshot or file upload). Student selects image type (photo / ID card / PAN card / passport / other). Image is stored immediately.
   - If yes: run face verification against base image via FastAPI. Show match result. Below-threshold match blocks the quiz with a "Identity could not be verified — please contact your teacher" message.
5. **Ready** — All checks passed. "Start Quiz" button activates.

### 3.5 In-Quiz Webcam Capture
- A small, non-intrusive camera indicator overlay appears in the top-right corner of the quiz page (configurable position).
- Overlay shows: a live camera feed thumbnail (approx 120×90px), a recording indicator dot, and a violation warning count.
- **Interval capture:** Every N seconds (configurable per quiz, default 30s), a snapshot is taken, compressed to PNG, and sent via AJAX to Moodle's filestore.
- **Burst capture on violation:** When a violation is detected, 3 rapid snapshots are taken at 2-second intervals and sent immediately.
- Images are stored in Moodledata under the `snapshots` file area, associated with the quiz module context and the user.

### 3.6 Violation Detection (Real-Time, Browser-Side)

All detection runs in the student's browser via AMD modules. No server round-trip for detection.

| Violation Type | Trigger | Severity | Weight |
|---------------|---------|----------|--------|
| FACE_ABSENT | No face detected for > grace_period seconds (default 5s) | Critical | 10 |
| MULTIPLE_FACES | More than 1 face detected in frame | Critical | 15 |
| TAB_SWITCH | Page Visibility API fires `visibilitychange` to hidden | Warning | 8 |
| FULLSCREEN_EXIT | Fullscreen API fires `fullscreenchange` to non-fullscreen (if enabled) | Warning | 5 |
| COPY_PASTE | `keydown` Ctrl+C/Ctrl+V/Ctrl+X or right-click detected | Warning | 3 |
| LOW_LIGHT | face-api.js confidence below threshold due to brightness | Info | 2 |
| CAMERA_BLOCKED | `getUserMedia` stream ends or track becomes inactive mid-quiz | Critical | 12 |

Each violation event is logged via AJAX with: violation_type, timestamp (absolute + seconds-elapsed-in-attempt), quiz page number the student was on, and the ID of the snapshot taken at the time of violation (if burst capture fired).

### 3.7 Student Warning System
- On each violation, a brief toast notification appears: "⚠ Warning [N]: [Violation description]. Continued violations may affect your submission."
- Warning count is shown in the overlay throughout the quiz.
- If "Auto-submit on violations" is enabled and critical violations reach the configured threshold, the quiz is auto-submitted via the existing Moodle quiz finish mechanism (no custom submit — uses quiz's own timer finish).

### 3.8 Fullscreen Enforcement (Optional)
- If enabled, the quiz page requests fullscreen on start via the Fullscreen API.
- Exit from fullscreen is logged as FULLSCREEN_EXIT violation (not a hard block).
- A re-enter fullscreen button appears on the overlay after an exit.

### 3.9 Trust Score Engine
- Calculated at the end of each quiz attempt (when the session is marked `completed`).
- Formula: `trust_score = max(0, 100 − Σ(violation.weight))`
- Each violation instance contributes its weight to the deduction. Same violation type accumulates (10 FACE_ABSENT events = 100 points deducted → score 0).
- Score is stored on the session record.
- Colour coding in reports: ≥ 80 = green (Low Risk), 60–79 = amber (Review), < 60 = red (High Risk / Flagged).

### 3.10 Teacher Reports

#### Attempts List (quiz-level)
URL: `/quizaccess/edproctoring/report/attempts.php?cmid=X`

Columns: Student name, Quiz attempt #, Attempt start time, Duration, Trust Score (colour-coded), Violation count (critical | warning | info), Status, Actions.

Filters: Date range, Trust score range, Violation type, Flagged only.

Bulk actions: Delete selected sessions' images, Export CSV.

#### Attempt Detail (single attempt)
URL: `/quizaccess/edproctoring/report/attempt_detail.php?sessionid=X`

Sections:
- **Header:** Student name, quiz, attempt number, start/end time, final trust score, total violations.
- **Violation Timeline:** Horizontal bar representing the quiz duration. Violations plotted as coloured dots on the timeline. Clicking a dot shows the violation details and the burst snapshots taken at that moment.
- **Image Gallery:** Grid of all captured snapshots in chronological order. Each image shows: timestamp, capture type (interval/burst), face_detected flag, violation badge if associated. Teacher can click to enlarge and can dismiss individual violations (mark as false positive).
- **Trust Score Breakdown:** Table of violation types with counts and weight contribution.
- **Actions:** Delete all images for this session, Export report as PDF (Phase 3), Flag for review.

### 3.11 Admin Site-Wide Report
URL: `/quizaccess/edproctoring/report/admin.php`
All sessions across all quizzes, all users. Same filters as teacher report plus course filter and teacher filter. Full bulk-delete and CSV export.

### 3.12 Base Image Management

#### Student (own images)
- Accessible via a link in the user's profile page (added via navigation hook).
- Student can view their current base image(s), upload a new one, or delete their own images.
- Upload supports: camera snapshot (via webcam) or file upload (JPG/PNG, max 5MB).
- Image type selection: photo / ID card / PAN card / passport / other.

#### Admin/Teacher (other users' images)
- Via the site-wide admin report or user management section.
- Teacher can upload a base image for any student enrolled in their courses.
- Admin can upload for any user.
- All uploads are audited (uploaded_by field).

### 3.13 GDPR & Data Retention
- **Retention policy:** Site admin configures retention in days (default 90). Images older than the retention period are automatically deleted by a daily scheduled task.
- **Right to erasure:** Admin can delete all proctoring data for a specific user via the admin report (User data → Delete). This removes all snapshots, base images, violation records, and session records for that user.
- **Privacy provider:** Implements `core_userlist_provider` and `plugin_provider`. All stored data is disclosed and deletable via Moodle's Privacy API (GDPR data export + delete).
- **Consent record:** Stored with timestamp. Included in GDPR data export.

### 3.14 Notifications
- When a quiz attempt session is marked `completed` and the trust score is below the configured threshold (default 60), a Moodle message is sent to the quiz's teacher(s) via `message_send()`.
- Message contains: student name, quiz name, trust score, violation summary, and a direct link to the attempt detail report.
- Can be disabled per quiz or site-wide.

---

## 4. Settings Inventory

### 4.1 Site-Level Admin Settings
(`/admin/settings.php?section=quizaccess_edproctoring`)

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| Default capture interval | int (seconds) | 30 | Used if quiz doesn't override |
| Default image resolution | select (480p/720p) | 480p | Higher = larger files |
| Face recognition service URL | text | (empty) | http://localhost:8765 when FastAPI is running |
| Face match threshold | float 0–1 | 0.60 | Cosine distance; lower = stricter |
| Default retention days | int | 90 | Images auto-deleted after this many days |
| Default trust score notify threshold | int | 60 | Send teacher notification below this score |
| Block mobile by default | bool | yes | |
| Institution privacy policy URL | text | (empty) | Shown in consent screen |
| face-api.js model CDN URL | text | (bundled) | URL of model weights |

### 4.2 Per-Quiz Settings
(Added to Quiz → Settings → Extra restrictions)

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| Enable proctoring | bool | no | Master switch |
| Capture interval (seconds) | int | (site default) | |
| Require fullscreen | bool | no | Log exit as violation |
| Detect tab switching | bool | yes | |
| Enable face detection | bool | yes | Browser-side, no service needed |
| Enable face recognition | bool | no | Requires FastAPI service URL |
| Who can upload base image | select | either | student / admin / either |
| Require consent screen | bool | yes | |
| Auto-submit on violations | bool | no | |
| Auto-submit violation threshold | int | 5 | Critical violations to trigger |
| Grace period (seconds) | int | 5 | Face must be absent this long to count |
| Block mobile access | bool | (site default) | |
| Notify teacher | bool | yes | |
| Notification trust threshold | int | (site default) | |

---

## 5. Phase Breakdown

| Phase | Scope |
|-------|-------|
| **Phase 1 (current)** | Plugin scaffold, consent screen, pre-flight check (no face recog), webcam capture, violation detection (face absent, multiple faces, tab switch, fullscreen exit, copy-paste, low light, camera blocked), violation logging, trust score engine, teacher reports (list + detail with timeline + gallery), student warning toasts, GDPR privacy provider, retention cleanup task, notifications |
| **Phase 2** | Base image upload (student + admin), FastAPI microservice integration, face recognition pre-flight gate, FACE_MISMATCH violation, inline base image upload during pre-flight, face match score in snapshots, updated reports with match scores |
| **Phase 3** | Audio monitoring (optional), PDF export, bulk CSV export, violation dismissal (false positive), admin site-wide report enhancements, browser fingerprinting |
| **Phase 4** | Live teacher monitoring dashboard (AJAX polling), real-time flagging, teacher-to-student message during exam |

---

## 6. Out of Scope (Phase 1–4)
- Screen recording / screen sharing capture
- LockDown browser (separate browser executable required)
- External SaaS integrations (ProctorU, Inspera)
- AI audio analysis (beyond basic voice activity detection)
- Multi-site (Moodle Network) support

---

## Live Monitor (Option A — snapshot wall) — added 2026-06-27 (v2026062700)

A real-time proctor dashboard that shows every **currently active** exam taker as a tile in one screen, auto-refreshing on a fixed interval. Lets an admin or tutor watch a whole cohort at a glance and jump into anyone who looks suspicious.

### What it does
- One tile per active proctoring session (`session.status = 'active'`).
- Each tile shows: latest webcam snapshot, student name, course/quiz, elapsed time, live violation counts (critical / warning), a provisional trust score, and the most recent violation type.
- Tiles are sorted **suspicious-first** (most critical violations, then lowest trust).
- A tile whose latest snapshot is older than `capture_interval × 3` is flagged **"Connection lost"** (student closed the tab, lost camera, or dropped offline).
- Click a tile to open that attempt's full detail report in a new tab.

### Liveness model (chosen: reuse existing snapshots)
The wall **reuses the snapshots the student already uploads** (default every 30 s). The dashboard polls every 9 s, so a new frame appears within ~30 s of capture. **No change to the student-side capture pipeline, no extra storage, no added per-student load** — the wall is purely a new read view over existing data. (A future "adaptive" mode could raise capture rate while a proctor is watching; deliberately out of scope for v1.)

### Scope & access (both)
- **Per-quiz** — `report/live.php?cmid=X`, gated by `quizaccess/edproctoring:viewreport` on the quiz module context. Reached from a new "Open live monitor" button on the proctoring report page. For teachers watching their own quiz.
- **Site-wide** — `report/live.php` (no cmid), gated by `quizaccess/edproctoring:viewallreports` at system context. Reached from Site administration ▸ Reports. For admins/managers watching all active proctored exams.

No new capabilities introduced — reuses the two existing report capabilities, so nothing needs to be assigned after upgrade.

### Settings
- `live_refresh` (default 9 s) — dashboard auto-refresh interval.

### Out of scope for v1
Live WebRTC video (Option B), adaptive capture-rate bump while watched, audio, and one-click intervene/pause-attempt from the wall.
