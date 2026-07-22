# mod_edztrackvideo — EDZ Track Video

A Moodle 5.x activity module that embeds a single video from **YouTube, Vimeo, or a local upload** inside a custom, locked-down player with forward-seek lock, automatic resume, optional speed lock, and percentage-watched completion tracking.

Source-neutral successor to `mod_edzyoutube`.

## Features
- One activity, three sources: YouTube URL, Vimeo URL, or uploaded video file.
- Custom player skin — native provider chrome hidden, watermark overlay.
- First-view forward-seek lock (backward rewind always allowed). True hard lock for local video; detect-and-rewind for YouTube/Vimeo.
- Playback-speed policy: locked to 1× by default, or teacher allows up to a configured max.
- Resume-from-last-position with dismiss.
- Percentage-watched completion via Moodle's Completion API.
- Redesigned UI: max-watched marker, locked-ahead styling, completion ring, theme-aware colours, mobile-friendly.
- Full privacy provider, backup/restore (including uploaded files).

## Install
1. Copy the `edztrackvideo` folder to `MOODLE/mod/edztrackvideo`.
2. Site admin → Notifications → run the install.
3. Purge caches: `php admin/cli/purge_caches.php`.
4. (Recommended) build AMD: `grunt amd --root=mod/edztrackvideo` — see Deploy notes.

## Configure (per activity)
Add the activity to a course, then:
- **Video source** — YouTube / Vimeo / Upload a file.
- **Video URL** (YouTube/Vimeo) or **Video file** (upload).
- **Do not allow forward seeking in first view** — default on.
- **Allow playback speed control** + **Maximum playback speed** — default off / 1×.
- Under Activity completion: **Enable percent-based completion** + **Completion threshold (%)**.

## Uninstall
Site admin → Plugins → Activity modules → EDZ Track Video → Uninstall. Removes both DB tables and uploaded files.

## Requirements
- Moodle 4.5 / 5.x (`requires 2024100700`).
- Learner browsers need network access to youtube.com / vimeo.com for those sources.
- For local upload: site `maxbytes` / PHP upload limits sized for your videos.

## Changelog
- **1.0 (2026062700)** — initial release. Three sources, redesigned player, speed policy, full privacy + backup/restore.
