# mod_edzsession

Custom Moodle activity for **recurring live online classes** with multi-account meeting hosting, attendance-driven completion, and **pluggable recording storage** (Vimeo now; S3/Drive/YouTube later with no core changes).

Clean-room build (not a `mod_zoom` fork) — EDZLMS owns the licence and can productize it.

## Requirements
- Moodle 5.0+ / PHP 8.1+
- A meeting provider account (Zoom Server-to-Server OAuth) — supports multiple accounts.
- A storage provider for recordings (Vimeo PAT with upload access) — optional; "none" is valid.

## Install
1. Copy this folder to `MOODLE/mod/edzsession/`.
2. Site admin → Notifications → run the install.
3. `php admin/cli/purge_caches.php`.
4. Configure: Site admin → Plugins → Activity modules → EDZ Session.

## Configure
See **admin_setup_guide.md** for the full step-by-step. In short:
1. **Zoom accounts** — Site admin → Plugins → Activity modules → EDZ Session meeting accounts. Add one or more credential sets (encrypted at rest).
2. **Default storage provider** — on the EDZ Session settings page, pick Vimeo (or "None"); enter the Vimeo PAT + default folder + embed domain.
3. **Global policies** — attendance basis, participant matching, source-deletion policy + grace, retention days.
4. **Cron + (optional) webhooks** — ensure Moodle cron runs; optionally point a Zoom "Recording Completed" webhook at `/mod/edzsession/webhook.php`.

## What works today
Multi-account Zoom meeting creation, single/weekly recurrence with occurrence generation, attendance polling + reconcile UI + attendance-based completion, and the full recording offload pipeline to Vimeo (pull-preferred, stream fallback) with a signature-verified webhook. Amazon S3 is a working skeleton that proves the pluggable-storage design. See `technical.md §11` for the phase map and what remains (reports, quota dashboard, S3 upload fill-in).

## Adding a new storage provider (the whole point of the architecture)
1. Create `classes/local/storage/provider/<name>_provider.php` implementing `storage_provider`.
2. Add one line to `provider_manager::STORAGE_DRIVERS`.
3. Add its lang strings + settings via the provider's own `add_settings()`.
No edits to the pipeline, DB, or UI. Same recipe for meeting providers (`meeting_provider`).

## Uninstall
Standard Moodle plugin uninstall. `db/uninstall.php` drops plugin tables; recordings already on the storage provider are **not** deleted by uninstall (by design).

## Status / changelog
- **0.1.0 (build 2026072402)** — Phases 1–4 built:
  - P1 — provider abstraction (storage + meeting interfaces + registry), installable core, full privacy provider.
  - P2 — encrypted multi-account vault, recurrence engine, meeting lifecycle wiring, occurrence generation.
  - P3 — attendance engine, reconcile/re-poll UI, attendance-based custom completion.
  - P4 — offload pipeline state machine (per-recording lock, retry/backoff, idempotent), discovery + process tasks, webhook endpoint, student embeds. Vimeo pull + stream fallback complete; S3 skeleton.
  - Backup/restore (moodle2) and a schedule-engine unit test included.
- Pending (P5/P6): report datasource, quota + licence dashboards, calendar/reminders, transcript captions, S3 upload fill-in, Zoom source-deletion. See technical.md §11.

## Related EDZLMS agents
- **Rupa** — theme/UI for the student/teacher screens.
- **Ravi** — any external service (axis-ai) integration.
