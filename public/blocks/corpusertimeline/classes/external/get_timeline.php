<?php
namespace block_corpusertimeline\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use context_system;

class get_timeline extends external_api {


    private static function icon_svg_map(): array {
        return [
            'login'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
            'quiz'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="12" y2="17"/></svg>',
            'assign'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
            'view'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
            'course'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
            'forum'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
            'complete' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
            'badge'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>',
            'scorm'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>',
            'h5p'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
            'default'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        ];
    }

    /** Public accessor for icon SVG map — used by block PHP for demo/server-side render. */
    public static function get_icon_svg_map_public(): array {
        return self::icon_svg_map();
    }

    private static function event_map(): array {
        return [
            'loggedin'             => ['label' => 'Logged in',            'icon' => 'login'],
            'course_viewed'        => ['label' => 'Viewed course',        'icon' => 'course'],
            'course_module_viewed' => ['label' => 'Viewed activity',      'icon' => 'view'],
            'attempt_submitted'    => ['label' => 'Submitted quiz',       'icon' => 'quiz'],
            'attempt_started'      => ['label' => 'Started quiz',         'icon' => 'quiz'],
            'attempt_reviewed'     => ['label' => 'Reviewed quiz',        'icon' => 'quiz'],
            'submission_created'   => ['label' => 'Submitted assignment', 'icon' => 'assign'],
            'submission_updated'   => ['label' => 'Updated assignment',   'icon' => 'assign'],
            'assessable_submitted' => ['label' => 'Submitted work',       'icon' => 'assign'],
            'post_created'         => ['label' => 'Posted in forum',      'icon' => 'forum'],
            'discussion_created'   => ['label' => 'Started discussion',   'icon' => 'forum'],
            'sco_launched'         => ['label' => 'Launched SCORM',       'icon' => 'scorm'],
            'statement_received'   => ['label' => 'Completed H5P',        'icon' => 'h5p'],
            'course_completed'     => ['label' => 'Completed course',     'icon' => 'complete'],
            'badge_awarded'        => ['label' => 'Earned badge',         'icon' => 'badge'],
        ];
    }

    /**
     * Core data-fetching logic — no $PAGE interaction, safe to call from get_content().
     * Called by both execute() (AJAX) and block_corpusertimeline::get_content() (page render).
     */
    public static function fetch_data(int $offset, int $limit, string $period): array {
        global $DB, $USER;

        $limit  = min(max($limit, 1), 50);
        $offset = max($offset, 0);

        $params = ['userid' => $USER->id];
        $where  = 'userid = :userid';

        if ($period === 'week') {
            $where           .= ' AND timecreated >= :since';
            $params['since']  = strtotime('monday this week midnight');
        }

        $rows = $DB->get_records_select(
            'logstore_standard_log',
            $where,
            $params,
            'timecreated DESC',
            'id,eventname,action,target,courseid,contextinstanceid,timecreated,other',
            $offset,
            $limit + 1
        );

        $hasmore = count($rows) > $limit;
        if ($hasmore) { array_pop($rows); }

        $emap      = self::event_map();
        $events    = [];
        $namecache = [];
        $today     = mktime(0, 0, 0, (int)date('n'), (int)date('j'), (int)date('Y'));

        $svgmap = self::icon_svg_map();
        foreach ($rows as $row) {
            $shortname = '';
            if (preg_match('/\\\\([a-z_]+)$/', $row->eventname, $m)) {
                $shortname = $m[1];
            }
            $lookup = $emap[$shortname] ?? ['label' => ucwords(str_replace('_', ' ', $row->action)), 'icon' => 'default'];

            $coursename = '';
            if ((int)$row->courseid > 1) {
                if (!isset($namecache['c_' . $row->courseid])) {
                    $c = $DB->get_record('course', ['id' => $row->courseid], 'fullname', IGNORE_MISSING);
                    $namecache['c_' . $row->courseid] = $c ? format_string($c->fullname) : '';
                }
                $coursename = $namecache['c_' . $row->courseid];
            }

            $badge = '';
            if ($row->other && $shortname === 'attempt_submitted') {
                $other = json_decode($row->other, true);
                if (is_array($other) && isset($other['sumgrades'], $other['maxgrade'])
                    && (float)$other['maxgrade'] > 0) {
                    $pct   = round(((float)$other['sumgrades'] / (float)$other['maxgrade']) * 100);
                    $badge = $pct >= 50 ? 'passed' : 'below';
                }
            }

            $rowday = mktime(0, 0, 0, (int)date('n', $row->timecreated),
                             (int)date('j', $row->timecreated), (int)date('Y', $row->timecreated));
            if ($rowday === $today) {
                $daylabel = 'Today · ' . userdate($row->timecreated, '%a, %b %e');
            } elseif ($rowday === ($today - DAYSECS)) {
                $daylabel = 'Yesterday · ' . userdate($row->timecreated, '%a, %b %e');
            } else {
                $daylabel = userdate($row->timecreated, '%A, %b %e');
            }

            $events[] = [
                'id'         => (int)$row->id,
                'label'      => $lookup['label'],
                'iconkey'    => $lookup['icon'],
                'iconsvg'    => $svgmap[$lookup['icon']] ?? $svgmap['default'],
                'coursename' => $coursename,
                'badge'      => $badge,
                'ispassed'   => $badge === 'passed',
                'isbelow'    => $badge === 'below',
                'timestr'    => userdate($row->timecreated, '%l:%M %p'),
                'daylabel'   => $daylabel,
                'dayts'      => (int)$rowday,
            ];
        }

        $groups = [];
        foreach ($events as $ev) {
            $key = $ev['dayts'];
            if (!isset($groups[$key])) {
                $groups[$key] = ['daylabel' => $ev['daylabel'], 'events' => []];
            }
            $groups[$key]['events'][] = $ev;
        }

        return [
            'groups'  => array_values($groups),
            'hasmore' => $hasmore,
            'total'   => count($events),
        ];
    }

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'offset' => new external_value(PARAM_INT,  'Offset',   VALUE_DEFAULT, 0),
            'limit'  => new external_value(PARAM_INT,  'Limit',    VALUE_DEFAULT, 20),
            'period' => new external_value(PARAM_TEXT, 'week|all', VALUE_DEFAULT, 'week'),
        ]);
    }

    /**
     * AJAX entry point — validates context/auth, then delegates to fetch_data().
     */
    public static function execute(int $offset = 0, int $limit = 20, string $period = 'week'): array {
        ['offset' => $offset, 'limit' => $limit, 'period' => $period] =
            self::validate_parameters(self::execute_parameters(),
                ['offset' => $offset, 'limit' => $limit, 'period' => $period]);

        self::validate_context(context_system::instance());

        return self::fetch_data($offset, $limit, $period);
    }

    public static function execute_returns(): external_single_structure {
        $event = new external_single_structure([
            'id'         => new external_value(PARAM_INT,  'Log id'),
            'label'      => new external_value(PARAM_TEXT, 'Action label'),
            'iconkey'    => new external_value(PARAM_TEXT, 'Icon key'),
            'iconsvg'    => new external_value(PARAM_RAW,  'Icon SVG markup'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name'),
            'badge'      => new external_value(PARAM_TEXT, 'passed|below|empty', VALUE_DEFAULT, ''),
            'ispassed'   => new external_value(PARAM_BOOL, 'Passed badge'),
            'isbelow'    => new external_value(PARAM_BOOL, 'Below threshold badge'),
            'timestr'    => new external_value(PARAM_TEXT, 'Formatted time'),
            'daylabel'   => new external_value(PARAM_TEXT, 'Day heading'),
            'dayts'      => new external_value(PARAM_INT,  'Day timestamp'),
        ]);
        return new external_single_structure([
            'groups'  => new external_multiple_structure(new external_single_structure([
                'daylabel' => new external_value(PARAM_TEXT, 'Day heading'),
                'events'   => new external_multiple_structure($event),
            ])),
            'hasmore' => new external_value(PARAM_BOOL, 'More events available'),
            'total'   => new external_value(PARAM_INT,  'Count in this batch'),
        ]);
    }
}
