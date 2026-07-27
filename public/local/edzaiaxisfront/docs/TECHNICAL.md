# local_edzaiaxisfront — Technical Reference

> Moodle LOCAL plugin that integrates the **axis-ai** Python service into Moodle LMS.
> Version: 2026010100 | Requires Moodle 4.1+, PHP 8.1+

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        MOODLE SERVER                            │
│                                                                 │
│  local_edzaiaxisfront (this plugin)                             │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐    │
│  │  Teacher UI  │  │  Student UI  │  │   Chatbot Panel    │    │
│  │  (dashboard/ │  │  (drawer on  │  │  (all pages via    │    │
│  │   wizard)    │  │  mod views)  │  │   before_footer)   │    │
│  └──────┬───────┘  └──────┬───────┘  └────────┬───────────┘    │
│         │                 │                   │                 │
│  ┌──────▼─────────────────▼───────────────────▼───────────┐    │
│  │           AMD / External Web Services (AJAX)            │    │
│  │    external/api.php  +  external/chatbot_api.php        │    │
│  └──────────────────────────┬──────────────────────────────┘    │
│                             │                                   │
│  ┌──────────────────────────▼──────────────────────────────┐    │
│  │  Local DB cache (11 tables)  +  axis_client.php          │    │
│  │  Scheduled tasks: poll_jobs (2m) + sync_outputs (5m)     │    │
│  └──────────────────────────┬──────────────────────────────┘    │
└─────────────────────────────┼───────────────────────────────────┘
                              │ HTTPS / Bearer token
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     AXIS-AI SERVICE (Python/FastAPI)            │
│                                                                 │
│  /api/v1/ingest/*  →  Celery tasks  →  Qdrant vector store      │
│  /api/v1/chat/*    →  RAG retrieval  →  LLM (OpenAI/Mistral)    │
│  /api/v1/content/* →  AI output fetch (summary, flashcards…)   │
│  /api/v1/admin/*   →  Tenant + user-override management         │
│  /api/v1/kb/*      →  Knowledge base documents                  │
│  /api/v1/jobs/*    →  Async job status polling                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. File Structure

```
local/edzaiaxisfront/
├── version.php                          Plugin version (2026010100)
├── lib.php                              Hook callbacks
│   ├── before_footer()                  Injects chatbot on all pages
│   └── after_config()                   Injects student panel on mod views
├── settings.php                         Admin nav: Settings / Reports / Overrides
│
├── classes/
│   ├── api/
│   │   └── axis_client.php              HTTP client to axis-ai REST API
│   ├── chatbot/
│   │   └── user_analytics.php          Moodle-DB-only analytics for chatbot
│   ├── content_router.php               Detects content type + builds ingest payload
│   ├── external/
│   │   ├── api.php                      21 AJAX functions (teacher + student + admin)
│   │   └── chatbot_api.php              6 AJAX functions (chatbot panel)
│   ├── output_manager.php               Syncs axis-ai outputs → Moodle DB tables
│   ├── privacy/
│   │   └── provider.php                 GDPR privacy API implementation
│   ├── scorm_extractor.php              PHP SCORM content extractor (4 formats)
│   └── task/
│       ├── poll_jobs.php                Scheduled: polls queued/processing jobs
│       └── sync_outputs.php             Scheduled: refreshes ready CM outputs
│
├── db/
│   ├── access.php                       Capability definitions
│   ├── install.xml                      11 DB tables (XMLDB schema)
│   ├── services.php                     27 web service function registrations
│   ├── tasks.php                        2 scheduled tasks
│   └── upgrade.php                      DB upgrade path
│
├── pages/
│   ├── dashboard.php                    Teacher: Course AI Manager
│   ├── wizard.php                       Teacher: 5-step AI setup wizard
│   ├── admin_reports.php                Admin: Token usage report
│   └── user_overrides.php               Admin: Per-user limit overrides
│
├── amd/src/
│   ├── repository.js                    Central AJAX call wrappers
│   ├── chatbot.js                       Floating chatbot orchestrator
│   ├── student_panel.js                 Student output drawer
│   ├── dashboard.js                     Teacher course manager
│   ├── wizard.js                        Teacher wizard steps
│   ├── job_monitor.js                   Real-time job progress polling
│   ├── admin_reports.js                 Token usage report UI
│   └── user_overrides.js                Per-user limit override UI
│
├── templates/
│   ├── chatbot_panel.mustache           Chatbot HTML shell
│   ├── student_panel.mustache           Student drawer HTML shell
│   ├── dashboard.mustache               Teacher dashboard
│   ├── wizard.mustache                  Teacher wizard
│   ├── admin_reports.mustache           Token report page
│   └── user_overrides.mustache          User overrides page
│
├── lang/en/local_edzaiaxisfront.php     All English strings (~150)
├── styles.css                           Full design system CSS (600+ lines)
└── docs/
    ├── TECHNICAL.md                     This document
    └── MANUAL.txt                       Teacher & admin hand manual
```

---

## 3. Database Schema (11 Tables)

### 3.1 `local_edzaiaxisfront_cm_config`
Per-CM AI configuration. One row per course module that a teacher configures.

| Column | Type | Notes |
|--------|------|-------|
| cmid | INT | FK → course_modules.id (UNIQUE) |
| courseid | INT | FK → course.id |
| axis_content_item_id | CHAR(36) | axis-ai UUID (NULL until first ingest) |
| status | CHAR(20) | `not_configured` \| `pending` \| `processing` \| `partial` \| `ready` \| `error` |
| enabled_features | TEXT | JSON: teacher-enabled output types |
| visible_features | TEXT | JSON: output types visible to students |
| generated_features | TEXT | JSON: output types that have completed generation |
| generation_config | TEXT | JSON: language, quiz count, Bloom distribution etc. |
| last_synced | INT | Unix timestamp of last successful axis-ai sync |

### 3.2 `local_edzaiaxisfront_outputs`
Cached text outputs: summary, FAQ, infographic, objectives, mindmap.

| Column | Type | Notes |
|--------|------|-------|
| cmid | INT | |
| output_type | CHAR(30) | `summary` \| `faq` \| `infographic` \| `objectives` \| `mindmap` |
| content | TEXT | Raw HTML / text |
| is_teacher_edited | INT(1) | 1 = teacher edited locally; won't be auto-overwritten on sync |
| is_visible | INT(1) | 0 = hidden from students |
| axis_output_id | CHAR(36) | axis-ai UUID for two-way sync |

### 3.3 `local_edzaiaxisfront_flashcards`
Flashcard pool per CM (supports soft-delete + teacher curation).

| Column | Notes |
|--------|-------|
| front / back | Question and answer text |
| hint | Optional hint (shown on request) |
| difficulty | `easy` \| `medium` \| `hard` |
| topic | Auto-classified topic string |
| is_active | 0 = excluded by teacher |
| source | `generated` \| `manual` |

### 3.4 `local_edzaiaxisfront_glossary`
Glossary term pool per CM.

### 3.5 `local_edzaiaxisfront_quiz_questions`
Quiz question pool with Bloom's taxonomy tagging.

| Column | Notes |
|--------|-------|
| question_type | `multichoice` \| `truefalse` \| `shortanswer` \| `essay` |
| options_json | JSON array of `{text, is_correct, feedback}` |
| blooms_level | `remember` \| `understand` \| `apply` \| `analyze` \| `evaluate` \| `create` |

### 3.6 `local_edzaiaxisfront_jobs`
Background job tracking (axis-ai async jobs).

| Column | Notes |
|--------|-------|
| axis_job_id | axis-ai processing_jobs UUID |
| status | `queued` \| `processing` \| `completed` \| `failed` \| `cancelled` |
| progress | 0–100 |
| tasks_json | Which output types this job will generate |

### 3.7 `local_edzaiaxisfront_chat_sessions`
Lightweight mirror of axis-ai chat sessions.

| Column | Notes |
|--------|-------|
| axis_session_id | axis-ai UUID |
| chat_mode | `study` \| `support` \| `practice` |
| tokens_used | Running total for rate limiting |

### 3.8 `local_edzaiaxisfront_token_usage`
Daily aggregated token/message counts per user (admin reporting).

### 3.9 `local_edzaiaxisfront_user_limits`
Per-user rate limit overrides. NULL = use site default.

| Column | Notes |
|--------|-------|
| chat_session_msg_limit | Messages per session |
| chat_daily_msg_limit | Messages per day |
| chat_monthly_msg_limit | Messages per month |
| token_monthly_limit | Tokens per month |

### 3.10 `local_edzaiaxisfront_kb_items`
Support knowledge base documents (mirrors axis-ai kb_items).

### 3.11 `local_edzaiaxisfront_chat_sessions` ← see 3.7

---

## 4. Content Ingestion Flow

```
Teacher clicks "Generate AI Content" in wizard
       │
       ▼
external/api.php → submit_ingest()
       │
       ├── if SCORM: scorm_extractor.php extracts chunks
       │   └── content_router::build_scorm_structured_payload()
       │       → POST /api/v1/ingest/structured
       │
       └── else: content_router::build_ingest_payload($cm)
           detects type: pdf | youtube | vimeo | peertube | page | book | video
           → POST /api/v1/ingest  (URL-based ingest)
       │
       ▼
axis-ai creates processing_job → returns job_id
       │
       ▼
Moodle saves job to local_edzaiaxisfront_jobs (status=queued)
       │
       ▼
poll_jobs task (every 2 min) polls /api/v1/jobs/{job_id}/status
       │
       ├── status = processing → update progress in DB
       │
       └── status = completed → output_manager::sync_cm_outputs()
                                fetches summary, glossary, flashcards,
                                quiz, faq, infographic from axis-ai
                                → stores in 6 local DB tables
                                → sets cm_config.status = ready
```

### Content Type Support Matrix

| Moodle Module | Content Type | axis-ai Ingest Endpoint |
|---------------|-------------|------------------------|
| mod_resource (PDF) | pdf | /ingest (URL) |
| mod_resource (video file) | video | /ingest (URL) |
| mod_url (YouTube) | youtube | /ingest (URL) |
| mod_url (Vimeo) | vimeo | /ingest (URL) |
| mod_url (PeerTube) | peertube | /ingest (URL) |
| mod_url (other) | page | /ingest (URL) |
| mod_page | page | /ingest (URL, with extracted HTML) |
| mod_book | book | /ingest (URL) |
| mod_scorm (Storyline 360) | scorm | /ingest/structured |
| mod_scorm (Rise) | scorm | /ingest/structured |
| mod_scorm (Rise Simple) | scorm | /ingest/structured |
| mod_scorm (Generic) | scorm | /ingest/structured |

---

## 5. Permission Cascade

Four independent bit-masks are ANDed before any output is shown to students:

```
site_enabled_features    (admin settings.php)
        ∩
enabled_features         (teacher wizard step 3, per CM)
        ∩
visible_features         (teacher toggle on dashboard)
        ∩
generated_features       (axis-ai actually generated this output)
        =
STUDENT SEES
```

Example: Admin has enabled `[summary, glossary, flashcards]`. Teacher enables `[summary, flashcards, quiz]`. Teacher makes `[summary]` visible. axis-ai generated `[summary, flashcards, quiz]`. Student sees: `[summary]` only.

---

## 6. SCORM Extraction (PHP)

`classes/scorm_extractor.php` runs on the Moodle server (no Python dependency) and handles four SCORM formats:

| Format | Detection | Extraction Method |
|--------|-----------|-------------------|
| Storyline 360 | `story.html` + `story_content/` directory | glob `slide*.html`, extract title+text per slide |
| Rise | `course_data.js` present | Parse JSON lesson/block structure |
| Rise Simple | `index_lms.html` contains "rise" | HTML scraping with heading-based sectioning |
| Generic | fallback | Recursive HTML file iterator |

Path resolution: checks `$CFG->dataroot/scorm/{scormid}/`, then falls back to extracting the SCORM zip from the Moodle file area.

Returns: `array of chunks [{sequence, chunk_type, title, text, has_audio, audio_transcript, embed_url}]`

---

## 7. Chatbot Architecture

### Hook injection — dual compatibility (Moodle 4.1 → 5.1)

The chatbot HTML is injected using **two parallel mechanisms** so the plugin runs on Moodle 4.1 through 5.1 without code changes:

| Moodle Version | Mechanism used | File |
|---------------|---------------|------|
| 4.1 – 4.3 | lib.php callback (`local_edzaiaxisfront_before_footer`) | `lib.php` |
| 4.4 – 5.x | PSR-14 Hooks API (`core\hook\output\before_footer_html_generation`) | `db/hooks.php` + `classes/hook/output_callbacks.php` |

Both contain identical logic. On Moodle 4.4+, when `db/hooks.php` is present, Moodle automatically ignores the `lib.php` version — no duplication risk.

The hook class calls `$hook->add_html($html)` instead of returning the HTML string (the key difference from the lib.php pattern).

Then injects `chatbot_panel.mustache` HTML and bootstraps `local_edzaiaxisfront/chatbot` AMD module.

### Chat session lifecycle
```
User selects course → get_course_ai_cms (AJAX)
→ User selects CM → create_chat_session (AJAX → axis-ai POST /chat/sessions)
→ User sends message → send_chat_message (AJAX → axis-ai POST /chat/message)
    [axis-ai: intent classify → RAG retrieve from Qdrant → LLM → parse]
→ AI replies with text + optional suggestions
→ User closes panel → end_chat_session (AJAX → axis-ai POST /chat/sessions/{id}/end)
```

### Chat Modes

| Mode | Qdrant Collection | Left Panel | Right Panel |
|------|------------------|------------|-------------|
| Study | `axis_content_chunks` | Enrolled courses | Tips + streak + stats |
| Support | `axis_kb_chunks` | Thread history | Support tips |
| Practice | `axis_content_chunks` | CM picker | Tips (quiz-focused) |

### My Analysis data sources (Moodle DB only — no Python call)

| Metric | Source Table |
|--------|-------------|
| Courses enrolled | `{enrol}` + `{user_enrolments}` |
| Courses completed | `{course_completions}` |
| Certificates/badges | `{badge_issued}` |
| XP Points | `{block_xp_log}` (falls back to activity-based calculation) |
| Overall progress | `{course_modules_completion}` |
| Average grade | `{grade_grades}` + `{grade_items}` |
| Login streak | `{logstore_standard_log}` consecutive day analysis |
| Upcoming events | `{event}` WHERE timestart > NOW() |

---

## 8. Token Usage Tracking

### How it works
Every successful `send_chat_message` call triggers `record_token_usage()` in `external/api.php` which upserts `local_edzaiaxisfront_token_usage` (daily row per user).

### Rate limit enforcement
axis-ai enforces hard limits per session/day/month using the tenant + user-override configuration. Moodle tracks usage for admin reporting only (soft mirror) — axis-ai is the authoritative rate limit source.

### Per-user overrides
1. Admin visits Site Admin → Axis AI → User Overrides
2. Searches for user, sets custom limits
3. Moodle saves to `local_edzaiaxisfront_user_limits`
4. Moodle immediately calls `axis_client->upsert_user_override()` to sync to axis-ai
5. axis-ai uses the override for all subsequent requests from that user

Sentinel: -1 in the AJAX API means "use site default" → stored as NULL in DB.

---

## 9. Scheduled Tasks

| Task | Schedule | Purpose |
|------|----------|---------|
| `poll_jobs` | Every 2 min | Polls `queued`/`processing` jobs, triggers output sync on completion |
| `sync_outputs` | Every 5 min | Refreshes `ready` CMs older than 10 min (catches partial updates) |

---

## 10. Admin KB (Knowledge Base)

Support chat draws from an admin-curated knowledge base stored in `axis_kb_chunks` Qdrant collection.

Admin workflow:
1. Site Admin → Axis AI → Settings → Knowledge Base section
2. Paste URL or upload file → click "Ingest"
3. axis-ai fetches, chunks, embeds → `axis_kb_chunks`
4. Admin can toggle items active/inactive or delete them
5. Support chat users get answers sourced from active KB items only

---

## 11. axis-ai API Contract (PHP ↔ Python)

### Authentication
All requests: `Authorization: Bearer {api_key}` header.

### Key endpoints used by axis_client.php

| PHP Method | HTTP | Path |
|-----------|------|------|
| `create_tenant()` | POST | `/api/v1/admin/tenants` |
| `update_tenant()` | PUT | `/api/v1/admin/tenants/{id}` |
| `upsert_user_override()` | POST | `/api/v1/admin/tenants/{id}/user-overrides` |
| `delete_user_override()` | DELETE | `/api/v1/admin/tenants/{id}/user-overrides/{user_id}` |
| `ingest_url()` | POST | `/api/v1/ingest` |
| `ingest_structured()` | POST | `/api/v1/ingest/structured` |
| `get_job_status()` | GET | `/api/v1/jobs/{job_id}/status` |
| `get_summary()` | GET | `/api/v1/content/{id}/summary` |
| `get_glossary()` | GET | `/api/v1/content/{id}/glossary` |
| `get_flashcards()` | GET | `/api/v1/content/{id}/flashcards` |
| `get_quiz_questions()` | GET | `/api/v1/content/{id}/quiz-questions` |
| `get_faq()` | GET | `/api/v1/content/{id}/faq` |
| `get_infographic()` | GET | `/api/v1/content/{id}/infographic` |
| `update_summary()` | PUT | `/api/v1/content/{id}/summary` |
| `trigger_generation()` | POST | `/api/v1/content/{id}/generate` |
| `create_chat_session()` | POST | `/api/v1/chat/sessions` |
| `send_chat_message()` | POST | `/api/v1/chat/message` |
| `end_chat_session()` | POST | `/api/v1/chat/sessions/{id}/end` |
| `kb_ingest_url()` | POST | `/api/v1/kb/ingest` |
| `list_kb_items()` | GET | `/api/v1/kb/items` |
| `toggle_kb_item()` | PUT | `/api/v1/kb/items/{id}` |
| `delete_kb_item()` | DELETE | `/api/v1/kb/items/{id}` |

---

## 12. Gap Analysis & Known Limitations

### Currently Not Wired (Python generates but Moodle doesn't expose)
These generators exist in axis-ai but the student panel does not yet render them:

| Generator | axis-ai Endpoint | Status |
|-----------|-----------------|--------|
| Bloom's Taxonomy analysis | `/content/{id}/blooms` | Planned for Phase 2 |
| Chapter markers (video) | `/content/{id}/chapters` | Planned for Phase 2 |
| Mind map | `/content/{id}/mindmap` | Planned for Phase 2 |
| Learning objectives | `/content/{id}/objectives` | Planned for Phase 2 |

### Practice Mode
`chatbot.js` submits `chat_mode: practice` when the user is in the Practice subtab. This maps to the same `axis_content_chunks` Qdrant collection as Study mode. The axis-ai chat orchestrator should be configured to adopt a Socratic/quiz-style prompt for this mode (intent classifier).

### SCORM Audio Transcripts
The `scorm_extractor.php` records `has_audio: true` and a placeholder `audio_transcript` for Storyline slides with audio. Full transcript extraction would require running Whisper on the audio files — this is a Python-side addition for a future phase.

### Moodle Quiz Import
The quiz questions stored in `local_edzaiaxisfront_quiz_questions` can be bulk-imported to Moodle's native Question Bank using `qformat_xml` — this bridge is not yet implemented.

### H5P
mod_h5p is not yet supported as a content source. It would require extracting H5P content JSON files, similar to SCORM.

---

## 13. Installation

### Moodle 4.1 – 4.4
1. Copy `local/edzaiaxisfront/` into your Moodle's `local/` directory.
2. Visit Site Administration → Notifications to run the DB install.
3. Go to Site Admin → Axis AI → Settings, enter Base URL + API key, save.
4. Enable desired site-level features.
5. Run `php admin/cli/scheduled_task.php --list` to confirm scheduled tasks.
6. Build AMD: `grunt amd` in the Moodle root.

### Moodle 5.1 (additional step — directory restructure)
Moodle 5.1 introduced a `/public` sub-directory. The web server document root now points to `<moodle_root>/public/` instead of `<moodle_root>/`.

**Plugin location**: copy to `public/local/edzaiaxisfront/` (inside `/public/`).

```
/var/www/moodle/                    ← Moodle installation root
    public/                         ← Web server document root (new in 5.1)
        local/
            edzaiaxisfront/         ← Plugin goes here
        mod/
        theme/
        ...
    config.php                      ← Outside web root (more secure)
    lib/                            ← Outside web root
```

All other installation steps are identical. The plugin's URL paths (e.g. `/local/edzaiaxisfront/styles.css`) remain unchanged because the web root now points into `/public/`, so `/local/` still maps correctly.

---

## 14. Moodle Version Compatibility

| Moodle | PHP min | Status | Notes |
|--------|---------|--------|-------|
| 4.1 LTS | 8.1 | ✅ Supported | Minimum supported version |
| 4.2 | 8.1 | ✅ Supported | |
| 4.3 | 8.1 | ✅ Supported | |
| 4.4 | 8.2 | ✅ Supported | Hooks API active (db/hooks.php preferred) |
| 5.0 | 8.2 | ✅ Supported | Hooks API required; /public dir structure |
| 5.1 | 8.2 | ✅ Supported | Tested; deploy to public/local/edzaiaxisfront/ |
| 3.x | any | ❌ Not supported | Privacy API v2 missing |

### Key 5.1 changes handled by this plugin

| Change | Impact on plugin | How we handle it |
|--------|-----------------|-----------------|
| PHP 8.2 minimum | All PHP code must be 8.2-clean | Plugin uses no deprecated 8.1-only features |
| `/public` directory restructure | Plugin moves to `public/local/` | No code changes needed; URL paths are identical |
| PSR-14 Hooks API for `before_footer` | lib.php callback deprecated | `db/hooks.php` + `classes/hook/output_callbacks.php` added |
| `$PAGE->requires->css()` expects `moodle_url` | Raw string paths emit warnings | Updated to `new moodle_url(...)` in both lib.php and hook class |
| `after_config` callback | Still valid in 5.1; no deprecation yet | No change needed |
| External web services API | No breaking changes in 5.1 | No change needed |
| AMD module system | No breaking changes | No change needed |
| Mustache templates | No breaking changes | No change needed |
| Privacy API | No breaking changes | No change needed |
| Scheduled tasks | No breaking changes | No change needed |
