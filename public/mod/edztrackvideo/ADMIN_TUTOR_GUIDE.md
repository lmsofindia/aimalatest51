# EDZ Track Video — Admin & Tutor Guide

**Plugin:** EDZ Track Video (`mod_edztrackvideo`) · v1.0 (2026062700)
**What it is:** an activity that plays a video from **YouTube, Vimeo, or a file you upload**, inside a locked player that stops learners skipping ahead and records how much they actually watched. Use it where you must be able to prove a learner watched a required percentage.

This guide has two parts: **A. For the administrator** (install + deploy) and **B. For the tutor/teacher** (create + test). Part C is a tick-box test script.

---

## A. For the administrator — install & deploy

1. **Copy the plugin** to your Moodle code tree:
   `MOODLE/mod/edztrackvideo/`  (the folder containing `version.php`).
2. **Build the JavaScript (recommended):** from the Moodle root run
   `grunt amd --root=mod/edztrackvideo`
   *(The plugin also ships ready-to-run JS, so it will work even if you skip this — but a grunt build is cleaner for production.)*
3. **Run the install:** log in as admin → **Site administration → Notifications**. Confirm the upgrade. This creates two database tables (`edztrackvideo`, `edztrackvideo_progress`).
4. **Purge caches:** `php admin/cli/purge_caches.php` (or Site admin → Development → Purge caches).
5. **Check it registered:** Site admin → Plugins → Activity modules → you should see **EDZ Track Video**.

> **Updating an existing install** (e.g. to this 1.1 build): sync the folder over the old one, then **Site admin → Notifications** runs the database upgrade automatically (it adds the `serverpath` column), then purge caches. No data is lost.

### Upload size (only if tutors will upload files)
Local uploads are limited by your site/PHP limits. If tutors need large files, raise:
- Site admin → Security → Site policies → **Maximum uploaded file size**, and your PHP `upload_max_filesize` / `post_max_size`.
- You can also cap per-course upload size in course settings.

### Large videos that won't upload through the browser (Server file source)
For videos too big for the browser upload, place them on the server and let tutors pick them from a list:

1. Create a folder on the server, ideally **outside the web root**, e.g. `/var/www/moodle-videos/`. Make sure the web server user can read it.
2. SFTP your large video files into it (subfolders are fine, e.g. `module1/intro.mp4`).
3. In Moodle: **Site admin → Plugins → Activity modules → EDZ Track Video** → set **Server video folder** to that absolute path. Save.
4. Tutors will now see a **"Server file (large videos)"** source with a dropdown of those files.

Security: the plugin only ever serves files **inside** that folder (path-traversal is blocked), and only to users who can view the activity. Streaming supports seeking/resume (HTTP range). To turn the feature off, clear the folder setting.

### Direct video URL source (optional, off by default)
If you keep videos in a **web-served** folder and want tutors to just paste the link:

1. **Site admin → Plugins → Activity modules → EDZ Track Video** → tick **Allow Direct video URL source**. Save.
2. Tutors then get a **"Direct video URL"** source and paste e.g. `https://yoursite/videos/foo.mp4`.

⚠️ Trade-off: a file reachable by direct URL is **downloadable by anyone with the link** — Moodle does not gate it. Prefer the Server file source when the content is sensitive. Leave this setting off unless you accept that.

### Permissions (defaults are sensible)
- **Add the activity:** Editing teacher, Manager (`mod/edztrackvideo:addinstance`).
- **View / be tracked:** Student, Teacher, Manager (`mod/edztrackvideo:view`).
No changes needed for a standard setup.

---

## B. For the tutor — create an activity

1. In your course, turn editing on → **Add an activity or resource → EDZ Track Video**.
2. **Name** your activity (e.g. "Module 1 — Safety Briefing").
3. Choose the **Video source**:

   | If you pick… | Then… |
   |---|---|
   | **YouTube** | Paste the YouTube link in **Video URL**. `watch?v=…`, `youtu.be/…`, `embed/…`, and `shorts/…` all work. |
   | **Vimeo** | Paste the Vimeo link in **Video URL**. `vimeo.com/123…`, a private link `vimeo.com/123…/hash`, or `player.vimeo.com/video/123…` all work. |
   | **Upload a file** | Use the **Video file** picker to upload one video (mp4, webm, ogv, mov). |
   | **Server file** *(if your admin enabled it)* | Pick a large video from the **Server video file** dropdown (files the admin placed on the server). |
   | **Direct video URL** *(if your admin enabled it)* | Paste a direct link to a video file in the **Video URL** box. |

4. **Playback controls:**
   - **Do not allow forward seeking in first view** *(default ON)* — learners can rewind but not jump ahead of what they've watched, until they finish. Leave on for compliance.
   - **Allow playback speed control** *(default OFF)* — leave off to lock speed at 1× (recommended for compliance, since speeding up is a way to skim). If you turn it on, also set the **Maximum playback speed** (1.25× / 1.5× / 2×).
5. **Completion (the part that proves they watched):** scroll to **Activity completion** → set **Completion tracking = "Show activity as complete when conditions are met"**, then tick **"Mark complete when the learner watches at least a set percentage"** and enter the **Required watch percentage** (e.g. 80 or 100).
6. **Save and display.**

> Tip: set completion **when you create the activity**, before learners start. Changing completion rules after people have data can behave unexpectedly.

---

## C. Test script (do this once per source after install)

Run through this as a **test student** (use a real student account or "Log in as"). Tick each row. Repeat the playback rows for **YouTube, Vimeo, and an uploaded file**.

### C1. Setup
- [ ] Activity saved with YouTube URL → opens and the video appears
- [ ] Activity saved with Vimeo URL → opens and the video appears
- [ ] Activity saved with an uploaded file → opens and the video appears
- [ ] (If enabled) Server file: dropdown lists the admin's files; selected video opens and plays; seeking/resume works
- [ ] (If enabled) Direct URL: pasted link opens and plays
- [ ] Server file source is hidden when no admin folder is set; Direct URL hidden when the admin setting is off
- [ ] No "video could not be loaded" error on a valid source

### C2. The locked player
- [ ] Video plays/pauses with the big centre button and the bottom Play button
- [ ] Mute and the volume slider work
- [ ] Time shows "current / total"; the bar fills as it plays
- [ ] The native YouTube/Vimeo logo/menu is **not** clickable (can't click through to the site)
- [ ] Fullscreen works and the EDZ controls stay visible
- [ ] Captions (CC) button toggles where the video has captions (YouTube/Vimeo)

### C3. Forward-lock (with "Do not allow forward seeking" ON, before completion)
- [ ] Click **ahead** on the progress bar → it refuses and shows "Fast-forward is disabled for this lesson"
- [ ] Click **behind** (rewind) → allowed
- [ ] The not-yet-watched part of the bar shows a greyed/striped **locked** area with a small lock
- [ ] A small **marker** shows the furthest point watched
- [ ] (Uploaded video) dragging ahead is hard-blocked instantly
- [ ] After you watch enough to hit the completion %, the lock releases and the ring shows a ✓

### C4. Speed
- [ ] With speed control **off** → no speed selector appears; video stays at normal speed
- [ ] With speed control **on, max 1.5×** → selector appears, offers up to 1.5×, won't go higher

### C5. Resume
- [ ] Watch part way, leave the activity, come back → a **"Resume from m:ss"** button appears
- [ ] Click **Resume** → it jumps to just before where you stopped and plays
- [ ] Click **Dismiss** instead → the prompt goes away and does **not** come back on reload

### C6. Completion (the proof)
- [ ] Set threshold 100% → activity completes only after watching the whole video
- [ ] Set threshold 80% → activity completes at 80% watched
- [ ] The tick shows on the course page and in **Reports → Activity completion**
- [ ] Re-opening after completion: free seeking is now allowed

### C7. Progress is saved
- [ ] Watch, then refresh the page → your watched position is remembered (didn't reset)
- [ ] Watched percentage never goes **down**

### C8. Admin round-trips (admin does these)
- [ ] **Backup** a course containing each source, then **restore** into a new course → activities and settings come back; the uploaded file plays in the restored copy
- [ ] **Privacy:** Site admin → Users → Privacy → export a test user → their watch progress is included; delete → it's removed
- [ ] **Delete** an activity → its progress rows and uploaded file are removed

---

## Quick troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| "Video could not be loaded" | Bad/blocked URL, private Vimeo without the hash in the link, or a file type that isn't a supported video. Re-check the source. |
| YouTube/Vimeo won't play in the browser | The learner's network blocks youtube.com / vimeo.com. Use the **Upload** source instead. |
| Upload rejected as too large | Raise the site/PHP upload limits (see Part A). |
| Player looks unstyled / old JS behaviour | Purge caches; if you edited the JS, run `grunt amd` and purge again. |
| Forward-skip still possible | Confirm "Do not allow forward seeking" is ticked and the learner hasn't already completed it (the lock lifts after completion by design). |

---

## What this plugin does **not** do (yet)
- No automatic conversion of old **EDZ YouTube** activities (they keep working separately).
- No caption upload for **locally uploaded** files (YouTube/Vimeo captions do work).
- No video thumbnail/poster, no playlist, no download button.

Questions or a bug? Note the source type, the activity, and what you saw vs expected, and send it over.
