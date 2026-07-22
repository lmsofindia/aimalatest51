# local_reportpanel — Detailed Reporting Upgrade Plan

**Status:** Planning only (no build). Drafted 2026-07-08.
**Base:** `local_reportpanel` v2026070803, Moodle 5.0+ (Boost child theme edzcorp).
**Goal:** Grow the Reports Hub from static report pages into a full reporting engine:
rich charts, branded PDF export, a quick ad-hoc mailer, and scheduled recurring
report delivery by email — with HR / L&D Manager as first-class audiences.

Related plugins to stay aligned with: `local_edzteams` (manager oversight, already
does PDF), `local_edzskills` (compliance/expiry engine), `local_edzperf`.

---

## 1. Where this fits

We extend the existing plugin rather than create a new one. The Hub stays the shell;
we add four capabilities on top of every report it already renders (quiz, certificate,
consolidated, course-consolidated, catalogue):

1. **Charts** — consistent, themed, reusable visualisations.
2. **PDF export** — branded, print-ready, chart-inclusive.
3. **Quick mailer** — send *this* report to someone now.
4. **Scheduler** — send *these* reports to these people on a cadence.

The four are layered: charts feed the PDF, the PDF is the mail attachment, the mailer
is the manual trigger, the scheduler is the automated trigger. Build in that order.

---

## 2. The hard technical decision up front: charts in PDF

This is the one thing that will bite us if we pick wrong, so decide it first.

Moodle ships **Chart.js** via `\core\chart_bar` etc. — but those render **client-side
on a `<canvas>`**. A server-generated PDF (TCPDF, the `pdf` class in `pdflib.php`) runs
with **no browser**, so a canvas chart cannot be captured server-side. (Also note our
known sizing gotcha: `\core\chart_*` render giant unless the `.chart-area/.chart-image`
parent chain has definite width+height in CSS — see memory.)

Three viable approaches:

| Approach | On-screen | In PDF | Verdict |
|---|---|---|---|
| **A. Chart.js on screen + server-side image for PDF** (render SVG/PNG with a PHP lib e.g. jpgraph, or a small SVG builder) | interactive | ✅ real charts | Best quality, most work |
| **B. Client-side "print to PDF"** — build the PDF in-browser from the live canvas (html2canvas + jsPDF in AMD) | interactive | ✅ pixel copy | Fast to ship, but can't run headless in a scheduled cron job |
| **C. Server-side SVG charts everywhere** (we emit SVG, not Chart.js) — TCPDF renders SVG | static SVG | ✅ | Uniform, cron-safe, but loses Chart.js interactivity |

**Recommendation:** **A**, with a shared abstraction. Extend `classes/helper/charts.php`
to a small `chart_spec` object (type, series, labels, colours) consumed by **two**
renderers: a JS one (Chart.js, on screen) and a PHP one (server image, for PDF/email).
Reason: the **scheduler runs under cron with no browser**, so any emailed PDF *must* be
built server-side. Option B alone cannot satisfy scheduled delivery — rules it out as
the primary path. We can still offer B as a "quick download" convenience on screen.

---

## 3. Architecture (new/changed pieces inside local_reportpanel)

```
classes/
  helper/
    charts.php          (EXTEND) → chart_spec model + php_image_renderer
    export.php          (EXTEND) → pdf_builder (branded header/footer, chart embed)
    report_registry.php (NEW)    → single source of truth: id, title, capability,
                                    data provider, default filters, chart set
    mailer.php          (NEW)    → build + send (email_to_user) with PDF/CSV attach
  report/                        → one class per report implementing a common
    report_base.php     (NEW)      interface: get_data(filters), get_charts(),
    quiz_report.php ...            get_columns(), applies_to_audience()
  task/
    send_scheduled_reports.php (NEW) → scheduled_task, runs each cron tick
  form/
    schedule_form.php   (NEW)    → create/edit a schedule
    quicksend_form.php  (NEW)    → ad-hoc "send now" modal
db/
  install.xml           (NEW)    → schedule + run-log tables (see §5)
  tasks.php             (NEW)    → register scheduled_task (currently absent)
  access.php            (EXTEND) → 4 new capabilities (see §6)
  services.php          (EXTEND) → AJAX for quick-send + schedule CRUD
templates/                       → schedule list, schedule form, email HTML body
```

The **report_registry + report_base** refactor is the keystone: today each report is a
standalone page. To mail/schedule them generically, each must expose *data* and *charts*
through one interface so the mailer, PDF builder, and scheduler treat them uniformly.
Doing this refactor first is what makes items 2–4 cheap instead of copy-pasted.

---

## 4. Feature detail

### 4.1 Charts
- Standard set per report: completion donut, trend line (enrolments/completions over
  time), bar (per-course or per-cohort), and a compliance status stacked bar.
- On screen: Chart.js, responsive, with the parent-container fix already noted.
- Themed to edzcorp palette so PDF and UI match.

### 4.2 PDF export
- Use core `pdf` class (TCPDF). Reuse the approach `local_edzteams` already ships.
- Branded template: logo, site + report title, filter summary ("Cohort: Sales, Jul
  2026"), generated-on timestamp, page numbers, footer disclaimer.
- Embeds server-rendered chart images (§2A) + the data table. CSV/XLSX stay as the
  raw-data export path (keep existing `export.php` CSV).
- Entry points: a "Download PDF" button on every report + reused by mailer/scheduler.

### 4.3 Quick mailer ("Send now")
- Button on each report → modal: recipients (user search — reuse
  `external/search_users.php`, or free-type email, or a cohort), format (PDF/CSV/both),
  optional message. Sends immediately via `email_to_user()` with the file attached.
- Respects current on-screen filters so "what you see is what you send."

### 4.4 Scheduled delivery
- A **schedule** = report(s) + filters + recipients + cadence + format + enabled flag.
- Cadence: daily / weekly (day-of-week) / monthly (day-of-month), plus send-hour.
- Engine: `db/tasks.php` registers `send_scheduled_reports`, set to run frequently
  (e.g. every 15 min via cron); the task itself checks each schedule's next-run and
  fires only those due, writes a run-log row, computes next-run. **Do not** encode the
  cadence in the cron expression — store it in the DB and let one task dispatch. This is
  the standard Moodle pattern and avoids a task per schedule.
- Each run: build PDF/CSV server-side (cron = no browser → §2 matters), email, log
  success/failure, catch exceptions so one bad schedule can't kill the batch.
- Self-service: an HR/L&D Manager can create schedules for their own audience; admins
  can manage all. Gated by capability (§6).

---

## 5. Data model (new tables)

```
local_reportpanel_schedule
  id, userid(owner), name, reportids(csv/json), filters(json),
  recipients(json: userids + raw emails + cohortids), format(pdf|csv|both),
  frequency(daily|weekly|monthly), dayofweek, dayofmonth, hour,
  enabled, nextrun(timestamp), timecreated, timemodified

local_reportpanel_schedule_log
  id, scheduleid, timrun, status(ok|fail), recipientcount, message(error text)
```

`nextrun` is the index the task queries each tick — cheap and scalable.

---

## 6. Capabilities & roles (extend db/access.php)

Existing: `local/reportpanel:view` (self-service), `local/reportpanel:viewall` (HR
Manager full mode). Add:

| New capability | Grants |
|---|---|
| `local/reportpanel:export` | Download PDF/CSV of any report they can view |
| `local/reportpanel:sendnow` | Use the quick mailer |
| `local/reportpanel:schedule` | Create/manage their own scheduled reports |
| `local/reportpanel:manageallschedules` | See/manage everyone's schedules (admin) |

**L&D Manager as its own audience:** clone the HR-Manager pattern already documented in
`hrmanager.md`. Create an `ldmanager` role (Manager archetype) scoped at **Category**
or **Cohort** context so an L&D Manager sees only their department — assign via
`local_cohortrole` sync so it's automatic. HR Manager stays system-scoped (all users),
L&D Manager stays cohort/category-scoped (their people). Both get `:export`, `:sendnow`,
`:schedule`. Report content is identical; the *audience filter* differs by role scope.

New HR/L&D-oriented reports worth adding while we're here: **compliance completion &
non-completion** (overdue mandatory training), **certification expiry/renewal**
(feed from `local_edzskills`), and **cohort/department rollup** with drill-down.

---

## 7. Phased roadmap

- **P1 — Foundation (refactor):** report_registry + report_base; migrate existing 5
  reports onto the interface. No user-visible change; unlocks everything else.
- **P2 — Charts + PDF:** chart_spec dual renderer, branded pdf_builder, "Download PDF"
  on every report.
- **P3 — Quick mailer:** send-now modal + AJAX + `email_to_user` attach.
- **P4 — Scheduler:** tables, schedule form/list UI, `db/tasks.php` task, run-log,
  self-service schedules for HR/L&D managers.
- **P5 — HR/L&D report set + L&D role:** compliance/expiry/rollup reports, ldmanager
  role docs mirroring hrmanager.md.
- **P6 — Verification:** cron-run test of scheduled PDF email; large-cohort performance;
  capability matrix test (self / HR / L&D / admin); PDF renders charts headless.

---

## 8. Open decisions (need your call before we spec deeper)

1. **Chart-in-PDF path:** confirm §2 Option A (server-side image, cron-safe). This is
   the biggest effort driver.
2. **Scheduler self-service scope:** can HR/L&D managers schedule freely, or admin-only
   at first with managers added in P4b?
3. **Attachment default:** PDF only, or PDF + CSV (data teams usually want the CSV too)?
4. **Recipients beyond Moodle users:** allow raw external emails, or Moodle accounts /
   cohorts only (cleaner for privacy/GDPR + the privacy provider)?
5. **Reuse vs share:** lift `local_edzteams`' existing PDF code into a shared lib, or
   keep each plugin's PDF separate? Sharing reduces drift but couples the plugins.
```
