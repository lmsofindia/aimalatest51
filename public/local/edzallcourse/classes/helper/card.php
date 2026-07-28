<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course card builder for local_edzallcourse.
 *
 * Turns a single course record into template/export-ready view data,
 * using Moodle core helpers (overview files, course contacts, enrolment).
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzallcourse\helper;

use context_course;
use core_course_category;
use core_course_list_element;
use moodle_url;

/**
 * Build course-card view data.
 */
class card {

    /** @var array Per-request cache of formatted category names. */
    private static $catnames = [];

    /** @var string[] Gradient colours for image-less cards. */
    private const COLOURS = [
        '#4272d7', '#e6921a', '#0891b2', '#7c3aed',
        '#059669', '#db2777', '#2563eb', '#ca8a04',
    ];

    /**
     * Deterministic background colour for a course without an image.
     *
     * @param int $id course id
     * @return string hex colour
     */
    public static function gradient(int $id): string {
        return self::COLOURS[$id % count(self::COLOURS)];
    }

    /**
     * Deterministic decorative SVG pattern (data URI) for an image-less card.
     *
     * @param int $id course id
     * @return string data URI
     */
    public static function pattern_datauri(int $id): string {
        $patterns = [
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="0" cy="0" r="50" fill="rgba(255,255,255,0.12)"/><circle cx="100" cy="0" r="50" fill="rgba(255,255,255,0.12)"/><circle cx="0" cy="100" r="50" fill="rgba(255,255,255,0.12)"/><circle cx="100" cy="100" r="50" fill="rgba(255,255,255,0.12)"/><circle cx="50" cy="50" r="30" fill="rgba(255,255,255,0.08)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><circle cx="20" cy="20" r="14" fill="rgba(255,255,255,0.12)"/><circle cx="60" cy="20" r="14" fill="rgba(255,255,255,0.12)"/><circle cx="20" cy="60" r="14" fill="rgba(255,255,255,0.12)"/><circle cx="60" cy="60" r="14" fill="rgba(255,255,255,0.12)"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><polygon points="50,0 95,25 95,75 50,100 5,75 5,25" fill="none" stroke="rgba(255,255,255,0.18)" stroke-width="5"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="80" height="80" rx="6" fill="none" stroke="rgba(255,255,255,0.18)" stroke-width="6"/></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><polygon points="50,5 95,50 50,95 5,50" fill="none" stroke="rgba(255,255,255,0.18)" stroke-width="5"/></svg>',
        ];
        $svg = $patterns[$id % count($patterns)];
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Formatted category name (per-request cached).
     *
     * @param int $categoryid
     * @return string
     */
    private static function catname(int $categoryid): string {
        if (!array_key_exists($categoryid, self::$catnames)) {
            $cat = core_course_category::get($categoryid, IGNORE_MISSING, true);
            self::$catnames[$categoryid] = $cat ? $cat->get_formatted_name() : '';
        }
        return self::$catnames[$categoryid];
    }

    /**
     * Build a page of cards from course records, preloading course contacts
     * in one batch (so the teacher line is reliable and not an N+1).
     *
     * @param \stdClass[] $records course records
     * @param array $opts card field toggles
     * @return array[] list of card view-data arrays
     */
    public static function build_page(array $records, array $opts = []): array {
        $elements = [];
        foreach ($records as $rec) {
            $elements[(int)$rec->id] = new core_course_list_element($rec);
        }
        if (!empty($opts['showteacher']) && !empty($elements)
                && method_exists('\\core_course_category', 'preload_course_contacts')) {
            \core_course_category::preload_course_contacts($elements);
        }
        $out = [];
        foreach ($elements as $cle) {
            $out[] = self::build_from_element($cle, $opts);
        }
        return $out;
    }

    /**
     * Build card view data from a course record.
     *
     * @param \stdClass $course course record (id, category, fullname, shortname,
     *                          summary, summaryformat, startdate at minimum)
     * @param array $opts ['showteacher'=>bool,'showstartdate'=>bool,'showsummary'=>bool]
     * @return array
     */
    public static function build(\stdClass $course, array $opts = []): array {
        return self::build_from_element(new core_course_list_element($course), $opts);
    }

    /**
     * Build card view data from a course list element.
     *
     * @param core_course_list_element $cle
     * @param array $opts
     * @return array
     */
    public static function build_from_element(core_course_list_element $cle, array $opts = []): array {
        global $USER;

        $showteacher   = $opts['showteacher']   ?? true;
        $showstartdate = $opts['showstartdate'] ?? true;
        $showsummary   = $opts['showsummary']   ?? true;

        $id       = (int)$cle->id;
        $context  = context_course::instance($id);
        $category = (int)$cle->__get('category');

        // Overview image (first valid) or deterministic fallback.
        $image = '';
        foreach ($cle->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $image = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid() ?: null,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
                break;
            }
        }

        // Teacher (first course contact — respects $CFG->coursecontact).
        $teacher = '';
        $moreteachers = 0;
        if ($showteacher) {
            $contacts = $cle->get_course_contacts();
            if (!empty($contacts)) {
                $first = reset($contacts);
                $teacher = $first['username'] ?? fullname($first['user']);
                $moreteachers = count($contacts) - 1;
            } else {
                $teacher = get_string('notyetassigned', 'local_edzallcourse');
            }
        }

        // Enrolment-aware CTA.
        $isguest = !isloggedin() || isguestuser();
        $enrolled = !$isguest && is_enrolled($context, $USER, '', true);
        if ($isguest) {
            $ctakey = 'viewcourse';
        } else if ($enrolled) {
            $ctakey = 'gotocourse';
        } else {
            $ctakey = 'enrolnow';
        }

        // Edit affordance for managers/teachers who can update the course.
        $canedit = has_capability('moodle/course:update', $context);
        $editurl = $canedit ? (new moodle_url('/course/edit.php', ['id' => $id]))->out(false) : null;

        $summary = '';
        if ($showsummary) {
            // Rewrite @@PLUGINFILE@@ tokens so embedded summary images resolve.
            $summarytext = file_rewrite_pluginfile_urls(
                (string)$cle->__get('summary'),
                'pluginfile.php',
                $context->id,
                'course',
                'summary',
                null
            );
            $summary = format_text(
                $summarytext,
                $cle->__get('summaryformat') ?? FORMAT_HTML,
                ['context' => $context, 'overflowdiv' => false, 'newlines' => false]
            );
        }

        $startdate = '';
        $startts = (int)$cle->__get('startdate');
        if ($showstartdate && $startts > 0) {
            $startdate = userdate($startts, get_string('strftimedate', 'langconfig'));
        }

        return [
            'id'            => $id,
            'fullname'      => $cle->get_formatted_name(),
            'fullnamealt'   => (string)$cle->__get('fullname'),
            'summary'       => $summary,
            'hassummary'    => $showsummary && $summary !== '',
            'categoryname'  => self::catname($category),
            'courseimage'   => $image,
            'hasimage'      => $image !== '',
            'coursecolor'   => $image === '' ? self::gradient($id) : '',
            'coursepattern' => $image === '' ? self::pattern_datauri($id) : '',
            'showteacher'   => $showteacher,
            'teacherlabel'  => terminology::teacher_label(),
            'teacher'       => $teacher,
            'moreteachers'  => $moreteachers > 0 ? $moreteachers : 0,
            'hasmoreteachers' => $moreteachers > 0,
            'showstartdate' => $showstartdate && $startdate !== '',
            'startdate'     => $startdate,
            'url'           => (new moodle_url('/course/view.php', ['id' => $id]))->out(false),
            'ctalabel'      => get_string($ctakey, 'local_edzallcourse'),
            'enrolled'      => $enrolled,
            'canedit'       => $canedit,
            'editurl'       => $editurl,
        ];
    }
}
