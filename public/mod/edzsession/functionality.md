# mod_edzsession — Functionality Specification

**Component:** `mod_edzsession`
**Type:** Activity module (`mod`)
**Target:** Moodle 5.0+ (PHP 8.1+)
**Owner project:** EDZLMS (AIMA + GAIL instances)
**Status:** Phases 1–4 built (see `technical.md §11`); P5/P6 remaining
**Author:** Vidya (EDZLMS plugin agent)
**Admin setup:** see `admin_setup_guide.md`

---

## 1. Why this plugin exists

AIMA and GAIL run **recurring live online classes** (weekly sessions) delivered over Zoom.
The stock `mod_zoom` plugin does not fit because:

1. **Multiple Zoom accounts.** The client uses several *separate* Zoom accounts (cost / licence driven). `mod_zoom` supports only ONE site-wide Server-to-Server OAuth credential set.
2. **Session model.** Classes are recurring weekly sessions, not single one-off meetings.
3. **Attendance-driven completion.** Completion must be based on *actual attendance* pulled from Zoom's Reports API (attended ≥ X%, ≥ N minutes, ≥ N of M sessions), not a manual tick.
4. **Recording offload.** Zoom cloud storage is limited. Recordings must be automatically pushed off Zoom to a long-term store (Vimeo today) and surfaced to students as embeds — replacing a manual upload workflow.
5. **Productization.** EDZLMS wants to sell this to other customers, so it must be clean-room (no `mod_zoom` fork) and **provider-agnostic**: different customers will want a different recording store (Vimeo, AWS S3, Google Drive, YouTube, Bunny, …) and, eventually, a different meeting platform (BigBlueButton, MS Teams, Google Meet).

## 2. Design pillars

| Pillar | What it means |
|---|---|
| **Multi-account** | Store many Zoom credential sets (encrypted). Each session/activity picks which account hosts it. |
| **Pluggable storage** | The recording *destination* is a swappable **storage provider**. Vimeo ships first; S3/Drive/YouTube added later as new classes with **zero core changes**. Selectable per-site (default) and overridable per-activity. |
| **Pluggable meeting platform** | The meeting *source* is a swappable **meeting provider**. Zoom ships first; BigBlueButton/Teams later. Same registry pattern as storage. |
| **Attendance = completion** | Attendance is polled from the meeting provider's report API, reconciled to Moodle users, and drives custom completion rules. |
| **Resilient pipeline** | Recording offload is a persisted, idempotent state machine (adhoc tasks + retry/backoff), survives failures, never double-uploads, and only deletes the source after verified. |

## 3. Roles & user stories

**Administrator**
- Adds/edits/removes Zoom credential accounts (multi-account vault).
- Chooses the **default storage provider** and configures its credentials (e.g. Vimeo PAT + default folder).
- Enables/disables individual providers; sees which are configured.
- Sets global policies: attendance % basis, source-deletion policy + grace period, retention.

**Teacher (in a course)**
- Adds an "EDZ Session" activity, picks which Zoom account hosts it, sets schedule (single or recurring).
- Optionally overrides the storage destination folder/project for this activity's recordings.
- Sees a roster of occurrences with per-student attendance and can **manually reconcile** unmatched participants.
- Sees recording processing status and the final embed link once verified.

**Student**
- Sees upcoming/past occurrences, a Join button (opens the meeting), their own attendance, and watches recordings inline (privacy-locked embed) once available.

## 4. Screens (all built unless noted)

1. **Admin: Zoom accounts** — `manage_accounts.php`. CRUD list of credential sets. Secrets encrypted; blank-to-keep on edit. ✅
2. **Admin: settings + storage providers** — `settings.php`. Defaults + policies; each provider self-registers its own settings block (Vimeo full; S3 skeleton). ✅
3. **mod_form (add/edit activity)** — name, intro, Zoom account selector, recurrence builder (single/weekly, interval, weekdays, end by count/date), start + duration, storage override + folder, completion rules. ✅
4. **view.php (student)** — occurrence list, Join buttons, domain-locked recording embeds, teacher link to manage. ✅
5. **manage.php (teacher)** — per-occurrence attendance grid, reconcile unmatched participants (preserves manual overrides), re-poll attendance. ✅
6. **index.php** — course-level list of EDZ Sessions. ✅

*Not yet built (P5/P6):* attendance/recording report-builder view, quota + licence-utilisation dashboards.

## 5. Settings inventory (site-level)

- `defaultmeetingprovider` (select — zoom)
- `defaultstorageprovider` (select — vimeo | s3 | drive | none)
- Vimeo provider: `pat`, `defaultfolder`, `embeddomain` (needs token scopes incl. `interact` for folders)
- S3 provider: `region`, `bucket`, `accesskey`, `secretkey`, `endpoint` (optional), `cdnbase` (recommended)
- Google Drive provider: `serviceaccount` (JSON key), `shareddrive` (required), `defaultfolder`, `domain` (optional)
- Attendance: `attendancebasis` (meeting_duration | scheduled_duration), `matchstrategy` (email | name | registrantid)
- Recording: `sourcedeletionpolicy` (never | after_verified | after_verified_grace), `gracehours`, `retentiondays`
- Zoom accounts are stored in their own table, not config.

## 6. Completion rules (custom completion API)

- `completionattendancepercent` — attended ≥ X% of session duration.
- `completionminutes` — attended ≥ N minutes total.
- `completionsessions` — attended ≥ N of M occurrences (for recurring series).
Rules are additive (all enabled must pass) — documented default: any single enabled rule OR all, configurable per activity (default: ALL enabled must pass).

## 7. Out of scope (this build)

- Live in-Moodle video (we deep-link to the provider's join URL).
- Bulk media hosting / bandwidth resale.
- Payment/licence management.
- BigBlueButton/Teams meeting implementations (interface only; deferred).
- YouTube / Bunny and other storage implementations (Vimeo, Amazon S3 and Google Drive are implemented; others slot in via the same interface).

## 8. External prerequisites (runtime, not code)

These block *go-live*, not the code build. Flagged for the team:
1. Vimeo app **upload access** approval + PAT scopes (upload/edit/delete/video_files/private).
2. Confirm Vimeo plan weekly upload quota + total storage.
3. Confirm Zoom cloud-recording download settings allow tokenized-URL download.
4. Prototype Vimeo **pull** upload against a real Zoom recording URL (token-expiry risk).
5. Student-recording retention / consent policy.
6. Verify Zoom licence-pooling behaviour on their plan (may allow account consolidation — worth a 10-min check with the Zoom rep).
