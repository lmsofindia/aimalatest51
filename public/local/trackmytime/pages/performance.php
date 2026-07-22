<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * GemUI Performance & Achievements page.
 *
 * URL: /local/trackmytime/pages/performance.php
 * Linked from: GemUI sidebar "Progress" nav item
 *
 * Renders: study heatmap, grade overview, badges earned, certificates.
 * Data fetched server-side for initial render; AMD module performance.js
 * augments with any client-side interactivity.
 *
 * @package    local_trackmytime
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/badgeslib.php');

require_login();

$userid = optional_param('userid', $USER->id, PARAM_INT);

// Users may only view their own page unless they are admin.
if ($userid != $USER->id) {
    require_capability('moodle/user:viewdetails', context_system::instance());
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/trackmytime/pages/performance.php', ['userid' => $userid]);
$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('local-trackmytime-performance');
$PAGE->set_title(get_string('performance_title', 'local_trackmytime'));
$PAGE->set_heading('');

// ── Collect data for initial server-side render ───────────────────────────────

// Heatmap — 26 weeks.
$heatmapweeks = [];
for ($w = 1; $w <= 26; $w++) {
    $end     = time() - (($w - 1) * WEEKSECS);
    $start   = $end - WEEKSECS;
    $minutes = 0;

    if ($DB->get_manager()->table_exists('trackmytime')) {
        $secs    = (int) $DB->get_field_sql(
            'SELECT COALESCE(SUM(timespent), 0) FROM {trackmytime}
              WHERE userid = ? AND timestart >= ? AND timestart < ?',
            [$userid, $start, $end]
        );
        $minutes = (int) round($secs / 60);
    }

    $level = 0;
    if ($minutes > 0)   { $level = 1; }
    if ($minutes > 30)  { $level = 2; }
    if ($minutes > 90)  { $level = 3; }
    if ($minutes > 180) { $level = 4; }

    $heatmapweeks[] = [
        'week'    => $w,
        'minutes' => $minutes,
        'level'   => $level,
        'label'   => userdate($end, get_string('strftimedatefullshort', 'langconfig')),
    ];
}

// Grades.
$grades = [];
$courses = enrol_get_users_courses($userid, true, null);
foreach ($courses as $course) {
    if ($course->id == SITEID) { continue; }
    $courseitem = \grade_item::fetch_course_item($course->id);
    if (!$courseitem) { continue; }

    $gradegrade = new \grade_grade(['itemid' => $courseitem->id, 'userid' => $userid], true);
    $rawgrade   = $gradegrade->finalgrade !== null ? (float) $gradegrade->finalgrade : null;
    $grademax   = (float) $courseitem->grademax;
    $gradepass  = (float) $courseitem->gradepass;

    // Real course-completion status (matches the dashboard), independent of grade.
    $ccompletion = new \completion_info($course);
    $iscomplete  = $ccompletion->is_enabled() && $ccompletion->is_course_complete($userid);

    $grades[] = [
        'coursename' => format_string($course->fullname),
        'grade'      => $rawgrade !== null ? number_format($rawgrade, 0) . ' / ' . number_format($grademax, 0) : '—',
        'completed'  => $iscomplete,
        'passed'     => $rawgrade !== null && $gradepass > 0 && $rawgrade >= $gradepass,
        'gradeurl'   => (new moodle_url('/grade/report/user/index.php',
                            ['id' => $course->id, 'userid' => $userid]))->out(false),
    ];
}

// Badges.
$badges = [];
if ($DB->get_manager()->table_exists('badge_issued')) {
    $sql = "SELECT bi.badgeid, b.name, b.type, b.courseid, bi.dateissued
              FROM {badge_issued} bi
              JOIN {badge} b ON b.id = bi.badgeid
             WHERE bi.userid = ?
               AND b.status != 0
          ORDER BY bi.dateissued DESC
             LIMIT 20";

    foreach ($DB->get_records_sql($sql, [$userid]) as $r) {
        // Badge images live in the badge's own context: system for site badges,
        // the course context for course badges. Moodle stores the image as
        // 'f1' (large) / 'f2' (small); 'f3' does not exist.
        if ((int) $r->type == BADGE_TYPE_COURSE && !empty($r->courseid)) {
            $badgectx = context_course::instance($r->courseid);
        } else {
            $badgectx = context_system::instance();
        }
        $imageurl = moodle_url::make_pluginfile_url(
            $badgectx->id, 'badges', 'badgeimage', $r->badgeid, '/', 'f1'
        );
        $badges[] = [
            'name'       => format_string($r->name),
            'imageurl'   => $imageurl->out(false),
            'dateissued' => userdate($r->dateissued),
        ];
    }
}

// ── Time analytics (periods + weekly/monthly charts + XP) ─────────────────────
$sumsecs = function (int $from, int $to) use ($DB, $userid): int {
    if (!$DB->get_manager()->table_exists('trackmytime')) {
        return 0;
    }
    return (int) $DB->get_field_sql(
        'SELECT COALESCE(SUM(timespent), 0) FROM {trackmytime}
          WHERE userid = ? AND timestart >= ? AND timestart < ?',
        [$userid, $from, $to]
    );
};
$fmt = function (int $secs): string {
    if ($secs <= 0) {
        return '0m';
    }
    if ($secs < 60) {
        return $secs . 's';   // Sub-minute — show seconds so it never reads "0m".
    }
    $h = intdiv($secs, 3600);
    $m = intdiv($secs % 3600, 60);
    return $h > 0 ? ($m > 0 ? "{$h}h {$m}m" : "{$h}h") : "{$m}m";
};

$now          = time();
$todaystart   = usergetmidnight($now);
$weekstart    = strtotime('monday this week 00:00', $now) ?: ($now - ($now % DAYSECS));
$lastweekst   = strtotime('monday last week 00:00', $now) ?: ($weekstart - WEEKSECS);
$monthstart   = strtotime('first day of this month 00:00', $now);
$qm           = (int) (floor((date('n', $now) - 1) / 3) * 3 + 1); // Quarter's first month: 1,4,7,10.
$quarterstart = mktime(0, 0, 0, $qm, 1, (int) date('Y', $now));

$svg_clock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
$svg_week  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M7 14h10"/></svg>';
$svg_cal   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M7 14h4"/></svg>';
$svg_month = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M7 13h2M11 13h2M15 13h2M7 17h2M11 17h2"/></svg>';

$stats = [
    ['label' => get_string('stat_today', 'local_trackmytime'),     'value' => $fmt($sumsecs($todaystart, $now + 1)), 'tone' => 'a', 'svg' => $svg_clock],
    ['label' => get_string('stat_thisweek', 'local_trackmytime'),  'value' => $fmt($sumsecs($weekstart, $now + 1)),  'tone' => 'b', 'svg' => $svg_week],
    ['label' => get_string('stat_lastweek', 'local_trackmytime'),  'value' => $fmt($sumsecs($lastweekst, $weekstart)), 'tone' => 'c', 'svg' => $svg_cal],
    ['label' => get_string('stat_thismonth', 'local_trackmytime'), 'value' => $fmt($sumsecs($monthstart, $now + 1)),  'tone' => 'd', 'svg' => $svg_month],
];

// Weekly bar chart — last 12 weeks (oldest to newest).
$weekbars = [];
$weekmax  = 1;
for ($i = 11; $i >= 0; $i--) {
    $ws  = strtotime("monday this week 00:00 - {$i} week", $now);
    $we  = strtotime('+1 week', $ws);
    $min = (int) round($sumsecs($ws, $we) / 60);
    $weekmax = max($weekmax, $min);
    $weekbars[] = ['mins' => $min, 'label' => userdate($ws, '%d %b')];
}
foreach ($weekbars as &$b) {
    $b['pct'] = (int) round($b['mins'] / $weekmax * 100);
    $b['tip'] = $b['label'] . ': ' . $fmt($b['mins'] * 60);
}
unset($b);

// Monthly bar chart — last 6 months (oldest to newest).
$monthbars = [];
$monthmax  = 1;
for ($i = 5; $i >= 0; $i--) {
    $ms  = strtotime("first day of -{$i} month 00:00", $now);
    $me  = strtotime('+1 month', $ms);
    $min = (int) round($sumsecs($ms, $me) / 60);
    $monthmax = max($monthmax, $min);
    $monthbars[] = ['mins' => $min, 'label' => userdate($ms, '%b')];
}
foreach ($monthbars as &$b) {
    $b['pct'] = (int) round($b['mins'] / $monthmax * 100);
    $b['tip'] = $b['label'] . ': ' . $fmt($b['mins'] * 60);
}
unset($b);

// Quarterly bar chart — last 8 quarters (oldest to newest).
$quarterbars = [];
$quartermax  = 1;
for ($i = 7; $i >= 0; $i--) {
    $qs  = strtotime('-' . ($i * 3) . ' month', $quarterstart);
    $qe  = strtotime('+3 month', $qs);
    $min = (int) round($sumsecs($qs, $qe) / 60);
    $quartermax = max($quartermax, $min);
    $qn  = (int) (floor((date('n', $qs) - 1) / 3) + 1);
    $quarterbars[] = ['mins' => $min, 'label' => 'Q' . $qn . " '" . date('y', $qs)];
}
foreach ($quarterbars as &$b) {
    $b['pct'] = (int) round($b['mins'] / $quartermax * 100);
    $b['tip'] = $b['label'] . ': ' . $fmt($b['mins'] * 60);
}
unset($b);

$hasactivitydata = ($sumsecs(0, $now + 1) > 0);

// Course-level time breakdown (donut) — top 6 courses by time.
$coursetime = [];
$donutgradient = '';
if ($DB->get_manager()->table_exists('trackmytime')) {
    $crows = $DB->get_records_sql(
        "SELECT courseid, SUM(timespent) AS secs
           FROM {trackmytime}
          WHERE userid = ? AND courseid <> ?
       GROUP BY courseid
       ORDER BY secs DESC",
        [$userid, SITEID], 0, 6
    );
    $palette = ['#2f6bf2', '#22c55e', '#f59e0b', '#8b5cf6', '#ef4444', '#0891b2'];
    $ctotal  = 0;
    foreach ($crows as $r) { $ctotal += (int) $r->secs; }
    $stops = [];
    $idx = 0; $cum = 0.0;
    foreach ($crows as $r) {
        $secs = (int) $r->secs;
        if ($secs <= 0) { continue; }
        $pct = $ctotal > 0 ? round($secs / $ctotal * 100, 1) : 0;
        try {
            $c = get_course((int) $r->courseid);
            $cname = format_string($c->shortname);
        } catch (\Throwable $e) {
            continue;
        }
        $color = $palette[$idx % count($palette)];
        $to    = ($idx === count($crows) - 1) ? 100 : round($cum + $pct, 1);
        $stops[] = "{$color} {$cum}% {$to}%";
        $coursetime[] = [
            'name'  => $cname,
            'time'  => $fmt($secs),
            'pct'   => $pct,
            'color' => $color,
        ];
        $cum = $to;
        $idx++;
    }
    if (!empty($stops)) {
        $donutgradient = 'conic-gradient(' . implode(', ', $stops) . ')';
    }
}
$hascoursetime = !empty($coursetime);

// Certificates — from mod_customcert (guarded; only if the plugin is installed).
$certs = [];
if ($DB->get_manager()->table_exists('customcert_issues')) {
    $crows = $DB->get_records_sql(
        "SELECT ci.id, ci.customcertid, ci.code, ci.timecreated, cc.name, cc.course
           FROM {customcert_issues} ci
           JOIN {customcert} cc ON cc.id = ci.customcertid
          WHERE ci.userid = ?
       ORDER BY ci.timecreated DESC",
        [$userid], 0, 40
    );
    foreach ($crows as $r) {
        $cm = get_coursemodule_from_instance('customcert', $r->customcertid, $r->course, false, IGNORE_MISSING);
        if (!$cm) {
            continue; // Activity removed — skip.
        }
        try {
            $c = get_course((int) $r->course);
            $cname = format_string($c->fullname);
        } catch (\Throwable $e) {
            $cname = '';
        }
        $certs[] = [
            'name'    => format_string($r->name),
            'course'  => $cname,
            'code'    => $r->code,
            'date'    => userdate((int) $r->timecreated, get_string('strftimedateshort', 'langconfig')),
            'viewurl' => (new moodle_url('/mod/customcert/view.php', ['id' => $cm->id]))->out(false),
        ];
    }
}
$hascerts = !empty($certs);

// ── Download-as-PDF report ────────────────────────────────────────────────────
if (optional_param('download', '', PARAM_ALPHA) === 'pdf') {
    require_once($CFG->libdir . '/pdflib.php');

    $fullname = fullname(\core_user::get_user($userid));
    $gendate  = userdate(time(), get_string('strftimedaydatetime', 'langconfig'));
    $primary  = '#2f6bf2';
    $prgb     = [47, 107, 242];
    $palette  = [[47, 107, 242], [34, 197, 94], [245, 158, 11], [139, 92, 246], [239, 68, 68], [8, 145, 178]];

    $pdf = new \pdf();
    $pdf->SetCreator('Track My Time');
    $pdf->SetTitle(get_string('performance_title', 'local_trackmytime') . ' — ' . $fullname);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);
    $pdf->SetHeaderData('', 0,
        get_string('performance_title', 'local_trackmytime'),
        $fullname . "\n" . get_string('pdf_generated', 'local_trackmytime', $gendate),
        $prgb, $prgb);
    $pdf->setHeaderFont(['helvetica', '', 9]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->SetMargins(15, 30, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(14);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();

    $h2 = function (string $t) use ($primary): string {
        return '<h2 style="color:' . $primary . ';font-size:13px;">' . s($t) . '</h2>';
    };
    // Ensure a chart block fits on the page; new page if not.
    $ensure = function ($needmm) use ($pdf) {
        if ($pdf->GetY() + $needmm > $pdf->getPageHeight() - 20) {
            $pdf->AddPage();
        }
    };
    // Draw a bar chart at the current Y from [{label, mins}, ...].
    $drawbars = function (string $title, array $bars) use ($pdf, $prgb, $ensure) {
        $ensure(58);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor($prgb[0], $prgb[1], $prgb[2]);
        $pdf->Cell(0, 7, $title, 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $x0 = $pdf->GetX();
        $y0 = $pdf->GetY() + 2;
        $chartw = 180; $charth = 38; $gap = 2;
        $n = max(1, count($bars));
        $barw = ($chartw - $gap * ($n - 1)) / $n;
        $max = 1;
        foreach ($bars as $b) { $max = max($max, (int) $b['mins']); }
        $pdf->SetDrawColor(210, 214, 222);
        $pdf->Line($x0, $y0 + $charth, $x0 + $chartw, $y0 + $charth);
        $pdf->SetFillColor($prgb[0], $prgb[1], $prgb[2]);
        $pdf->SetFont('helvetica', '', 6);
        $i = 0;
        foreach ($bars as $b) {
            $h  = ((int) $b['mins'] / $max) * $charth;
            $bx = $x0 + $i * ($barw + $gap);
            $by = $y0 + $charth - max(0.4, $h);
            $pdf->Rect($bx, $by, $barw, max(0.4, $h), 'F');
            $pdf->SetXY($bx - 1, $y0 + $charth + 1.5);
            $pdf->Cell($barw + 2, 3, $b['label'], 0, 0, 'C');
            $i++;
        }
        $pdf->SetY($y0 + $charth + 8);
    };
    // Draw a donut with legend from $coursetime.
    $drawdonut = function (array $ct) use ($pdf, $palette, $primary, $h2, $ensure) {
        $ensure(70);
        $pdf->writeHTML($h2(get_string('coursetime_title', 'local_trackmytime')), true, false, true, false, '');
        $cx = 45; $cy = $pdf->GetY() + 30; $r = 26;
        $ang = 0; $i = 0;
        foreach ($ct as $seg) {
            $sweep = (float) $seg['pct'] / 100 * 360;
            if ($sweep <= 0) { continue; }
            $c = $palette[$i % count($palette)];
            $pdf->SetFillColor($c[0], $c[1], $c[2]);
            $pdf->PieSector($cx, $cy, $r, $ang, $ang + $sweep, 'F', false, 0);
            $ang += $sweep; $i++;
        }
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Circle($cx, $cy, $r * 0.55, 0, 360, 'F');
        // Legend to the right.
        $lx = 82; $ly = $cy - 24; $i = 0;
        $pdf->SetFont('helvetica', '', 9);
        foreach ($ct as $seg) {
            $c = $palette[$i % count($palette)];
            $pdf->SetFillColor($c[0], $c[1], $c[2]);
            $pdf->Rect($lx, $ly + 0.5, 4, 4, 'F');
            $pdf->SetXY($lx + 6, $ly - 0.5);
            $pdf->Cell(110, 5, $seg['name'] . '   —   ' . $seg['time'] . ' · ' . $seg['pct'] . '%', 0, 0, 'L');
            $ly += 7; $i++;
        }
        $pdf->SetY(max($cy + $r + 4, $ly + 2));
    };

    // ── Build the report ──
    // 1. Time summary cards.
    $html = $h2(get_string('time_analytics', 'local_trackmytime')) . '<table border="0" cellpadding="6"><tr>';
    foreach ($stats as $st) {
        $html .= '<td width="22%" align="center" style="background-color:#eef4fb;"><b>' . s($st['value'])
              . '</b><br/><span style="color:#666;font-size:8px;">' . s($st['label']) . '</span></td><td width="1%"></td>';
    }
    if ($hasxp) {
        $html .= '<td width="22%" align="center" style="background-color:#e7f0ff;"><b>' . s($xppoints)
              . ' XP</b><br/><span style="color:#666;font-size:8px;">' . s(get_string('xp_level', 'local_trackmytime') . ' ' . $xplevel) . '</span></td>';
    }
    $html .= '</tr></table><br/>';
    $pdf->writeHTML($html, true, false, true, false, '');

    // 2. Charts (drawn).
    if ($hasactivitydata) {
        $drawbars(get_string('weekly_activity', 'local_trackmytime'), $weekbars);
        $drawbars(get_string('monthly_activity', 'local_trackmytime'), $monthbars);
        $drawbars(get_string('quarterly_activity', 'local_trackmytime'), $quarterbars);
    }
    if ($hascoursetime) {
        $drawdonut($coursetime);
    }

    // 3. Detail tables.
    $tbl = '';
    if (!empty($recent)) {
        $tbl .= $h2(get_string('recent_activity', 'local_trackmytime'))
             . '<table border="1" cellpadding="4"><tr style="background-color:#f3f4f6;"><th width="42%"><b>Activity</b></th><th width="23%"><b>Course</b></th><th width="20%"><b>Date</b></th><th width="15%" align="right"><b>Time</b></th></tr>';
        foreach ($recent as $ra) {
            $tbl .= '<tr><td>' . s($ra['name']) . '</td><td>' . s($ra['course']) . '</td><td>' . s($ra['when']) . '</td><td align="right">' . s($ra['time']) . '</td></tr>';
        }
        $tbl .= '</table><br/>';
    }
    if (!empty($grades)) {
        $tbl .= $h2(get_string('grade_overview', 'local_trackmytime'))
             . '<table border="1" cellpadding="4"><tr style="background-color:#f3f4f6;"><th width="60%"><b>Course</b></th><th width="25%" align="right"><b>Grade</b></th><th width="15%" align="center"><b>Status</b></th></tr>';
        foreach ($grades as $g) {
            $tbl .= '<tr><td>' . s($g['coursename']) . '</td><td align="right">' . s($g['grade']) . '</td><td align="center">' . ($g['passed'] ? 'Pass' : '-') . '</td></tr>';
        }
        $tbl .= '</table><br/>';
    }
    if (!empty($badges)) {
        $tbl .= $h2(get_string('badges_earned', 'local_trackmytime'))
             . '<table border="1" cellpadding="4"><tr style="background-color:#f3f4f6;"><th width="70%"><b>Badge</b></th><th width="30%"><b>Issued</b></th></tr>';
        foreach ($badges as $bd) {
            $tbl .= '<tr><td>' . s($bd['name']) . '</td><td>' . s($bd['dateissued']) . '</td></tr>';
        }
        $tbl .= '</table><br/>';
    }
    if ($hascerts) {
        $tbl .= $h2(get_string('certificates_wallet', 'local_trackmytime'))
             . '<table border="1" cellpadding="4"><tr style="background-color:#f3f4f6;"><th width="45%"><b>Certificate</b></th><th width="35%"><b>Course</b></th><th width="20%"><b>Date</b></th></tr>';
        foreach ($certs as $ce) {
            $tbl .= '<tr><td>' . s($ce['name']) . '</td><td>' . s($ce['course']) . '</td><td>' . s($ce['date']) . '</td></tr>';
        }
        $tbl .= '</table><br/>';
    }
    if ($tbl !== '') {
        $pdf->writeHTML($tbl, true, false, true, false, '');
    }

    $filename = 'performance-report-' . userdate(time(), '%Y%m%d') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
}

// Recent activity timeline — aggregate tracked time per activity, most recent first.
$recent = [];
if ($DB->get_manager()->table_exists('trackmytime')) {
    $rows = $DB->get_records_sql(
        "SELECT cmid, courseid, SUM(timespent) AS secs, MAX(timestart) AS lastseen
           FROM {trackmytime}
          WHERE userid = ? AND cmid IS NOT NULL
       GROUP BY cmid, courseid
       ORDER BY lastseen DESC",
        [$userid], 0, 12
    );
    // Monochrome line icons that match the section-header SVG style. Each entry
    // holds the inner paths; $svgwrap() adds the shared <svg> shell.
    $svgwrap = function (string $paths): string {
        return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" '
             . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
             . $paths . '</svg>';
    };
    $iconpaths = [
        'quiz'        => '<rect x="5" y="3.5" width="14" height="17" rx="2"/><path d="M9 3.5V3h6v.5"/><path d="M8.5 11l2 2 4-4.5"/>',
        'assign'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>',
        'page'        => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 12h6M9 16h6"/>',
        'resource'    => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'url'         => '<path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1 0l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1"/>',
        'book'        => '<path d="M4 5.5A2 2 0 0 1 6 3.5h13v14H6a2 2 0 0 0-2 2z"/><path d="M4 19.5A2 2 0 0 1 6 17.5h13"/>',
        'lesson'      => '<path d="M12 6C10.5 5 8 4.5 5 4.5V18c3 0 5.5.5 7 1.5 1.5-1 4-1.5 7-1.5V4.5c-3 0-5.5.5-7 1.5z"/><path d="M12 6v13.5"/>',
        'forum'       => '<path d="M20 11.5a7 7 0 0 1-9.6 6.5L5 19.5l1.6-4A7 7 0 1 1 20 11.5z"/>',
        'scorm'       => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M4 7.5l8 4.5 8-4.5M12 12v9"/>',
        'h5pactivity' => '<rect x="3" y="8" width="18" height="9" rx="4"/><path d="M7 11v3M5.5 12.5h3"/><circle cx="15.5" cy="12" r=".9"/><circle cx="17.5" cy="14" r=".9"/>',
        'feedback'    => '<path d="M5 20V11M10 20V4M15 20v-6M20 20H4"/>',
        'choice'      => '<circle cx="12" cy="12" r="9"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'workshop'    => '<path d="M14.7 6.3a4 4 0 0 0-5.2 5.2L4 17l3 3 5.5-5.5a4 4 0 0 0 5.2-5.2l-2.6 2.6-2.6-2.6z"/>',
        'glossary'    => '<path d="M6 3.5h11a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H6a2 2 0 0 1 0-4h11"/><path d="M9 8h6"/>',
        'wiki'        => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/>',
        'data'        => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>',
        'label'       => '<path d="M20 13l-7 7-9-9V4h7z"/><circle cx="8" cy="8" r="1.2"/>',
    ];
    $defaulticon = '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>';
    foreach ($rows as $r) {
        try {
            [$rc, $cm] = get_course_and_cm_from_cmid((int) $r->cmid);
        } catch (\Throwable $e) {
            continue; // Activity deleted — skip.
        }
        $recent[] = [
            'name'    => format_string($cm->name),
            'icon'    => $svgwrap($iconpaths[$cm->modname] ?? $defaulticon),
            'modname' => get_string('modulename', $cm->modname),
            'course'  => format_string($rc->shortname),
            'time'    => $fmt((int) $r->secs),
            'url'     => $cm->url ? $cm->url->out(false)
                                  : (new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]))->out(false),
            'when'    => userdate((int) $r->lastseen, '%d %b %Y'),
        ];
    }
}

// XP + level from block_xp — ONLY if that plugin is installed.
$hasxp = false; $xppoints = 0; $xplevel = 1;
if ($DB->get_manager()->table_exists('block_xp')) {
    $row = $DB->get_record_sql(
        'SELECT COALESCE(SUM(xp), 0) AS points, COALESCE(MAX(lvl), 1) AS lvl
           FROM {block_xp} WHERE userid = ?',
        [$userid]
    );
    if ($row) {
        $hasxp    = true;
        $xppoints = (int) $row->points;
        $xplevel  = (int) $row->lvl;
    }
}

// ── Template context ──────────────────────────────────────────────────────────

// Read the theme toggle directly — the theme lib is not guaranteed
// loaded on a local-plugin page, so calling its helper here fatals.
$heatmapcfg = get_config('local_trackmytime', 'showstudyheatmap');
$showstudyheatmap = ($heatmapcfg === false) ? true : (bool) $heatmapcfg;

$templatecontext = [
    'heatmapweeks'     => array_values($heatmapweeks),
    'grades'           => $grades,
    'badges'           => $badges,
    'hasgrades'        => !empty($grades),
    'hasbadges'        => !empty($badges),
    'showstudyheatmap' => $showstudyheatmap,
    'stats'            => $stats,
    'weekbars'         => $weekbars,
    'monthbars'        => $monthbars,
    'hasxp'            => $hasxp,
    'xppoints'         => number_format($xppoints),
    'xplevel'          => $xplevel,
    'quarterbars'      => $quarterbars,
    'hasactivitydata'  => $hasactivitydata,
    'recent'           => $recent,
    'hasrecent'        => !empty($recent),
    'coursetime'       => $coursetime,
    'hascoursetime'    => $hascoursetime,
    'donutgradient'    => $donutgradient,
    'certs'            => $certs,
    'hascerts'         => $hascerts,
    'pdfurl'           => (new moodle_url('/local/trackmytime/pages/performance.php', ['userid' => $userid, 'download' => 'pdf']))->out(false),
    'userid'           => $userid,
    'isownpage'        => ($userid == $USER->id),
];

$PAGE->requires->js_call_amd('local_trackmytime/performance', 'init', []);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_trackmytime/performance', $templatecontext);
echo $OUTPUT->footer();
