# mod_edzsession — Administrator Setup Guide

**Plugin:** EDZ Session (`mod_edzsession`) · Moodle 5.0+ (works on 4.5+)
**Audience:** Moodle site administrator
**Version:** 0.1.0 (build 2026072402)

This guide takes a site from "plugin uploaded" to "teachers can run recurring live classes with attendance-based completion and automatic recording offload." Work through the sections in order. Anything marked *optional* can be skipped for a first run.

---

## 1. What this plugin does

EDZ Session is a Moodle activity for **recurring live online classes**. Each activity represents a class that meets on a schedule (single or weekly). It hosts meetings on **Zoom** using one of several credential accounts you configure, pulls **attendance** from Zoom's participant reports to drive activity completion, and automatically **offloads recordings** off Zoom's cloud storage to a long-term store (Vimeo out of the box; other backends are pluggable). Students get a join button and, once processed, an embedded recording.

Two things are configurable and pluggable: the **meeting platform** (Zoom today) and the **recording storage backend** (Vimeo today, Amazon S3 as a skeleton, others addable without code changes to the core). You choose a site default for each and can override storage per activity.

---

## 2. Before you start — prerequisites

You will need the following ready. The first two are external accounts; gather their credentials before configuring the plugin.

A **Zoom Server-to-Server OAuth app** for each Zoom account you want to host classes on. In the Zoom App Marketplace, create a *Server-to-Server OAuth* app and note its **Account ID**, **Client ID**, and **Client Secret**. Grant it recording and report scopes (`recording:read:admin`, `report:read:admin`, `meeting:write:admin`, `meeting:read:admin`). If you want webhooks, also note the app's **Secret Token**. You can repeat this for multiple separate Zoom accounts — that is the reason this plugin exists.

A **Vimeo account with upload access** (only if you want recording offload). Create a Vimeo API app and generate a **Personal Access Token (PAT)** with `upload`, `edit`, `delete`, `video_files`, and `private` scopes, and request **upload access** approval from Vimeo (this has a lead time — do it early). If you do not want offload yet, you can set storage to "None" and add Vimeo later.

**Moodle cron must be running** on a short interval (every minute is standard). The offload pipeline and attendance polling run as scheduled tasks; without cron they never fire.

Administrator access to **Site administration**, and the ability to run CLI commands on the server (for cache purge and priming) is helpful but not required.

---

## 3. Install the plugin

1. Copy the `edzsession` folder into your Moodle at `MOODLE/mod/edzsession/` (so `version.php` sits at `MOODLE/mod/edzsession/version.php`).
2. In the browser, go to **Site administration → Notifications**. Moodle detects the new plugin — click through to run the install. You should see the database tables created with no errors.
3. Purge caches: **Site administration → Development → Purge caches**, or on the CLI run `php admin/cli/purge_caches.php`.

If the install reports an error, stop and check it before continuing — do not proceed with a half-installed plugin.

---

## 4. Add your Zoom account(s)

The plugin stores multiple Zoom credential sets in an encrypted vault. Secrets are encrypted at rest using Moodle's site encryption key.

1. Go to **Site administration → Plugins → Activity modules → EDZ Session meeting accounts** (also reachable at `/mod/edzsession/manage_accounts.php`).
2. Click **Add account** and fill in: a friendly **Account name** (e.g. "AIMA Zoom – Main"), the provider (**Zoom**), the **Account ID**, **Client ID**, **Client secret**, and — if using webhooks — the **Webhook secret token**. Leave **Enabled** ticked.
3. Save. Repeat for each separate Zoom account.

When editing an existing account later, leave the secret fields **blank** to keep the stored values; only enter them to change them. The secret values are never sent back to the browser.

---

## 5. Choose and configure the recording storage backend

1. Go to **Site administration → Plugins → Activity modules → EDZ Session** (the settings page).
2. Set **Default storage provider**:
   - **Vimeo** — recordings are pushed to Vimeo and embedded, domain-locked and non-downloadable.
   - **Amazon S3 (skeleton)** — appears in the list to prove the architecture; its settings save, but the upload path is not wired yet. Do not select it for production until the upload code is completed.
   - **None** — recordings stay on Zoom only; no offload happens.
3. If you chose **Vimeo**, scroll to the **Vimeo** settings block and enter your **Vimeo access token (PAT)**, an optional **default folder (project)** name, and the **embed domain** (your Moodle host, e.g. `learn.example.edu`). The embed domain is what Vimeo will allow the videos to play on.

You can override the storage provider and folder per activity when adding a session, so different courses can use different destinations.

---

## 6. Set global policies

Still on the EDZ Session settings page:

**Default meeting provider** — leave as Zoom.

**Attendance basis** — whether attended percentage is measured against the *actual* meeting duration (recommended) or the *scheduled* duration.

**Participant matching** — how Zoom participants are matched to Moodle users. **By email** is the most reliable (participants should join with the email on their Moodle account). *By name* is a fallback and less reliable. Unmatched participants are surfaced to teachers for manual reconciliation.

**Delete source recording** — what happens to the original Zoom recording after a verified copy exists on the storage provider. Options: **Never** (default, safest), **After verified**, or **After verified + grace period**. Deletion never happens before the copy is verified. Note: for Zoom, source deletion is not yet implemented, so any option other than "Never" will safely leave the source in place and log that deletion was skipped.

**Grace period (hours)** and **Retention (days)** — supporting values for the deletion policy; retention of 0 means keep stored recordings forever.

Save changes.

---

## 7. Confirm the scheduled tasks

The plugin registers three scheduled tasks. Check them at **Site administration → Server → Tasks → Scheduled tasks** (search "edzsession"):

- **Poll session attendance** — pulls participant reports for finished sessions and reconciles attendance (default every hour at :30).
- **Discover new session recordings** — finds recordings for finished sessions that did not arrive via a webhook (hourly at :15).
- **Process recording offload pipeline** — advances each recording one step through upload/verify/finalize (every 5 minutes).

The default schedules are fine. Ensure Moodle cron itself is running. To prime things immediately after setup you can run, on the CLI:

```
php admin/cli/scheduled_task.php --execute="\mod_edzsession\task\process_pipeline"
```

---

## 8. Webhooks (optional, recommended)

Webhooks make recording offload near-immediate instead of waiting for the hourly discovery poll.

1. In your Zoom Server-to-Server OAuth app, add an **Event Subscription** and set the endpoint URL to `https://YOUR-MOODLE/mod/edzsession/webhook.php`.
2. Subscribe to the **Recording Completed** event.
3. Zoom will send a validation request; the plugin responds to it automatically using your account's secret token (make sure the **Webhook secret token** is filled in on the matching account in step 4).
4. Save and activate the subscription.

The endpoint verifies every request's signature against the matching account before acting; unsigned or unknown requests are rejected. If you run multiple Zoom accounts behind one endpoint, the account is identified from the event payload.

---

## 9. How a teacher creates a session

Share this with teachers. In a course, **Add an activity or resource → EDZ Session**. They set the name, pick the **Host account** (one of your Zoom accounts), choose **Single session** or **Weekly recurring** (with interval, weekdays, and an end by count or date), set start time and duration, optionally override the **storage provider/folder**, and set completion rules. On save, the plugin creates the Zoom meeting and generates the occurrence rows.

If no meeting account is attached or the credentials fail, the activity still saves and lists its scheduled occurrences, but no live meeting is created — the teacher will see a notice and can fix the account and re-save to retry.

---

## 10. Attendance and reconciliation

After a session ends, the attendance task pulls the participant report, dedupes each person's join/leave segments, computes their attended percentage, and matches them to Moodle users. Teachers open **Manage attendance & recordings** from the activity to see the grid, **re-poll** attendance on demand, and **assign** any unmatched participants to the right user. Manual assignments are preserved even if attendance is re-polled later.

Completion rules (attendance %, minutes attended, sessions attended) are recomputed automatically after each poll and after any manual reconcile.

---

## 11. Recordings and the offload pipeline

Once a recording exists, it moves through a resilient, resumable pipeline: discovered → selected → quota checked → uploading → processing → verified → finalized (→ source deleted, only if your policy allows and the copy is verified). Each recording is offloaded exactly once (guaranteed by a unique key), and failed steps retry with backoff before parking as "failed" for review. Students see "Processing…" until the copy is finalized, then an embedded, domain-locked player.

If you use Vimeo, the plugin prefers a **pull upload** (Vimeo fetches the recording directly from Zoom — no bandwidth through your server) and falls back to streaming if needed.

---

## 12. Adding a different storage backend later (no core changes)

The storage backend is a swappable provider. To add, say, Google Drive or a different S3-compatible store, a developer creates one class implementing the `storage_provider` interface under `classes/local/storage/provider/`, registers it with a single line in `provider_manager::STORAGE_DRIVERS`, and adds its settings via the provider's own `add_settings()`. It then appears in the storage dropdown automatically — no changes to the pipeline, database, forms, or student views. The same pattern applies to meeting platforms (`meeting_provider`) for future BigBlueButton/Teams support.

---

## 13. Troubleshooting

**"The remote meeting could not be created."** The selected Zoom account's credentials are wrong or lack scopes. Re-check the Account/Client ID and secret in the accounts page, confirm the S2S app has the meeting/recording/report scopes, then edit and re-save the activity to retry.

**Attendance shows lots of "Unmatched" rows.** Participants joined with a name/email that doesn't match their Moodle account. Set matching to **By email** and ask learners to join with their Moodle email, or reconcile manually from the Manage page.

**Recordings never appear.** Confirm cron is running and the three scheduled tasks are enabled. Check that the activity's storage provider isn't "None." For Vimeo, confirm the PAT is valid and upload access is approved. Look at the recording's pipeline log (in the database `edzsession_pipeline_log`) or the failed state for the last error.

**S3 upload throws "not implemented."** Expected — S3 is a skeleton provider in this build. Use Vimeo, or have a developer complete the S3 upload methods.

**Vimeo playback blocked.** The embed domain in settings must match the domain your Moodle is served from.

---

## 14. Security and privacy notes

All Zoom client secrets and webhook tokens are **encrypted at rest** and never shown in the browser or written to logs. The webhook endpoint verifies signatures before acting on any payload. Recordings are locked to domain-whitelisted, non-downloadable embeds by default. The plugin stores per-user attendance and implements Moodle's Privacy (GDPR) API, so attendance data is included in data export and deletion requests. Uninstalling the plugin does **not** delete recordings already stored on your provider — that is deliberate, to avoid destroying a customer's media.
