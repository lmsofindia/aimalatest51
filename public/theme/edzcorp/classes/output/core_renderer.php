<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * EdzCorp core renderer.
 *
 * Extends Boost's renderer so all Boost-specific output helpers are available.
 *
 * @package    theme_edzcorp
 * @copyright  2024 EdzCorp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_edzcorp\output;

use html_writer;

defined('MOODLE_INTERNAL') || die();

/**
 * EdzCorp core renderer.
 *
 * Customisations:
 *  - standard_head_html()       Injects Google Fonts <link> tags when font settings
 *                               are configured (avoids scssphp @import url() failures).
 *  - context_header()           Enhances the header on course view pages with
 *                               a course thumbnail image and description.
 *  - firstview_fakeblocks()     Safe fallback implementation for Moodle 5.x.
 *  - page_title()               Friendly page-name string for the navbar title area.
 */
class core_renderer extends \theme_boost\output\core_renderer
{

    /**
     * Returns the HTML to inject into <head>.
     *
     * Prepends Google Fonts <link> tags (preconnect + stylesheet) when any
     * font setting is configured.  Using a <link> tag avoids the scssphp
     * @import url() limitation where the SCSS compiler attempts to fetch the
     * external URL as a local file and fails.
     *
     * @return string
     */
    public function standard_head_html(): string
    {
        $output = parent::standard_head_html();

        $settings = $this->page->theme->settings;

        // -----------------------------------------------------------------------
        // Always load Instrument Sans as the EdzCorp default font.
        // Additional fonts from admin settings are appended after.
        // Using <link> tags (never @import url()) to avoid scssphp failures.
        // -----------------------------------------------------------------------
        $families = ['Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400'];

        // Append any admin-configured Google Fonts (unique, non-empty).
        $selectedfonts = array_values(array_filter(array_unique([
            !empty($settings->headingfont)    ? $settings->headingfont    : '',
            !empty($settings->subheadingfont) ? $settings->subheadingfont : '',
            !empty($settings->bodyfont)       ? $settings->bodyfont       : '',
        ])));

        foreach ($selectedfonts as $font) {
            // Skip if it is Instrument Sans (already included above).
            if (str_replace(' ', '+', trim($font)) === 'Instrument+Sans') {
                continue;
            }
            $families[] = $font . ':ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400';
        }

        $gfontsurl = 'https://fonts.googleapis.com/css2?family='
            . implode('&family=', $families)
            . '&display=swap';

        // Preconnect hints + stylesheet — prepended so fonts load before theme CSS.
        $fonthtml  = '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        $fonthtml .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        $fonthtml .= '<link rel="stylesheet" href="' . htmlspecialchars($gfontsurl, ENT_QUOTES, 'UTF-8') . '">' . "\n";

        $output = $fonthtml . $output;

        return $output;
    }

    /**
     * Returns the context-specific page header HTML.
     *
     * On the course home page (pagetype course-view-*) this renders a card-style
     * header that shows:
     *   • Course thumbnail image (from the course overview files area)
     *   • Course full name as the heading
     *   • Course description (plain text, max 220 chars)
     *
     * All other page types fall through to Boost's default implementation.
     *
     * @param array|null $headerinfo  Optional header data (passed by Boost).
     * @param int        $headinglevel Heading level (default 1).
     * @return string HTML
     */
    public function context_header($headerinfo = null, $headinglevel = 1): string
    {
        // Only apply the enhanced header on course home pages.
        if (strpos($this->page->pagetype, 'course-view') !== 0) {
            return parent::context_header($headerinfo, $headinglevel);
        }

        $course = $this->page->course;

        // Safety: fall back for site-level or missing course object.
        if (!$course || !isset($course->id) || $course->id == SITEID) {
            return parent::context_header($headerinfo, $headinglevel);
        }

        $coursecontext = \context_course::instance($course->id);

        // Course full name.
        $coursetitle = format_string($course->fullname, true, ['context' => $coursecontext]);

        // Course thumbnail image (from the course overview files).
        $imageurl = $this->get_course_overview_image_url($course);

        // Per-course vibrant palette (deterministic from the course id). Used for
        // the header background, and — when the course has no image — for a
        // generated tile so the media slot is never blank.
        $headergradients = [
            ['#4f46e5', '#7c3aed', '#db2777'], // indigo → violet → pink
            ['#0891b2', '#2563eb', '#7c3aed'], // cyan → blue → violet
            ['#0d9488', '#0891b2', '#2563eb'], // teal → cyan → blue
            ['#ea580c', '#db2777', '#7c3aed'], // orange → pink → violet
            ['#b45309', '#dc2626', '#db2777'], // amber → red → pink
            ['#7c3aed', '#6366f1', '#0891b2'], // violet → indigo → cyan
        ];
        $tilegradients = [
            ['#818cf8', '#f472b6'],
            ['#22d3ee', '#818cf8'],
            ['#34d399', '#22d3ee'],
            ['#fb923c', '#f472b6'],
            ['#fbbf24', '#fb7185'],
            ['#a78bfa', '#22d3ee'],
        ];
        $gi       = (int) $course->id % 6;
        $hg       = $headergradients[$gi];
        $tg       = $tilegradients[$gi];
        $headerbg = "linear-gradient(135deg, {$hg[0]} 0%, {$hg[1]} 55%, {$hg[2]} 100%)";

        // Course description — strip HTML, collapse whitespace, truncate.
        $description = '';
        if (!empty($course->summary)) {
            $html      = format_text($course->summary, $course->summaryformat, [
                'context'  => $coursecontext,
                'noclean'  => false,
                'para'     => false,
            ]);
            $plaintext = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
            if (\core_text::strlen($plaintext) > 220) {
                $plaintext = \core_text::substr($plaintext, 0, 220) . '…';
            }
            $description = $plaintext;
        }

        // Build the enhanced header markup.
        $inner = '';

        if ($imageurl) {
            $inner .= html_writer::empty_tag('img', [
                'src'     => $imageurl,
                'alt'     => '',
                'class'   => 'edz-course-header-img',
                'loading' => 'lazy',
            ]);
        } else {
            // No course image — render a vibrant generated tile carrying the
            // course initial, so the media slot is filled and colourful.
            $initial = \core_text::strtoupper(\core_text::substr(trim($course->fullname), 0, 1));
            if ($initial === '') {
                $initial = 'C';
            }
            $tilebg  = "linear-gradient(135deg, {$tg[0]}, {$tg[1]})";
            $inner  .= html_writer::tag(
                'div',
                html_writer::tag('span', s($initial), ['class' => 'edz-course-header-gen__ch']),
                [
                    'class'       => 'edz-course-header-gen',
                    'style'       => "background: {$tilebg};",
                    'aria-hidden' => 'true',
                ]
            );
        }

        $bodycontent  = html_writer::tag('h2', $coursetitle, ['class' => 'edz-course-header-title']);
        if ($description) {
            $bodycontent .= html_writer::tag('p', s($description), ['class' => 'edz-course-header-desc']);
        }
        $inner .= html_writer::div($bodycontent, 'edz-course-header-body');

        // Completion donut chart (right side) — only when completion tracking
        // is enabled for the course and the user is a real logged-in user.
        $inner .= $this->course_completion_donut($course);

        return html_writer::div($inner, 'edz-course-header', ['style' => "background: {$headerbg};"]);
    }

    /**
     * Builds an SVG donut chart showing the current user's activity-completion
     * percentage for the course. Returns '' when completion is disabled, the
     * course has no trackable activities, or the user is a guest.
     *
     * @param  \stdClass $course  The course record.
     * @return string              HTML (div.edz-course-header-progress) or ''.
     */
    protected function course_completion_donut(\stdClass $course): string
    {
        global $CFG, $USER;

        if (!isloggedin() || isguestuser()) {
            return '';
        }

        require_once($CFG->libdir . '/completionlib.php');

        try {
            $completion = new \completion_info($course);
            if (!$completion->is_enabled()) {
                return '';
            }

            $activities = $completion->get_activities();
            if (empty($activities)) {
                return '';
            }

            $total = count($activities);
            $done  = 0;
            foreach ($activities as $cm) {
                $data = $completion->get_data($cm, true, $USER->id);
                if (in_array((int) $data->completionstate,
                        [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
                    $done++;
                }
            }
        } catch (\Throwable $e) {
            return ''; // Never break the course page over a progress widget.
        }

        $pct  = (int) round($done / $total * 100);
        $r    = 34;
        $circ = 2 * M_PI * $r;
        $dash = round($circ * $pct / 100, 2);

        $arc = '';
        if ($dash > 0) {
            $arc = '<circle class="edz-donut-arc" cx="40" cy="40" r="' . $r . '"'
                 . ' stroke-dasharray="' . $dash . ' ' . round($circ, 2) . '"'
                 . ' transform="rotate(-90 40 40)"/>';
        }

        $svg = '<svg class="edz-donut" viewBox="0 0 80 80" role="img"'
             . ' aria-label="' . s(get_string('completed', 'completion') . ': ' . $pct . '%') . '">'
             . '<circle class="edz-donut-track" cx="40" cy="40" r="' . $r . '"/>'
             . $arc
             . '<text x="40" y="45" class="edz-donut-text">' . $pct . '%</text>'
             . '</svg>';

        $label = html_writer::tag('span',
            get_string('completed', 'completion'),
            ['class' => 'edz-donut-label']);

        return html_writer::div($svg . $label, 'edz-course-header-progress');
    }

    /**
     * Returns the URL of the first image file in the course overview files area.
     *
     * @param  \stdClass $course  The course record.
     * @return string             Absolute image URL, or empty string if none found.
     */
    protected function get_course_overview_image_url(\stdClass $course): string
    {
        if (empty($course->id)) {
            return '';
        }

        try {
            $fs      = get_file_storage();
            $context = \context_course::instance($course->id);

            // Use itemid=0 explicitly (course overviewfiles always use itemid 0).
            // Using false would return all items but may miss files in some Moodle builds.
            $files = $fs->get_area_files(
                $context->id,
                'course',
                'overviewfiles',
                0,          // explicit itemid=0 — course overview files always stored here
                'filename',
                false       // exclude directory entries
            );

            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                $mime = $file->get_mimetype();
                // Accept all common image types.
                if (
                    strpos($mime, 'image/') === 0 ||
                    in_array($mime, ['image/jpeg', 'image/png', 'image/gif',
                                     'image/webp', 'image/svg+xml'], true)
                ) {
                    // itemid must be null (not 0) for course overviewfiles.
                    // Moodle's make_pluginfile_url omits the itemid segment when null,
                    // producing: /pluginfile.php/{ctx}/course/overviewfiles/{filename}
                    // Passing 0 incorrectly adds /0/ producing a 404.
                    return \moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        null,
                        $file->get_filepath(),
                        $file->get_filename()
                    )->out(false);
                }
            }
        } catch (\Exception $e) {
            // Fail silently — the header will render without an image.
            debugging('theme_edzcorp: get_course_overview_image_url failed: ' . $e->getMessage(),
                      DEBUG_DEVELOPER);
        }

        return '';
    }


    /**
     * Returns a friendly page title string for display in the navbar.
     *
     * Maps the current page's body-id and page-layout to a short human-readable
     * label.  Returning an empty string suppresses the navbar title area so the
     * nav does not show anything for pages that have their own prominent heading.
     *
     * @return string  Plain text (no HTML) or empty string.
     */
    public function page_title(): string
    {
        $pagetype   = $this->page->pagetype   ?? '';  // e.g. 'course-view-topics' — confirmed real property
        $pagelayout = $this->page->pagelayout ?? '';  // e.g. 'course', 'mydashboard'
        $bodyid     = $this->page->bodyid     ?? '';  // may be empty — not a reliable property

        // ── 1. Activity / resource page: show the specific instance name ────
        // $PAGE->cm is a cm_info object whenever Moodle is viewing a module page.
        // Using instanceof is safer than !empty() for Moodle proxy objects.
        $cm = $this->page->cm;
        if ($cm instanceof \cm_info) {
            // Prefer the human name of the specific instance (e.g. "Week 3 Quiz").
            $name = '';
            try { $name = (string)$cm->name; } catch (\Throwable $e) {}
            if ($name !== '') {
                return format_string($name, true, ['context' => $this->page->context]);
            }
            // Fallback: capitalised module type (Quiz, Forum, Lesson …).
            try { $mod = (string)$cm->modname; } catch (\Throwable $e) { $mod = ''; }
            return $mod !== '' ? ucfirst($mod) : '';
        }

        // ── 2. Course page: show the course full name ───────────────────────
        // $PAGE->pagetype is the reliable signal: 'course-view-topics', 'course-view-weeks', etc.
        // $PAGE->pagelayout === 'course' and bodyid prefix are kept as additional fallbacks.
        if (
            !empty($this->page->course) &&
            $this->page->course->id != SITEID &&
            (
                strpos($pagetype, 'course-view') === 0 ||
                $pagelayout === 'course' ||
                strpos($bodyid, 'page-course-view') === 0
            )
        ) {
            return format_string(
                $this->page->course->fullname,
                true,
                ['context' => $this->page->context]
            );
        }

        // ── 2b. Specific plugin pages — pagetype is the reliable signal ─────
        $pagetypemap = [
            'local-trackmytime-pages-performance' => 'Achievement',
            'local-edzonboard-myonboarding'       => 'Onboarding Journey',
            'local-coursecatalogue-index'         => 'Catalogue',
            'local-edzteams-index'                => 'Team',
            'local-edzrisebuilder-select'         => 'Build Scorm',
            'local-edzcoursegen-select'           => 'AI Course Builder',
            'local-reportpanel-index'             => 'Analytics',
            'local-admincontrol-index'            => 'Site administration',
        ];
        if (isset($pagetypemap[$pagetype])) {
            return $pagetypemap[$pagetype];
        }

        // ── 3. Body-id based titles (specific pages) ────────────────────────
        $bodymap = [
            'page-local-edzperformance-index' => 'Performance',
            'page-local-edzskills-index'      => 'Skills',
            'page-local-edzreports-index'     => 'Reports',
            'page-local-edzlms-index'         => 'Dashboard',
            'page-my-index'                   => 'Dashboard',
            'page-user-profile'               => 'My Profile',
            'page-user-preferences'           => 'Preferences',
            'page-admin-index'                => 'Site Administration',
            'page-course-index-category'      => 'Course Catalogue',
            'page-course-index'               => 'All Courses',
            'page-mod-forum-index'            => 'Forums',
            'page-mod-forum-discuss'          => 'Discussion',
            'page-calendar-view'              => 'Calendar',
            'page-message-index'              => 'Messages',
            'page-grade-report-user-index'    => 'My Grades',
            'page-grade-report-overview-index'=> 'Grade Overview',
        ];

        if (isset($bodymap[$bodyid])) {
            return $bodymap[$bodyid];
        }

        // ── 4. Layout-based fallbacks ────────────────────────────────────────
        $layoutmap = [
            'mydashboard'    => 'Dashboard',
            'mycourses'      => 'My Courses',
            'course'         => '',  // should be caught by pagetype check above; suppress fallback
            'incourse'       => '',  // caught by cm_info check above
            'admin'          => 'Administration',
            'coursecategory' => 'Courses',
            'report'         => 'Reports',
            'standard'       => '',
            'frontpage'      => '',
        ];

        if (isset($layoutmap[$pagelayout])) {
            return $layoutmap[$pagelayout];
        }

        return '';
    }

    /**
     * Returns true on the first view of a course module page that exposes
     * fake (auto-open) blocks in the side-pre drawer region.
     *
     * @return bool
     */
    public function firstview_fakeblocks(): bool
    {
        // Note: ['parent', 'method'] callables are deprecated in PHP 8.x —
        // use method_exists() on the parent class instead.
        if (method_exists(get_parent_class($this), 'firstview_fakeblocks')) {
            return parent::firstview_fakeblocks();
        }

        global $SESSION;

        if ($this->page->cm) {
            if (!$this->page->blocks->region_has_fakeblocks('side-pre')) {
                return false;
            }

            if (!property_exists($SESSION, 'firstview_fakeblocks')) {
                $SESSION->firstview_fakeblocks = [];
            }

            if (array_key_exists($this->page->cm->id, $SESSION->firstview_fakeblocks)) {
                return false;
            }

            $SESSION->firstview_fakeblocks[$this->page->cm->id] = true;

            if (count($SESSION->firstview_fakeblocks) > 100) {
                array_shift($SESSION->firstview_fakeblocks);
            }

            return true;
        }

        return false;
    }
}
