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
 * Consolidated COURSE report — pick a course, see every enrolled user's completion,
 * grade, activity completion and status, with summary charts. Full mode only.
 * Supports PDF and CSV export.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_reportpanel\helper\access;
use local_reportpanel\helper\catalogue;
use local_reportpanel\helper\charts;
use local_reportpanel\helper\courseconsolidated;
use local_reportpanel\helper\activities;
use local_reportpanel\helper\export;

require_login(null, false);
$context = context_system::instance();
require_capability('local/reportpanel:view', $context);

// This is an all-user report: require full mode.
require_capability('local/reportpanel:viewall', $context);

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$exportformat = optional_param('export', '', PARAM_ALPHA);

// Handle export (read-only download). Require sesskey for safety.
if ($exportformat && $courseid) {
    require_sesskey();
    $report = courseconsolidated::build($courseid);
    if (!$report['course']) {
        throw new moodle_exception('error_nocourse', 'local_reportpanel');
    }
    if ($exportformat === 'pdf') {
        export::course_pdf($report['course'], $report['rows'], $report['summary']);
    } else if ($exportformat === 'csv') {
        export::course_csv($report['course'], $report['rows']);
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reportpanel/courseconsolidated.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('coursecons_title', 'local_reportpanel'));
$PAGE->set_heading(get_string('coursecons_title', 'local_reportpanel'));

$backurl = (new moodle_url('/local/reportpanel/index.php'))->out(false);

// Category options.
$catoptions = [];
foreach (catalogue::categories() as $id => $name) {
    $catoptions[] = ['id' => $id, 'name' => $name, 'selected' => ($id == $categoryid)];
}

// Course options for the selected category (server-side, so reloads/deeplinks work).
$courseoptions = [];
if ($categoryid) {
    foreach (catalogue::courses_in_category($categoryid) as $c) {
        $courseoptions[] = [
            'id' => $c['id'],
            'name' => $c['fullname'],
            'selected' => ($c['id'] == $courseid),
        ];
    }
}

$templatecontext = [
    'backurl' => $backurl,
    'intro' => get_string('coursecons_intro', 'local_reportpanel'),
    'actionurl' => (new moodle_url('/local/reportpanel/courseconsolidated.php'))->out(false),
    'categories' => $catoptions,
    'courses' => $courseoptions,
    'hascourses' => !empty($courseoptions),
    'categoryid' => $categoryid,
    'courseid' => $courseid,
    'hascourse' => false,
];

if ($courseid) {
    $report = courseconsolidated::build($courseid);
    if ($report['course']) {
        $course = $report['course'];
        $summary = $report['summary'];
        $templatecontext['hascourse'] = true;
        $templatecontext['coursename'] = format_string($course->fullname);
        $templatecontext['rows'] = $report['rows'];
        $templatecontext['hasrows'] = $report['hasrows'];

        // Summary stat cards.
        $templatecontext['stat_total'] = $summary['totalusers'];
        $templatecontext['stat_completed'] = $summary['completed'];
        $templatecontext['stat_inprogress'] = $summary['inprogress'];
        $templatecontext['stat_notstarted'] = $summary['notstarted'];
        $templatecontext['stat_completionrate'] = $summary['completionrate'] . '%';
        $templatecontext['stat_avggrade'] = $summary['avggradelabel'];

        // Charts (rendered server-side via core/chartjs).
        $donut = charts::status_doughnut(
            $summary['completed'], $summary['inprogress'], $summary['notstarted']);
        $gradebar = charts::grade_bar($summary['gradebuckets']);
        $templatecontext['hasdonut'] = (bool)$donut;
        $templatecontext['donutchart'] = $donut ? $OUTPUT->render_chart($donut, false) : '';
        $templatecontext['hasgradebar'] = (bool)$gradebar;
        $templatecontext['gradebarchart'] = $gradebar ? $OUTPUT->render_chart($gradebar, false) : '';
        $templatecontext['haschart'] = ($donut || $gradebar);

        // Export links.
        $templatecontext['exportpdfurl'] = (new moodle_url('/local/reportpanel/courseconsolidated.php',
            ['courseid' => $courseid, 'categoryid' => $categoryid,
             'export' => 'pdf', 'sesskey' => sesskey()]))->out(false);
        $templatecontext['exportcsvurl'] = (new moodle_url('/local/reportpanel/courseconsolidated.php',
            ['courseid' => $courseid, 'categoryid' => $categoryid,
             'export' => 'csv', 'sesskey' => sesskey()]))->out(false);

        // Domain E — per-activity coverage table.
        $templatecontext['activities'] = activities::build($courseid);
    }
}

// Auto-submit the GET form when category/course changes (degrades to the button
// when JS is off). Reset course when category changes.
$PAGE->requires->js_amd_inline("
require([], function() {
    var cat = document.getElementById('reportpanel-category');
    var course = document.getElementById('reportpanel-course');
    if (cat) {
        cat.addEventListener('change', function() {
            if (course) { course.value = '0'; }
            cat.form.submit();
        });
    }
    if (course) {
        course.addEventListener('change', function() { course.form.submit(); });
    }
});
");

// Client-side filtering (name / email / status) + pagination for the learner table.
$infotpljs = json_encode(get_string('page_info', 'local_reportpanel'));
$pagetpljs = json_encode(get_string('page_pageof', 'local_reportpanel'));
$PAGE->requires->js_amd_inline("
require([], function() {
    var table = document.getElementById('reportpanel-course-table');
    if (!table) { return; }
    var namef = document.getElementById('reportpanel-filter-name');
    var emailf = document.getElementById('reportpanel-filter-email');
    var statusf = document.getElementById('reportpanel-filter-status');
    var count = document.getElementById('reportpanel-filter-count');
    var empty = document.getElementById('reportpanel-table-empty');
    var pager = document.getElementById('reportpanel-pagination');
    var sizef = document.getElementById('reportpanel-pagesize');
    var info = document.getElementById('reportpanel-pagination-info');
    var label = document.getElementById('reportpanel-page-label');
    var prev = document.getElementById('reportpanel-page-prev');
    var next = document.getElementById('reportpanel-page-next');
    var rows = Array.prototype.slice.call(table.tBodies[0].rows);
    var norm = function(v) { return (v || '').toString().toLowerCase().trim(); };
    var infotpl = $infotpljs;
    var pagetpl = $pagetpljs;
    var pagesize = 25;
    var page = 1;
    var filtered = rows;

    var fill = function(tpl, map) {
        return tpl.replace(/\{(\w+)\}/g, function(m, k) {
            return (map[k] !== undefined) ? map[k] : m;
        });
    };

    var refilter = function() {
        var nq = norm(namef && namef.value);
        var eq = norm(emailf && emailf.value);
        var sq = statusf ? statusf.value : '';
        filtered = rows.filter(function(r) {
            var okname = !nq || norm(r.getAttribute('data-name')).indexOf(nq) !== -1;
            var okemail = !eq || norm(r.getAttribute('data-email')).indexOf(eq) !== -1;
            var okstatus = !sq || r.getAttribute('data-status') === sq;
            return okname && okemail && okstatus;
        });
        page = 1;
    };

    var render = function() {
        var total = filtered.length;
        var size = (pagesize === 0) ? (total || 1) : pagesize;
        var pages = Math.max(1, Math.ceil(total / size));
        if (page > pages) { page = pages; }
        if (page < 1) { page = 1; }
        var start = (page - 1) * size;
        var end = Math.min(start + size, total);

        rows.forEach(function(r) { r.style.display = 'none'; });
        filtered.slice(start, end).forEach(function(r) { r.style.display = ''; });

        if (count) { count.textContent = total + ' / ' + rows.length; }
        if (empty) { empty.hidden = total !== 0; }

        // Pagination bar only appears when a page break is actually needed.
        var needpager = (pagesize !== 0) && (total > pagesize);
        if (pager) { pager.hidden = (total === 0); }
        if (info) {
            info.textContent = total === 0 ? '' :
                fill(infotpl, {from: start + 1, to: end, total: total});
        }
        if (label) {
            label.textContent = fill(pagetpl, {page: page, pages: pages});
            label.style.visibility = needpager ? 'visible' : 'hidden';
        }
        if (prev) { prev.disabled = (page <= 1); prev.style.visibility = needpager ? 'visible' : 'hidden'; }
        if (next) { next.disabled = (page >= pages); next.style.visibility = needpager ? 'visible' : 'hidden'; }
    };

    var apply = function() { refilter(); render(); };

    [namef, emailf].forEach(function(el) { if (el) { el.addEventListener('input', apply); } });
    if (statusf) { statusf.addEventListener('change', apply); }
    if (sizef) {
        sizef.addEventListener('change', function() {
            pagesize = parseInt(sizef.value, 10) || 0;
            page = 1;
            render();
        });
    }
    if (prev) { prev.addEventListener('click', function() { page--; render(); }); }
    if (next) { next.addEventListener('click', function() { page++; render(); }); }
    apply();
});
");

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reportpanel/course_consolidated', $templatecontext);
echo $OUTPUT->footer();
