# quizaccess_edproctoring — README

On-premises AI-assisted quiz proctoring for Moodle. No external SaaS. No per-exam fees.

---

## Requirements

- Moodle 4.3 or higher (tested on 5.x)
- PHP 8.1+
- mod_quiz enabled (core — always present)
- HTTPS required (browsers block getUserMedia on plain HTTP)
- Modern browser: Chrome 90+, Firefox 88+, Edge 90+ (Safari 15.4+ with limitations)
- **Phase 2 face recognition only:** Python 3.9+, pip, systemd (Linux server)

---

## Installation

### Step 1 — Upload plugin files

Copy the `edproctoring` folder to your Moodle server:

```bash
cp -r quizaccess_edproctoring/ /var/www/moodle/quizaccess/edproctoring/
chown -R www-data:www-data /var/www/moodle/quizaccess/edproctoring/
```

### Step 2 — Run Moodle upgrade

Visit **Site administration → Notifications** and complete the plugin install. This creates the 5 database tables.

Or via CLI:
```bash
php /var/www/moodle/admin/cli/upgrade.php --non-interactive
```

### Step 3 — Purge caches

```bash
php /var/www/moodle/admin/cli/purge_caches.php
```

### Step 4 — Configure site defaults

Go to **Site administration → Plugins → Quiz access rules → Ed Proctoring**.

Key settings to configure:
- **Face recognition service URL** — leave empty (Phase 2). Set to `http://localhost:8765` when FastAPI is running.
- **Default retention days** — how long to keep captured images (default 90).
- **Institution privacy policy URL** — shown on student consent screen.
- **Default capture interval** — seconds between snapshots (default 30).

### Step 5 — Enable proctoring on a quiz

1. Open any quiz → Edit settings.
2. Expand "Extra restrictions on attempts".
3. Enable "Ed Proctoring" and configure per-quiz options.
4. Save. All future attempts will go through the proctoring flow.

---

## Phase 2 — Face Recognition Service Setup

### Install Python dependencies

```bash
pip install deepface fastapi uvicorn python-multipart pillow
```

### Start the service manually (test)

```bash
cd /var/www/moodle/quizaccess/edproctoring/fastapi_service/
uvicorn main:app --host 127.0.0.1 --port 8765
```

Verify: `curl http://localhost:8765/health` → `{"status":"ok","model":"ArcFace"}`

### Install as systemd service (production)

```bash
chmod +x /var/www/moodle/quizaccess/edproctoring/fastapi_service/install.sh
sudo /var/www/moodle/quizaccess/edproctoring/fastapi_service/install.sh
sudo systemctl enable edproctoring-face
sudo systemctl start edproctoring-face
```

### Configure Moodle to use the service

Site admin → Ed Proctoring settings → Face recognition service URL: `http://localhost:8765`

Click "Test connection" — should show green "Connected. Model: ArcFace".

---

## Viewing Reports

**Teacher:** Open any quiz → Quiz administration → Proctoring Report.
Or: `/quizaccess/edproctoring/report/attempts.php?cmid=YOUR_CMID`

**Admin (all quizzes):** Site administration → Reports → Proctoring → All sessions.

---

## Student Base Image Upload

**Student self-upload:** Users can manage their base images at:
`/quizaccess/edproctoring/baseimage.php` (link added to user profile when face recognition is enabled)

**Admin/Teacher upload:** Available in the attempts report → select student → Manage base image.

**Inline upload during quiz:** If no base image exists and face recognition is enabled, the student is prompted to upload one during the pre-flight check before the quiz starts.

---

## Permissions

By default:
- **Students** — can take proctored quizzes and upload their own base image.
- **Teachers (editingteacher)** — can configure proctoring per quiz, view reports for their courses, upload base images for enrolled students.
- **Managers** — can view all reports across all courses.
- **Admins** — full access including bulk delete and GDPR purge.

To change: Site administration → Users → Permissions → Define roles.
Capabilities: `quizaccess/edproctoring:viewreport`, `quizaccess/edproctoring:manage`, `quizaccess/edproctoring:viewallreports`, `quizaccess/edproctoring:deleteimages`, `quizaccess/edproctoring:uploadbaseimage`.

---

## GDPR / Data Erasure

To delete all proctoring data for a user:
1. Site administration → Privacy and policies → Data requests → New request → Delete all data → select user.
2. Or: Admin Proctoring Report → Search user → Delete all data for user.

The privacy provider handles: export of all session metadata, violations, and consent records; deletion of all DB records and Moodledata images.

**Automatic retention:** Images are auto-deleted after the configured retention period by a daily cron task (runs at 03:00 server time).

---

## Uninstallation

1. Site administration → Plugins → Plugin overview → find quizaccess_edproctoring → Uninstall.
2. Moodle will call `db/uninstall.php` which deletes all 5 tables and removes all files from Moodledata.
3. Stop and disable the FastAPI systemd service if installed: `sudo systemctl disable --now edproctoring-face`

---

## Troubleshooting

**Camera not working in quiz:**
- Ensure site is served over HTTPS.
- Check browser console for `getUserMedia` errors.
- Confirm browser has granted camera permission for the site.

**face-api.js model not loading:**
- Check that `amd/build/` files are correctly deployed.
- Purge Moodle caches and browser cache.
- Check browser console for 404 errors on model weight files.

**FastAPI service not connecting:**
- `sudo systemctl status edproctoring-face` — check for Python errors.
- `curl http://localhost:8765/health` — verify service is listening.
- Check Moodle's cURL can reach localhost:8765 (some server configs block this — test with `php -r "echo file_get_contents('http://localhost:8765/health');"`)

**AMD modules not loading:**
- Confirm `amd/build/*.min.js` files are deployed.
- Run `grunt amd` in plugin directory if `grunt` is available.
- Alternatively, manually minify `amd/src/*.js` into `amd/build/*.min.js`.
- Purge all Moodle caches after any AMD change.

---

## Changelog

### 2025050100 — Phase 1 (initial release)
- Core plugin scaffold (quizaccess plugin type)
- Consent screen and pre-flight check (camera permission + face detection)
- Webcam capture: interval + burst on violation
- Violation detection: FACE_ABSENT, MULTIPLE_FACES, TAB_SWITCH, FULLSCREEN_EXIT, COPY_PASTE, LOW_LIGHT, CAMERA_BLOCKED
- Trust score engine + colour-coded report bands
- Teacher reports: attempts list + attempt detail with violation timeline and image gallery
- Student warning toasts + auto-submit option
- GDPR privacy provider + image retention scheduled task
- Teacher notification on low trust score
- Mobile blocking
- Admin site settings

### Phase 2 (planned)
- Base image upload (student + admin + inline pre-flight)
- FastAPI face recognition service integration
- FACE_MISMATCH violation and pre-quiz face verification gate
- Face match scores in snapshot records and reports

### Phase 3 (planned)
- Audio monitoring (voice activity detection)
- PDF report export
- Violation dismissal (false positive marking)
- Browser fingerprinting

### Phase 4 (planned)
- Live teacher monitoring dashboard
