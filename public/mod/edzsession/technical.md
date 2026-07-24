# mod_edzsession — Technical Design

**Component:** `mod_edzsession` · Moodle 5.0+ / PHP 8.1+
**Author:** Vidya · EDZLMS
**Status:** Phases 1–4 built (§11). Admin setup: `admin_setup_guide.md`.

This document is the source of truth for *how* the plugin is structured. The headline goal — the reason we are writing the structure "this way" now — is that **both the recording storage backend and the meeting platform are pluggable**. Nothing in the core references "Vimeo" or "Zoom" directly; core talks only to interfaces.

---

## 1. The extensibility principle (read this first)

Two extension points, both built on the **same** pattern (interface + registry + capability-declaring drivers):

```
                 ┌──────────────────────── mod_edzsession core ───────────────────────┐
                 │  activity CRUD · occurrences · attendance engine · completion ·     │
                 │  offload pipeline (state machine) · reports · UI                    │
                 └────────────┬───────────────────────────────────┬────────────────────┘
                              │ talks only to                      │ talks only to
                     meeting_provider (interface)          storage_provider (interface)
                              │                                    │
              ┌───────────────┴───────────┐        ┌───────────────┼─────────────────────┐
        zoom_provider   (bbb_provider)*  (teams)*   vimeo_provider  s3_provider   (drive/youtube/bunny)*
        [implemented]     [future]      [future]    [implemented]   [skeleton]    [future]

        * not shipped in this build — interface reserved, adding one is a new class only
```

**Rule:** to add a provider you drop ONE class implementing the interface + register it. No edits to `lib.php`, `view.php`, the pipeline, the attendance engine, or the DB schema. This is what makes it sellable per-customer.

### Why a registry (not just Moodle sub-plugins) — decision
We keep providers as **internal driver classes discovered by a registry**, not as separate Moodle sub-plugin types, for v1. Rationale: fewer moving parts, single install, secrets live in one settings tree, and we control the release cadence. The registry is written so it can later delegate to a `mod_edzsession_store` / `mod_edzsession_meet` sub-plugin type (via `core_component::get_plugin_list()`) **without changing the interface** — third-party providers become a v2 concern. Documented here so future-us doesn't repaint the whole thing.

---

## 2. Storage provider contract

`mod/edzsession/classes/local/storage/storage_provider.php` (interface).

Every storage driver declares its identity, its capabilities, its settings, and implements a **normalized offload lifecycle**. The pipeline (Section 6) calls these methods; it never knows which driver it is holding.

```php
interface storage_provider {
    // --- identity ---
    public static function get_name(): string;              // 'vimeo'
    public static function get_display_name(): string;       // get_string('provider_vimeo', ...)
    public function is_configured(): bool;                    // creds present & valid-shaped

    // --- capability flags (pipeline branches on these, never on the name) ---
    public function supports_pull_upload(): bool;            // provider fetches source URL itself
    public function supports_stream_upload(): bool;          // we stream bytes through (tus/multipart)
    public function supports_folders(): bool;                // destination "folders/projects/prefixes"
    public function supports_captions(): bool;               // can attach a VTT track
    public function supports_delete(): bool;                 // can delete a stored asset
    public function get_quota(): ?storage_quota;             // null if provider has no quota concept

    // --- destination folders (only if supports_folders) ---
    public function list_folders(): array;                   // [id => label] for the mod_form picker
    public function ensure_folder(string $label): string;    // create-or-get, returns folder id

    // --- the offload lifecycle (return value objects, never bools) ---
    public function begin_upload(upload_request $req): upload_handle;   // pull OR open stream
    public function push_chunk(upload_handle $h, string $bytes): void;  // stream mode only
    public function finalize_upload(upload_handle $h): stored_asset;    // returns provider asset id
    public function poll_processing(stored_asset $a): processing_status;// transcode/verify state
    public function apply_privacy(stored_asset $a, privacy_spec $s): void;
    public function move_to_folder(stored_asset $a, string $folderid): void;
    public function attach_caption(stored_asset $a, string $vtt, string $lang): void;
    public function get_embed(stored_asset $a): embed_info;             // iframe/src + player meta
    public function delete_asset(stored_asset $a): void;

    // --- admin settings contribution ---
    public static function add_settings(\admin_settingpage $page): void;
    public static function config_prefix(): string;          // 'edzstore_vimeo'
}
```

Supporting value objects (all under `classes/local/storage/`): `upload_request`, `upload_handle`, `stored_asset`, `processing_status`, `privacy_spec`, `embed_info`, `storage_quota`. Using value objects (not associative arrays) means adding a field later doesn't silently break a driver.

### 2.1 vimeo_provider (implemented)
- Pull upload: `POST /me/videos` with `upload.approach=pull`, `link = <zoom download_url>?access_token=<download_token>`. Zero infra — Vimeo fetches directly.
- Stream fallback: `upload.approach=tus`, chunked `PATCH`. Pipeline auto-falls-back pull→tus.
- Folders = Vimeo **projects**: `GET/POST /me/projects`, `PUT /me/projects/{pid}/videos/{vid}`.
- Privacy: `privacy.view=disable` + `embed=whitelist` + `PUT /videos/{id}/privacy/domains/{domain}` + `download=false`.
- Poll: `GET /videos/{id}?fields=upload.status,transcode.status`.
- Quota: `GET /me?fields=upload_quota`.
- Captions: `POST /videos/{id}/texttracks` (VTT from Zoom transcript).
- All HTTP via Moodle `curl` class; PAT read from encrypted config; rate-limit aware (429 → backoff).

### 2.2 s3_provider (skeleton — proves the abstraction)
- Declares `supports_pull_upload()=false`, `supports_stream_upload()=true`, `supports_folders()=true` (prefixes), `supports_captions()=true` (sidecar `.vtt`), `supports_delete()=true`, `get_quota()=null`.
- `begin_upload/push_chunk/finalize_upload` are stubbed with a clearly-marked `// TODO S3 multipart` body and throw `not_implemented_yet` if invoked, but the class **loads, registers, appears in the admin dropdown, and exposes its settings** (region/bucket/keys/endpoint/cdnbase). This is deliberate: it demonstrates a second provider slotting in with no core edits, and gives the next engineer the exact seams to fill.
- Embed = a signed CDN URL wrapped in a `<video>` tag via `get_embed()`.

---

## 3. Meeting provider contract

`classes/local/meeting/meeting_provider.php` (interface). Same shape, meeting-side concerns:

```php
interface meeting_provider {
    public static function get_name(): string;               // 'zoom'
    public static function get_display_name(): string;
    public function is_configured(account $a): bool;

    public function create_meeting(meeting_spec $s, account $a): remote_meeting;
    public function update_meeting(remote_meeting $m, meeting_spec $s, account $a): void;
    public function delete_meeting(remote_meeting $m, account $a): void;
    public function get_join_url(remote_meeting $m, \stdClass $user): string;

    // attendance
    public function fetch_participants(remote_meeting $m, account $a): array; // participant_record[]
    public function supports_webhooks(): bool;
    public function verify_webhook(string $payload, array $headers, account $a): bool;

    // recordings (the bridge into the storage pipeline)
    public function list_recordings(remote_meeting $m, account $a): array;    // recording_asset[]
    public function get_download(recording_asset $r, account $a): download_ref;// url + token
    public function delete_recording(recording_asset $r, account $a): void;
}
```

`account` = one entry from the multi-account vault. `zoom_provider` implements Server-to-Server OAuth per account (token cached per account id). Key gotchas baked into the impl: double-URL-encode occurrence UUIDs; dedupe multiple join/leave segments per participant in `fetch_participants`.

---

## 4. Provider registry

`classes/local/provider_manager.php` — one manager, two typed facades.

```php
class provider_manager {
    // storage
    public static function storage_providers(): array;        // ['vimeo'=>class, 's3'=>class]
    public static function get_storage(string $name): storage_provider;
    public static function default_storage(): storage_provider; // from config, null-object if 'none'
    // meeting
    public static function meeting_providers(): array;         // ['zoom'=>class]
    public static function get_meeting(string $name): meeting_provider;
    public static function default_meeting(): meeting_provider;
}
```

v1 discovery = a hardcoded map inside the manager (`STORAGE_DRIVERS`, `MEETING_DRIVERS`) — adding a provider means adding one line here + the class. (v2 seam: swap the map for `core_component` sub-plugin discovery; interface unchanged.) A `null_storage_provider` (no-op) backs the "none" option so pipeline code never null-checks.

---

## 5. Data model (db/install.xml)

| Table | Purpose | Key columns |
|---|---|---|
| `edzsession` | activity instance | id, course, name, intro, meetingprovider, zoomaccountid, storageprovider(nullable→default), storagefolderid, storagefoldername, schedule (json/recur fields), completion rule cols, timemodified |
| `edzsession_account` | multi-account Zoom vault | id, name, provider, accountid, clientid, **clientsecret_enc**, verificationtoken_enc, enabled, timecreated |
| `edzsession_occurrence` | one scheduled sitting of a session | id, edzsessionid, remotemeetingid, remoteuuid, starttime, duration, status, joinurl |
| `edzsession_attendance` | reconciled attendance per user per occurrence | id, occurrenceid, userid, matchedname, matchedemail, joinseconds, attendedpercent, matchstate(auto/manual/unmatched), timemodified |
| `edzsession_attendance_raw` | raw participant segments before reconcile | id, occurrenceid, name, email, registrantid, jointime, leavetime |
| `edzsession_recording` | pipeline row per source recording (idempotent) | id, occurrenceid, source_uuid (**unique**), storageprovider, state, assetid, embedjson, quotachecked, attempts, lasterror, timecreated, timemodified |
| `edzsession_pipeline_log` | audit of state transitions | id, recordingid, fromstate, tostate, message, time |

Secrets (`*_enc`) encrypted with Moodle `\core\encryption` (or `encrypt()`/`decrypt()` from `/lib/encryption`). Never stored plaintext. Never logged.

`source_uuid` UNIQUE index = the idempotency guarantee: a recording is offloaded exactly once no matter how many webhooks/polls fire.

---

## 6. Offload pipeline (persisted state machine)

State column on `edzsession_recording`. Transitions driven by adhoc tasks; each transition is idempotent and re-entrant.

```
discovered → selected → quota_checked → uploading → processing → verified
   → finalized → source_deleted            (any step → failed[retryable])
```

- **discovered** — from Zoom webhook `recording.completed` OR reconcile poll. Insert row (unique source_uuid → dupes ignored).
- **selected** — choose main MP4; keep VTT as caption sidecar.
- **quota_checked** — `provider->get_quota()`; if provider returns null (e.g. S3) skip; if over quota → `failed`+alert.
- **uploading** — `begin_upload` (pull if `supports_pull_upload`, else stream via `push_chunk`); auto-fallback pull→tus.
- **processing** — `poll_processing` until transcode done.
- **verified** — provider confirms playable.
- **finalized** — `apply_privacy` + `move_to_folder` + `attach_caption` + store `embed_info` json.
- **source_deleted** — ONLY if policy allows AND verified; optional grace window; `meeting_provider->delete_recording()`.

Retry/backoff via adhoc task `nextruntime` (exp backoff, cap attempts → `failed` + admin notification). Each step writes a `pipeline_log` row. The pipeline calls **only interface methods**, so an S3 offload runs the identical state machine.

Tasks:
- `\mod_edzsession\task\discover_recordings` (scheduled, hourly) — reconcile poll for meetings missing a webhook.
- `\mod_edzsession\task\process_pipeline` (scheduled, every 5 min) — advance all non-terminal recordings one step.
- `\mod_edzsession\task\poll_attendance` (scheduled) — pull participants for finished occurrences, reconcile.
- Adhoc `run_recording_step` queued per recording for responsiveness.

---

## 7. Attendance & completion

- `attendance_engine` (helper) pulls `fetch_participants`, writes `attendance_raw`, then reconciles:
  match by strategy (email → registrantid → fuzzy name), dedupe segments, sum join seconds, compute `attendedpercent` on configured basis (meeting vs scheduled duration). Unmatched rows surface in the teacher reconcile UI (the #1 support burden — first-class feature, not an afterthought).
- Completion via `\mod_edzsession\completion\custom_completion` (`\core_completion\activity_custom_completion`): rules `completionattendancepercent`, `completionminutes`, `completionsessions`. Recomputed after each `poll_attendance`.

---

## 8. Class map (files)

```
classes/local/storage/storage_provider.php        interface
classes/local/storage/provider/vimeo_provider.php implemented
classes/local/storage/provider/s3_provider.php    skeleton
classes/local/storage/null_storage_provider.php   no-op ('none')
classes/local/storage/{upload_request,upload_handle,stored_asset,
        processing_status,privacy_spec,embed_info,storage_quota}.php  value objects
classes/local/meeting/meeting_provider.php         interface
classes/local/meeting/provider/zoom_provider.php   implemented
classes/local/meeting/{account,meeting_spec,remote_meeting,
        recording_asset,download_ref,participant_record}.php          value objects
classes/local/provider_manager.php                 registry (storage + meeting)
classes/local/schedule.php                         recurrence -> occurrence times
classes/local/session_manager.php                  meeting lifecycle + occurrence sync
classes/local/account_vault.php                    encrypted multi-account CRUD
classes/local/attendance/attendance_engine.php     poll -> dedupe -> reconcile -> completion
classes/local/pipeline/states.php                  state constants (+ PENDING_DELETE)
classes/local/pipeline/pipeline_manager.php        state machine driver (per-recording lock)
classes/local/pipeline/discovery.php               reconcile-poll recording discovery
classes/completion/custom_completion.php           attendance-based rules
classes/task/{discover_recordings,process_pipeline,poll_attendance}.php  scheduled
classes/task/run_recording_step.php                adhoc (per recording)
classes/form/account_form.php                       credential form
classes/privacy/provider.php                        full provider (stores attendance)
tests/schedule_test.php                             PHPUnit (schedule engine)
lib.php  mod_form.php  view.php  index.php  settings.php  version.php
manage.php  manage_accounts.php  webhook.php
db/{install.xml,access.php,tasks.php,events.php,caches.php,upgrade.php,uninstall.php}
backup/moodle2/{backup,restore}_edzsession_{activity_task.class,stepslib}.php
```

*Reserved for later (not yet built):* `classes/external/*` (AJAX reconcile/status),
`classes/event/*` (custom events), `db/services.php`. The current reconcile UI is
server-side (no AMD), so no external/AJAX layer is required yet.

---

## 9. Caching
- `edzsession/occurrences` (application) — occurrence list per activity, invalidated on schedule edit / poll.
- `edzsession/quota` (application, TTL 30 min) — provider quota to avoid hammering the API.
- Provider access tokens cached per account (`edzsession/tokens`, TTL from expiry).

## 10. Security & privacy
- All provider secrets + Zoom client secrets encrypted at rest; `require_capability` at every entry point (`mod/edzsession:manageaccounts`, `:reconcile`, `:view`, `:addinstance`).
- Webhook endpoint verifies signature via `meeting_provider->verify_webhook` before trusting payload (no acting on unverified external content).
- Recordings default to domain-whitelisted, non-downloadable embeds.
- `classes/privacy/provider.php` exports/deletes attendance + reconcile data.

## 11. Build phases
- **P1 (done):** spec docs + provider abstraction (interfaces, registry, Vimeo full, S3 skeleton, Zoom provider) + installable core scaffold. Installs cleanly, providers show in admin.
- **P2 (done):** encrypted multi-account vault (CRUD page + form + `\core\encryption`), schedule/recurrence engine (weekly, interval, N-of-M / until, capped at 200), meeting lifecycle wired into add/update/delete_instance, occurrence generation + resync.
- **P3 (done):** attendance engine (poll → dedupe segments → reconcile by email/registrant/name → % on configured basis), poll task, custom completion aggregation (percent/minutes/sessions), teacher reconcile + re-poll page (server-side, preserves manual overrides).
- **P4 (done):** offload pipeline state machine (discovered→…→finalized/source_deleted) with per-recording lock + retry/backoff, discovery + process tasks, adhoc per-recording task, webhook endpoint (signature-verified, url-validation handshake), student recording embeds. Pull path complete; tus/stream fallback implemented.
- **P5 (remaining):** report-builder datasource, quota dashboard, calendar integration + reminders, transcript→caption sidecar, S3 upload fill-in, privacy `export_user_data` payload.
- **P6 (remaining):** licence-utilisation view, pooling helpers, broader unit/behat coverage.

Backup/restore (moodle2) is implemented (activity + occurrences + recordings + user attendance under userinfo). One PHPUnit test covers the schedule engine.
