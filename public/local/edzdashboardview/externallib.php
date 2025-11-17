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
 * TODO describe file externallib
 *
 * @package    local_edzdashboardview
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// namespace local_edzdashboardview;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/lib/modinfolib.php');

// require_once("$CFG->libdir/externallib.php");


class local_edzdashboardview_external extends external_api {


    // ---------------- USER TAB WEB SERVICE ----------------

    // ✅ USER Parameters
    public static function get_user_dashboard_data_parameters() {
        return new external_function_parameters([
            'range' => new external_value(PARAM_RAW, 'Range: 1day, 7day, 30day, 1year')
        ]);
    }
    // ✅ USER Logic
    public static function get_user_dashboard_data($range) {
        global $DB;

        // echo $range;
        // die();
        // $params = self::validate_parameters(
        //     self::get_user_dashboard_data_parameters(),
        //     ['range' => $range]
        // );

        $now          = time();
        $todayStart   = strtotime('today midnight');
        $tomorrowStart= strtotime('tomorrow midnight');
        $currentHourStart = strtotime(date('Y-m-d H:00:00', $now));
        $last7Start   = strtotime('-6 days', $todayStart);
        $last28Start  = strtotime('-27 days', $todayStart);
        $yearStart    = strtotime('first day of January this year 00:00');
        $nextYearStart= strtotime('first day of January next year 00:00');
        $hoursSoFar   = (int)date('G', $now) + 1;

        
        
        // --- Active Users ---
        $loggedin_today = self::get_login_counts_binned($todayStart, $currentHourStart + 3600, 3600, $hoursSoFar);
        $loggedin_7day  = self::get_login_counts_binned($last7Start, $tomorrowStart, 86400, 7);
        $loggedin_30day = self::get_login_counts_binned($last28Start, $tomorrowStart, 7 * 86400, 4);
        $loggedin_1year = self::get_login_counts_months_current_year($yearStart, $nextYearStart);



        $loggedin = [
            '1day'  => $loggedin_today,
            '7day'  => $loggedin_7day,
            '30day' => $loggedin_30day,
            '1year' => $loggedin_1year,
        ];
        $activeusers = [
            '1day'  => array_sum($loggedin['1day']),
            '7day'  => array_sum($loggedin['7day']),
            '30day' => array_sum($loggedin['30day']),
            '1year' => array_sum($loggedin['1year'])
        ];

        // --- Enrolments ---
        $enrol_today = self::get_enrol_counts_binned($todayStart, $currentHourStart + 3600, 3600, $hoursSoFar);
        $enrol_7day  = self::get_enrol_counts_binned($last7Start, $tomorrowStart, 86400, 7);
        $enrol_30day = self::get_enrol_counts_binned($last28Start, $tomorrowStart, 7 * 86400, 4);
        $enrol_1year = self::get_enrol_counts_months_current_year($yearStart, $nextYearStart);

        $enrolments = [
            '1day'  => $enrol_today,
            '7day'  => $enrol_7day,
            '30day' => $enrol_30day,
            '1year' => $enrol_1year,
        ];
        $enrolcounts = [
            '1day'  => array_sum($enrolments['1day']),
            '7day'  => array_sum($enrolments['7day']),
            '30day' => array_sum($enrolments['30day']),
            '1year' => array_sum($enrolments['1year'])
        ];

        // --- Registered Users breakdown ---
        $registered_today = self::get_registered_counts_binned($todayStart, $currentHourStart + 3600, 3600, $hoursSoFar);
        $registered_7day  = self::get_registered_counts_binned($last7Start, $tomorrowStart, 86400, 7);
        $registered_30day = self::get_registered_counts_binned($last28Start, $tomorrowStart, 7 * 86400, 4);
        $registered_1year = self::get_registered_counts_months_current_year($yearStart, $nextYearStart);

        $registered = [
            '1day'  => $registered_today,
            '7day'  => $registered_7day,
            '30day' => $registered_30day,
            '1year' => $registered_1year,
        ];
        // Registered Users
        $registeredcounts = [
            '1day'  => array_sum($registered['1day']),
            '7day'  => array_sum($registered['7day']),
            '30day' => array_sum($registered['30day']),
            '1year' => array_sum($registered['1year'])
        ];
        // Ranges
        $webmobile = [
            '1day'  => self::get_web_mobile_counts($todayStart, $tomorrowStart),
            '7day'  => self::get_web_mobile_counts($last7Start, $tomorrowStart),
            '30day' => self::get_web_mobile_counts($last28Start, $tomorrowStart),
            '1year' => self::get_web_mobile_counts($yearStart, $nextYearStart),
        ];



        return [
            'cards' => [
                'activeusers' => $activeusers,
                'enrolments'  => $enrolcounts,
                'registeredusers' => $registeredcounts,
                'webmobile' => $webmobile
            ],
            'loggedin'   => $loggedin,
            'enrolments' => $enrolments,
            'registered' => $registered,
            'webmobile' => $webmobile
        ];
    }
    // ✅USER Return type
    public static function get_user_dashboard_data_returns() {
        return new external_single_structure([
            'cards' => new external_single_structure([
                'activeusers' => new external_multiple_structure(new external_value(PARAM_INT, 'Active users count')),
                'enrolments'  => new external_multiple_structure(new external_value(PARAM_INT, 'Enrolments count')),
                'registeredusers' => new external_multiple_structure(new external_value(PARAM_INT, 'Registered users count')),
                'webmobile' => new external_single_structure([
                    '1day' => new external_single_structure([
                        'web' => new external_value(PARAM_INT, 'Web logins count'),
                        'mobile' => new external_value(PARAM_INT, 'Mobile logins count')
                    ]),
                    '7day' => new external_single_structure([
                        'web' => new external_value(PARAM_INT, 'Web logins count'),
                        'mobile' => new external_value(PARAM_INT, 'Mobile logins count')
                    ]),
                    '30day' => new external_single_structure([
                        'web' => new external_value(PARAM_INT, 'Web logins count'),
                        'mobile' => new external_value(PARAM_INT, 'Mobile logins count')
                    ]),
                    '1year' => new external_single_structure([
                        'web' => new external_value(PARAM_INT, 'Web logins count'),
                        'mobile' => new external_value(PARAM_INT, 'Mobile logins count')
                    ])
                ])

            ]),
            'loggedin' => new external_multiple_structure(new external_multiple_structure(
                new external_value(PARAM_INT, 'Login series')
            )),
            'enrolments' => new external_multiple_structure(new external_multiple_structure(
                new external_value(PARAM_INT, 'Enrol series')
            )),
            'registered' => new external_multiple_structure(new external_multiple_structure(
                new external_value(PARAM_INT, 'Registered series')
            )),
            'webmobile' => new external_single_structure([
                '1day' => new external_single_structure([
                    'web' => new external_value(PARAM_INT, 'Web logins count'),
                    'mobile' => new external_value(PARAM_INT, 'Mobile logins count')
                ]),
                '7day' => new external_single_structure([
                    'web' => new external_value(PARAM_INT, 'Web logins count'),
                    'mobile' => new external_value(PARAM_INT, 'Mobile logins count')
                ]),
                '30day' => new external_single_structure([
                    'web' => new external_value(PARAM_INT, 'Web logins count'),
                    'mobile' => new external_value(PARAM_INT, 'Mobile logins count')
                ]),
                '1year' => new external_single_structure([
                    'web' => new external_value(PARAM_INT, 'Web logins count'),
                    'mobile' => new external_value(PARAM_INT, 'Mobile logins count')
                ])
            ])
        ]);
    }



    // ---------------- SITE TAB WEB SERVICE ----------------
    // ✅SITE Parameters
    public static function get_site_dashboard_data_parameters() {
        return new external_function_parameters([
            'range' => new external_value(PARAM_RAW, 'Range: 1day, 7day, 30day, 1year')
        ]);
    }
    // ✅ Site Logic
    public static function get_site_dashboard_data($range) {
        global $DB, $CFG;

        // --- System Info ---
        $cpu_load = sys_getloadavg()[0]; // 1-min avg
        $cpu_usage = round(($cpu_load / sys_getloadavg()[2]) * 100, 2); // approx %

        // --- Memory ---
        $memory = [];
        $meminfo = file_get_contents("/proc/meminfo");
        if ($meminfo) {
            preg_match('/MemTotal:\s+(\d+) kB/', $meminfo, $matches);
            $memtotal = $matches[1] / 1024 / 1024; // GB

            preg_match('/MemAvailable:\s+(\d+) kB/', $meminfo, $matches);
            $memavail = $matches[1] / 1024 / 1024; // GB

            $memused = $memtotal - $memavail;
            $mempercent = round(($memused / $memtotal) * 100, 2);

            $memory = [
                'percent' => $mempercent,
                'used'    => round($memused, 2),
                'total'   => round($memtotal, 2)
            ];
        }

        // --- Storage ---
        $disk_total = round(disk_total_space($CFG->dataroot) / 1024 / 1024 / 1024, 2);
        $disk_free  = round(disk_free_space($CFG->dataroot) / 1024 / 1024 / 1024, 2);
        $disk_used  = $disk_total - $disk_free;
        $disk_percent = round(($disk_used / $disk_total) * 100, 2);

        // --- Users ---
        $siteactiveusers = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
        $suspended   = $DB->count_records('user', ['suspended' => 1]);
        $deleted     = $DB->count_records('user', ['deleted' => 1]);

        // --- Live Users (last 30 mins) ---
        $timecheck = time() - 1800;
        $sql = "SELECT COUNT(DISTINCT userid)
                FROM {logstore_standard_log}
                WHERE timecreated > $timecheck
                AND userid > 0";
        $liveusers = $DB->get_field_sql($sql, ['timecheck' => $timecheck]);

        // --- Plugins and directory sizes ---
        $plugins = core_plugin_manager::instance()->get_plugins();
        $plugin_count = count($plugins);

        $srcdirsize = display_size(get_directory_size($CFG->dirroot));
        $datadirsize = display_size(get_directory_size($CFG->dataroot));

        $sitecontext = [
            'theme_designermode' => theme_config::load('boost')->settings->themedesignermode ?? false,
            'debugging'          => debugging(),
            'plugins'            => $plugin_count,
            'srcdirsize'         => $srcdirsize,
            'datadirsize'        => $datadirsize
        ];

        // --- Usage logs (last 24h in 5-min bins) ---
        $now = time();
        $yesterday = $now - 24 * 3600;
        $bins = 288; // 24h * 12 bins per hour (5-min)

        $usage_24h = self::get_siteusage_last24h($yesterday, $now, 300, $bins);

        // ✅ Return dataset for Mustache
        return [
            'sitecontext' => $sitecontext,
            'cpu' => [
                'percent' => $cpu_usage,
            ],
            'memory' => [
                'percent' => $memory['percent'] ?? 0,
                'used'    => $memory['used'] ?? 0,
                'total'   => $memory['total'] ?? 0
            ],
            'storage' => [
                'percent' => $disk_percent,
                'used'    => $disk_used,
                'total'   => $disk_total
            ],
            'users' => [
                'active'    => $siteactiveusers,
                'suspended' => $suspended,
                'deleted'   => $deleted
            ],
            'liveusers' => (int)$liveusers,
            'usage24h' => $usage_24h
        ];
    }
    // ✅ sITE Return type
    public static function get_site_dashboard_data_returns() {
        return new external_single_structure([
            'sitecontext' => new external_single_structure([
                'theme_designermode' => new external_value(PARAM_BOOL, 'Theme designer mode'),
                'debugging'          => new external_value(PARAM_BOOL, 'Debugging enabled'),
                'plugins'            => new external_value(PARAM_INT, 'Number of plugins'),
                'srcdirsize'         => new external_value(PARAM_RAW, 'Source dir size'),
                'datadirsize'        => new external_value(PARAM_RAW, 'Data dir size')
            ]),
            'cpu' => new external_single_structure([
                'percent' => new external_value(PARAM_FLOAT, 'CPU usage %')
            ]),
            'memory' => new external_single_structure([
                'percent' => new external_value(PARAM_FLOAT, 'Memory usage %'),
                'used'    => new external_value(PARAM_FLOAT, 'Used GB'),
                'total'   => new external_value(PARAM_FLOAT, 'Total GB')
            ]),
            'storage' => new external_single_structure([
                'percent' => new external_value(PARAM_FLOAT, 'Disk usage %'),
                'used'    => new external_value(PARAM_FLOAT, 'Used GB'),
                'total'   => new external_value(PARAM_FLOAT, 'Total GB')
            ]),
            'users' => new external_single_structure([
                'active'    => new external_value(PARAM_INT, 'Active users'),
                'suspended' => new external_value(PARAM_INT, 'Suspended users'),
                'deleted'   => new external_value(PARAM_INT, 'Deleted users')
            ]),
            'liveusers' => new external_value(PARAM_INT, 'Live users in last 30 min'),
            'usage24h' => new external_single_structure([
                'cpu' => new external_multiple_structure(new external_value(PARAM_FLOAT, 'CPU %')),
                'memory' => new external_multiple_structure(new external_value(PARAM_FLOAT, 'Memory %')),
                'storage' => new external_multiple_structure(new external_value(PARAM_FLOAT, 'Storage %'))
            ])
        ]);
    }


    // ---------------- COURSE TAB WEB SERVICE ----------------
    // TODO implement course tab web service
    public static function get_course_dashboard_data_parameters() {
        return new external_function_parameters([
            'range' => new external_value(PARAM_RAW, 'Range: 1day, 7day, 30day, 1year')
        ]);
    }
    // ✅ Course Logic
    public static function get_course_dashboard_data($range) {
        global $DB;
        
        // echo $range;
        // die();
        // $params = self::validate_parameters(
        //     self::get_user_dashboard_data_parameters(),
        //     ['range' => $range]
        // );

        $now          = time();
        $todayStart   = strtotime('today midnight');
        $tomorrowStart= strtotime('tomorrow midnight');
        $currentHourStart = strtotime(date('Y-m-d H:00:00', $now));
        $last7Start   = strtotime('-6 days', $todayStart);
        $last28Start  = strtotime('-27 days', $todayStart);
        $yearStart    = strtotime('first day of January this year 00:00');
        $nextYearStart= strtotime('first day of January next year 00:00');
        $hoursSoFar   = (int)date('G', $now) + 1;

        // --- Courses Created breakdown ---
        $courses_today = self::get_course_counts_binned($todayStart, $currentHourStart + 3600, 3600, $hoursSoFar);
        $courses_7day  = self::get_course_counts_binned($last7Start, $tomorrowStart, 86400, 7);
        $courses_30day = self::get_course_counts_binned($last28Start, $tomorrowStart, 7 * 86400, 4);
        $courses_1year = self::get_course_counts_months_current_year($yearStart, $nextYearStart);

        $courses = [
            '1day'  => $courses_today,
            '7day'  => $courses_7day,
            '30day' => $courses_30day,
            '1year' => $courses_1year,
        ];

        // Total counts for cards
        $coursecounts = [
            '1day'  => array_sum($courses['1day']),
            '7day'  => array_sum($courses['7day']),
            '30day' => array_sum($courses['30day']),
            '1year' => array_sum($courses['1year'])
        ];


        // Ranges
        $Enrolvscompletecourse = [
            '1day'  => self::get_enrolled_vs_completed_counts($todayStart, $tomorrowStart),
            '7day'  => self::get_enrolled_vs_completed_counts($last7Start, $tomorrowStart),
            '30day' => self::get_enrolled_vs_completed_counts($last28Start, $tomorrowStart),
            '1year' => self::get_enrolled_vs_completed_counts($yearStart, $nextYearStart),
        ];



        // ========================
        // Collect everything
        // ========================

        // Example: assume you already calculated time boundaries:
        /// $todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start,
        /// $yearStart, $nextYearStart, $tomorrowStart

        $badges      = self::get_badge_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart);
        $forum       = self::get_forum_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart);
        $assignment  = self::get_assignment_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart);
        $quizatt     = self::get_quizattempt_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart);
        $quizsub     = self::get_quizsub_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart);

        // Full dashboard array
        $dashboard = [
            'badges'     => $badges,
            'forum'      => $forum,
            'assignment' => $assignment,
            'quizatt'    => $quizatt,
            'quizsub'    => $quizsub,
        ];

        // Totals for cards (sum per range)
        $dashboardtotalscount = [
            'badges'     => array_map('array_sum', $badges),
            'forum'      => array_map('array_sum', $forum),
            'assignment' => array_map('array_sum', $assignment),
            'quizatt'    => array_map('array_sum', $quizatt),
            'quizsub'    => array_map('array_sum', $quizsub),
        ];

        
        // --- Top 5 courses by enrolments ---
        $top_courses = [
            '1day'  => self::get_top_courses_binned($todayStart, $tomorrowStart, 5),
            '7day'  => self::get_top_courses_binned($last7Start, $tomorrowStart, 5),
            '30day' => self::get_top_courses_binned($last28Start, $tomorrowStart, 5),
            '1year' => self::get_top_courses_binned($yearStart, $nextYearStart, 5),
        ];

        // --- Top 5 categories by enrolments ---
        $top_categories = [
            '1day'  => self::get_top_categories_binned($todayStart, $tomorrowStart, 5),
            '7day'  => self::get_top_categories_binned($last7Start, $tomorrowStart, 5),
            '30day' => self::get_top_categories_binned($last28Start, $tomorrowStart, 5),
            '1year' => self::get_top_categories_binned($yearStart, $nextYearStart, 5),
        ];
        

        return [
            'cards' => [
               
                'courses' => $coursecounts,
                'enrolvscompletecourse' => $Enrolvscompletecourse,
                'topcourses' => $top_courses,
                'topcategories' => $top_categories,
                'dashboardtotalscount' => $dashboardtotalscount,


            ],
           
            'courses' => $courses,
            'enrolvscompletecourse' => $Enrolvscompletecourse,
            
        ];
    }
    // ✅ Course Return type
    public static function get_course_dashboard_data_returns() {
        return new external_single_structure([
            'cards' => new external_single_structure([
                'courses' => new external_multiple_structure(new external_value(PARAM_INT, 'Courses created count')),
                'enrolvscompletecourse' => new external_single_structure([
                    '1day' => new external_single_structure([
                        'enrolled' => new external_value(PARAM_INT, 'Enrolled users count'),
                        'completed' => new external_value(PARAM_INT, 'Completed users count')
                    ]),
                    '7day' => new external_single_structure([
                        'enrolled' => new external_value(PARAM_INT, 'Enrolled users count'),
                        'completed' => new external_value(PARAM_INT, 'Completed users count')
                    ]),
                    '30day' => new external_single_structure([
                        'enrolled' => new external_value(PARAM_INT, 'Enrolled users count'),
                        'completed' => new external_value(PARAM_INT, 'Completed users count')
                    ]),
                    '1year' => new external_single_structure([
                        'enrolled' => new external_value(PARAM_INT, 'Enrolled users count'),
                        'completed' => new external_value(PARAM_INT, 'Completed users count')
                    ])
                ]),
                'topcourses' => new external_single_structure([
                    '1day' => new external_multiple_structure(new external_single_structure([
                        'courseid' => new external_value(PARAM_INT, 'Course ID'),
                        'fullname' => new external_value(PARAM_RAW, 'Course full name'),
                        'total'    => new external_value(PARAM_INT, 'Enrolments count'),
                        'image'    => new external_value(PARAM_URL, 'Course image URL')
                    ])),
                    '7day' => new external_multiple_structure(new external_single_structure([
                        'courseid' => new external_value(PARAM_INT, 'Course ID'),
                        'fullname' => new external_value(PARAM_RAW, 'Course full name'),
                        'total'    => new external_value(PARAM_INT, 'Enrolments count'),
                        'image'    => new external_value(PARAM_URL, 'Course image URL')
                    ])),
                    '30day' => new external_multiple_structure(new external_single_structure([
                        'courseid' => new external_value(PARAM_INT, 'Course ID'),
                        'fullname' => new external_value(PARAM_RAW, 'Course full name'),
                        'total'    => new external_value(PARAM_INT, 'Enrolments count'),
                        'image'    => new external_value(PARAM_URL, 'Course image URL')
                    ])),
                    '1year' => new external_multiple_structure(new external_single_structure([
                        'courseid' => new external_value(PARAM_INT, 'Course ID'),
                        'fullname' => new external_value(PARAM_RAW, 'Course full name'),
                        'total'    => new external_value(PARAM_INT, 'Enrolments count'),
                        'image'    => new external_value(PARAM_URL, 'Course image URL')
                    ]))
                ]),
                'topcategories' => new external_single_structure([
                    '1day' => new external_multiple_structure(new external_single_structure([
                        'categoryid' => new external_value(PARAM_INT, 'Category ID'),
                        'categoryname' => new external_value(PARAM_RAW, 'Category name'),
                        'total'    => new external_value(PARAM_INT, 'Enrolments count'),
                        'image'    => new external_value(PARAM_URL, 'Category image URL')
                    ])),
                    '7day' => new external_multiple_structure(new external_single_structure([
                        'categoryid' => new external_value(PARAM_INT, 'Category ID'), 
                        'categoryname' => new external_value(PARAM_RAW, 'Category name'),
                        'total'    => new external_value(PARAM_INT, 'Enrolments count'),
                        'image'    => new external_value(PARAM_URL, 'Category image URL')
                    ])),
                    '30day' => new external_multiple_structure(new external_single_structure([
                        'categoryid' => new external_value(PARAM_INT, 'Category ID'),
                        'categoryname' => new external_value(PARAM_RAW, 'Category name'),
                        'total'    => new external_value(PARAM_INT, 'Enrolments count'),
                        'image'    => new external_value(PARAM_URL, 'Category image URL')
                    ])),
                    '1year' => new external_multiple_structure(new external_single_structure([
                        'categoryid' => new external_value(PARAM_INT, 'Category ID'),
                        'categoryname' => new external_value(PARAM_RAW, 'Category name'),
                        'total'    => new external_value(PARAM_INT, 'Enrolments count'),
                        'image'    => new external_value(PARAM_URL, 'Category image URL')
                    ]))
                ]),
                'dashboardtotalscount' => new external_single_structure([
                    'badges'     => new external_multiple_structure(new external_value(PARAM_INT, 'Badges issued count')),
                    'forum'      => new external_multiple_structure(new external_value(PARAM_INT, 'Forum posts count')),
                    'assignment' => new external_multiple_structure(new external_value(PARAM_INT, 'Assignment submissions count')),
                    'quizatt'    => new external_multiple_structure(new external_value(PARAM_INT, 'Quiz attempts count')),
                    'quizsub'    => new external_multiple_structure(new external_value(PARAM_INT, 'Quiz submissions count'))
                ])
            ]),
            'courses' => new external_multiple_structure(new external_multiple_structure(
                new external_value(PARAM_INT, 'Courses created series')
            )),
            'enrolvscompletecourse' => new external_single_structure([
                '1day' => new external_single_structure([
                    'enrolled' => new external_value(PARAM_INT, 'Enrolled users count'),
                    'completed' => new external_value(PARAM_INT, 'Completed users count')
                ]),
                '7day' => new external_single_structure([
                    'enrolled' => new external_value(PARAM_INT, 'Enrolled users count'),
                    'completed' => new external_value(PARAM_INT, 'Completed users count')
                ]),
                '30day' => new external_single_structure([
                    'enrolled' => new external_value(PARAM_INT, 'Enrolled users count'),
                    'completed' => new external_value(PARAM_INT, 'Completed users count')
                ]),
                '1year' => new external_single_structure([
                    'enrolled' => new external_value(PARAM_INT, 'Enrolled users count'),        
                    'completed' => new external_value(PARAM_INT, 'Completed users count')
                ]) 
            ])            
        ]);
    }


    // ---------------- REWARD TAB WEB SERVICE ----------------
     // ✅ Reward Parameters
    public static function get_reward_dashboard_data_parameters() {
        return new external_function_parameters([
            'range' => new external_value(PARAM_RAW, 'Range: 1day, 7day, 30day, 1year')
        ]);
    }
    // ✅ Reward Logic
    public static function get_reward_dashboard_data($range) {
        global $DB;

        // echo $range;
        // die();
        // $params = self::validate_parameters(
        //     self::get_user_dashboard_data_parameters(),
        //     ['range' => $range]
        // );

        $now          = time();
        $todayStart   = strtotime('today midnight');
        $tomorrowStart= strtotime('tomorrow midnight');
        $currentHourStart = strtotime(date('Y-m-d H:00:00', $now));
        $last7Start   = strtotime('-6 days', $todayStart);
        $last28Start  = strtotime('-27 days', $todayStart);
        $yearStart    = strtotime('first day of January this year 00:00');
        $nextYearStart= strtotime('first day of January next year 00:00');
        $hoursSoFar   = (int)date('G', $now) + 1;

        
        // --- Reward Points breakdown ---
        $reward_today = self::get_reward_points_binned($todayStart, $currentHourStart + 3600, 3600, $hoursSoFar);
        $reward_7day  = self::get_reward_points_binned($last7Start, $tomorrowStart, 86400, 7);
        $reward_30day = self::get_reward_points_binned($last28Start, $tomorrowStart, 7 * 86400, 4);
        $reward_1year = self::get_reward_points_months_current_year($yearStart, $nextYearStart);

        $rewardpoints = [
            '1day'  => $reward_today,
            '7day'  => $reward_7day,
            '30day' => $reward_30day,
            '1year' => $reward_1year,
        ];

        // --- Total counts for reward cards ---
        $rewardcounts = [
            '1day'  => array_sum($rewardpoints['1day']),
            '7day'  => array_sum($rewardpoints['7day']),
            '30day' => array_sum($rewardpoints['30day']),
            '1year' => array_sum($rewardpoints['1year'])
        ];

        // --- Badges Issued breakdown ---
        $badges_today = self::get_badge_counts_binned($todayStart, $currentHourStart + 3600, 3600, $hoursSoFar);
        $badges_7day  = self::get_badge_counts_binned($last7Start, $tomorrowStart, 86400, 7);
        $badges_30day = self::get_badge_counts_binned($last28Start, $tomorrowStart, 7 * 86400, 4);
        $badges_1year = self::get_badge_counts_months_current_year($yearStart, $nextYearStart);

        $badges = [
            '1day'  => $badges_today,
            '7day'  => $badges_7day,
            '30day' => $badges_30day,
            '1year' => $badges_1year,
        ];

        // Total counts for cards
        $badgecounts = [
            '1day'  => array_sum($badges['1day']),
            '7day'  => array_sum($badges['7day']),
            '30day' => array_sum($badges['30day']),
            '1year' => array_sum($badges['1year'])
        ];


        // --- Certificates Issued breakdown ---
        $certs_today = self::get_certificate_counts_binned($todayStart, $currentHourStart + 3600, 3600, $hoursSoFar);
        $certs_7day  = self::get_certificate_counts_binned($last7Start, $tomorrowStart, 86400, 7);
        $certs_30day = self::get_certificate_counts_binned($last28Start, $tomorrowStart, 7 * 86400, 4);
        $certs_1year = self::get_certificate_counts_months_current_year($yearStart, $nextYearStart);

        $certificates = [
            '1day'  => $certs_today,
            '7day'  => $certs_7day,
            '30day' => $certs_30day,
            '1year' => $certs_1year,
        ];

        // Total counts for cards
        $certificatecounts = [
            '1day'  => array_sum($certificates['1day']),
            '7day'  => array_sum($certificates['7day']),
            '30day' => array_sum($certificates['30day']),
            '1year' => array_sum($certificates['1year'])
        ];


        // --- Top users for each range ---
        $topusers = [
            '1day'  => self::get_top_reward_users_with_levels($todayStart, $tomorrowStart, 10),
            '7day'  => self::get_top_reward_users_with_levels($last7Start, $tomorrowStart, 10),
            '30day' => self::get_top_reward_users_with_levels($last28Start, $tomorrowStart, 10),
            '1year' => self::get_top_reward_users_with_levels($yearStart, $nextYearStart, 10)
        ];

        
        


        return [
            'cards' => [
                
                'rewardpoints' => $rewardcounts,
                'badgesissued' => $badgecounts,
                'certificatesissued' => $certificatecounts,
                'topusers' => $topusers
            ],
        
            'rewardpoints' => $rewardpoints,
            'badges' => $badges,
            'certificates' => $certificates
        ];
    }
    // ✅ Rewaed Return type
    public static function get_reward_dashboard_data_returns() {
        // return new external_single_structure([
        //     'cards' => new external_single_structure([

        //         'rewardpoints' => new external_multiple_structure(new external_value(PARAM_INT, 'Reward points count')),
        //         'badgesissued' => new external_multiple_structure(new external_value(PARAM_INT, 'Badges issued count')),
        //         'certificatesissued' => new external_multiple_structure(new external_value(PARAM_INT, 'Certificates issued count')),
        //         'topusers' => new external_single_structure([
        //             '1day' => new external_multiple_structure(new external_single_structure([
        //                 'userid' => new external_value(PARAM_INT, 'User ID'),
        //                 'fullname' => new external_value(PARAM_RAW, 'User full name'),
        //                 'profileimageurl' => new external_value(PARAM_URL, 'User profile image URL'),
        //                 'points' => new external_value(PARAM_INT, 'Reward points'),
        //                 'level'  => new external_value(PARAM_INT, 'User level')
        //             ])),
        //             '7day' => new external_multiple_structure(new external_single_structure([
        //                 'userid' => new external_value(PARAM_INT, 'User ID'),
        //                 'fullname' => new external_value(PARAM_RAW, 'User full name'),
        //                 'profileimageurl' => new external_value(PARAM_URL, 'User profile image URL'),
        //                 'points' => new external_value(PARAM_INT, 'Reward points'),
        //                 'level'  => new external_value(PARAM_INT, 'User level')
        //             ])),
        //             '30day' => new external_multiple_structure(new external_single_structure([
        //                 'userid' => new external_value(PARAM_INT, 'User ID'),
        //                 'fullname' => new external_value(PARAM_RAW, 'User full name'),
        //                 'profileimageurl' => new external_value(PARAM_URL, 'User profile image URL'),
        //                 'points' => new external_value(PARAM_INT, 'Reward points'),
        //                 'level'  => new external_value(PARAM_INT, 'User level')
        //             ])),
        //             '1year' => new external_multiple_structure(new external_single_structure([
        //                 'userid' => new external_value(PARAM_INT, 'User ID'),
        //                 'fullname' => new external_value(PARAM_RAW, 'User full name'),
        //                 'profileimageurl' => new external_value(PARAM_URL, 'User profile image URL'),
        //                 'points' => new external_value(PARAM_INT, 'Reward points'),
        //                 'level'  => new external_value(PARAM_INT, 'User level')
        //             ]))
        //         ])

        //     ]),

        //     'rewardpoints' => new external_multiple_structure(new external_multiple_structure(
        //         new external_value(PARAM_INT, 'Reward points series')
        //     )),
        //     'badges' => new external_multiple_structure(new external_multiple_structure(
        //         new external_value(PARAM_INT, 'Badges issued series')
        //     )),
        //     'certificates' => new external_multiple_structure(new external_multiple_structure(
        //         new external_value(PARAM_INT, 'Certificates issued series')
        //     )),

        // ]);

        return new external_single_structure([
        'cards' => new external_single_structure([
            'rewardpoints' => new external_single_structure([
                '1day' => new external_value(PARAM_INT, 'Reward points today'),
                '7day' => new external_value(PARAM_INT, 'Reward points 7 days'),
                '30day'=> new external_value(PARAM_INT, 'Reward points 30 days'),
                '1year'=> new external_value(PARAM_INT, 'Reward points 1 year'),
            ]),
            'badgesissued' => new external_single_structure([
                '1day' => new external_value(PARAM_INT, 'Badges issued today'),
                '7day' => new external_value(PARAM_INT, 'Badges issued 7 days'),
                '30day'=> new external_value(PARAM_INT, 'Badges issued 30 days'),
                '1year'=> new external_value(PARAM_INT, 'Badges issued 1 year'),
            ]),
            'certificatesissued' => new external_single_structure([
                '1day' => new external_value(PARAM_INT, 'Certificates issued today'),
                '7day' => new external_value(PARAM_INT, 'Certificates issued 7 days'),
                '30day'=> new external_value(PARAM_INT, 'Certificates issued 30 days'),
                '1year'=> new external_value(PARAM_INT, 'Certificates issued 1 year'),
            ]),
            'topusers' => new external_single_structure([
                '1day' => new external_multiple_structure(new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_RAW, 'User full name'),
                    'points' => new external_value(PARAM_INT, 'Reward points'),
                    'level' => new external_value(PARAM_INT, 'User level'),
                    'color' => new external_value(PARAM_RAW, 'Level color'),
                    'progress' => new external_value(PARAM_INT, 'Progress to next level (%)'),
                    'nextxp' => new external_value(PARAM_INT, 'XP needed for next level')
                ])),
                '7day' => new external_multiple_structure(new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_RAW, 'User full name'),
                    'points' => new external_value(PARAM_INT, 'Reward points'),
                    'level' => new external_value(PARAM_INT, 'User level'),
                    'color' => new external_value(PARAM_RAW, 'Level color'),
                    'progress' => new external_value(PARAM_INT, 'Progress to next level (%)'),
                    'nextxp' => new external_value(PARAM_INT, 'XP needed for next level')
                ])),
                '30day'=> new external_multiple_structure(new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_RAW, 'User full name'),
                    'points' => new external_value(PARAM_INT, 'Reward points'),
                    'level' => new external_value(PARAM_INT, 'User level'),
                    'color' => new external_value(PARAM_RAW, 'Level color'),
                    'progress' => new external_value(PARAM_INT, 'Progress to next level (%)'),
                    'nextxp' => new external_value(PARAM_INT, 'XP needed for next level')
                ])),
                '1year'=> new external_multiple_structure(new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_RAW, 'User full name'),
                    'points' => new external_value(PARAM_INT, 'Reward points'),
                    'level' => new external_value(PARAM_INT, 'User level'),
                    'color' => new external_value(PARAM_RAW, 'Level color'),
                    'progress' => new external_value(PARAM_INT, 'Progress to next level (%)'),
                    'nextxp' => new external_value(PARAM_INT, 'XP needed for next level')
                ])),
            ]),
        ]),
        'rewardpoints' => new external_single_structure([
            '1day' => new external_multiple_structure(new external_value(PARAM_INT, 'Reward series today')),
            '7day' => new external_multiple_structure(new external_value(PARAM_INT, 'Reward series 7d')),
            '30day'=> new external_multiple_structure(new external_value(PARAM_INT, 'Reward series 30d')),
            '1year'=> new external_multiple_structure(new external_value(PARAM_INT, 'Reward series 1y')),
        ]),
        'badges' => new external_single_structure([
            '1day' => new external_multiple_structure(new external_value(PARAM_INT, 'Badges series today')),
            '7day' => new external_multiple_structure(new external_value(PARAM_INT, 'Badges series 7d')),
            '30day'=> new external_multiple_structure(new external_value(PARAM_INT, 'Badges series 30d')),
            '1year'=> new external_multiple_structure(new external_value(PARAM_INT, 'Badges series 1y')),
        ]),
        'certificates' => new external_single_structure([
            '1day' => new external_multiple_structure(new external_value(PARAM_INT, 'Certificates series today')),
            '7day' => new external_multiple_structure(new external_value(PARAM_INT, 'Certificates series 7d')),
            '30day'=> new external_multiple_structure(new external_value(PARAM_INT, 'Certificates series 30d')),
            '1year'=> new external_multiple_structure(new external_value(PARAM_INT, 'Certificates series 1y')),
        ]),
    ]);

    }




    // --- Helper: monthly certificates issued this year ---
    private static function get_certificate_counts_months_current_year(int $yearStart, int $nextYearStart): array {
        global $DB;

          // --- Check if customcert_issues table exists ---
            $dbman = $DB->get_manager();
            if (!$dbman->table_exists('customcert_issues')) {
                return array_fill(0, 12, 0); // fallback: 12 months, all 0
            }
        $sql = "
            SELECT MONTH(FROM_UNIXTIME(ci.timecreated)) AS m,
                COUNT(ci.id) AS total
            FROM {customcert_issues} ci
            WHERE ci.timecreated >= :s
            AND ci.timecreated < :e
            GROUP BY m
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $yearStart, 'e' => $nextYearStart]);

        $series = array_fill(0, 12, 0);
        foreach ($rows as $r) {
            $m = (int)$r->m;
            if ($m >= 1 && $m <= 12) {
                $series[$m - 1] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: binned certificate counts ---
    private static function get_certificate_counts_binned(int $start, int $end, int $bin, int $bins): array {
        global $DB;

          // --- Check if customcert_issues table exists ---
            $dbman = $DB->get_manager();
            if (!$dbman->table_exists('customcert_issues')) {
                return array_fill(0, $bins, 0);
            }
        $sql = "
            SELECT FLOOR((ci.timecreated - $start) / $bin) AS b,
                COUNT(ci.id) AS total
            FROM {customcert_issues} ci
            WHERE ci.timecreated >= $start
            AND ci.timecreated < $end
            GROUP BY b
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $start, 'e' => $end, 'bin' => $bin]);

        $series = array_fill(0, $bins, 0);
        foreach ($rows as $r) {
            $b = (int)$r->b;
            if ($b >= 0 && $b < $bins) {
                $series[$b] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: monthly badges issued this year ---
    private static function get_badge_counts_months_current_year(int $yearStart, int $nextYearStart): array {
        global $DB;
        $sql = "
            SELECT MONTH(FROM_UNIXTIME(bi.dateissued)) AS m,
                COUNT(bi.id) AS total
            FROM {badge_issued} bi
            WHERE bi.dateissued >= $yearStart
            AND bi.dateissued < $nextYearStart
            GROUP BY m
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $yearStart, 'e' => $nextYearStart]);

        $series = array_fill(0, 12, 0);
        foreach ($rows as $r) {
            $m = (int)$r->m;
            if ($m >= 1 && $m <= 12) {
                $series[$m - 1] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: binned badge counts ---
    private static function get_badge_counts_binned(int $start, int $end, int $bin, int $bins): array {
        global $DB;
        $sql = "
            SELECT FLOOR((bi.dateissued - $start) / $bin) AS b,
                COUNT(bi.id) AS total
            FROM {badge_issued} bi
            WHERE bi.dateissued >= $start
            AND bi.dateissued < $end
            GROUP BY b
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $start, 'e' => $end, 'bin' => $bin]);

        $series = array_fill(0, $bins, 0);
        foreach ($rows as $r) {
            $b = (int)$r->b;
            if ($b >= 0 && $b < $bins) {
                $series[$b] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: monthly reward points this year ---
    private static function get_reward_points_months_current_year(int $yearStart, int $nextYearStart): array {
        global $DB;

          // Check if table exists first
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('local_xp_log')) {
            // Table does not exist → return 12 months with 0
            return array_fill(0, 12, 0);
        }
        $sql = "
            SELECT MONTH(FROM_UNIXTIME(l.time)) AS m,
                SUM(l.points) AS total
            FROM {local_xp_log} l
            WHERE l.time >= $yearStart
            AND l.time < $nextYearStart
            GROUP BY m
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $yearStart, 'e' => $nextYearStart]);

        $series = array_fill(0, 12, 0);
        foreach ($rows as $r) {
            $m = (int)$r->m;
            if ($m >= 1 && $m <= 12) {
                $series[$m - 1] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: binned reward points ---
    private static function get_reward_points_binned(int $start, int $end, int $bin, int $bins): array {
        global $DB;

          // Check if table exists
            $dbman = $DB->get_manager();
            if (!$dbman->table_exists('local_xp_log')) {
                return array_fill(0, $bins, 0);
            }
        $sql = "
            SELECT FLOOR((l.time - $start) / $bin) AS b,
                SUM(l.points) AS total
            FROM {local_xp_log} l
            WHERE l.time >= $start
            AND l.time < $end
            GROUP BY b
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $start, 'e' => $end, 'bin' => $bin]);

        $series = array_fill(0, $bins, 0);
        foreach ($rows as $r) {
            $b = (int)$r->b;
            if ($b >= 0 && $b < $bins) {
                $series[$b] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: Get Top 10 users with XP, level & progress ---
    private static function get_top_reward_users_with_levels(int $start, int $end, int $limit = 10): array {
        global $DB;

        // --- Level definitions ---
        $leveldefs = [
            1  => ['threshold' => 0,    'color' => '#00b7ff'], // blue
            2  => ['threshold' => 120,  'color' => '#3fbf2a'], // green
            3  => ['threshold' => 276,  'color' => '#6f42c1'], // purple
            4  => ['threshold' => 479,  'color' => '#f08fcf'], // pink
            5  => ['threshold' => 742,  'color' => '#e83e55'], // red
            6  => ['threshold' => 1085, 'color' => '#ff7a00'], // orange
            7  => ['threshold' => 1531, 'color' => '#ffc107'], // yellow
            8  => ['threshold' => 2110, 'color' => '#1e7b3b'], // dark green
            9  => ['threshold' => 2863, 'color' => '#8a90a9'], // grey/purple
        10  => ['threshold' => 3842, 'color' => '#0d6efd']  // bright blue
        ];

          // --- Check if table exists ---
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('local_xp_log')) {
            return [];
        }

        // --- Fetch top N users ---
        $sql = "
            SELECT u.id, u.firstname, u.lastname, SUM(l.points) AS total
            FROM {local_xp_log} l
            JOIN {user} u ON u.id = l.userid
            WHERE l.time >= :start AND l.time < :end
            GROUP BY u.id, u.firstname, u.lastname
            ORDER BY total DESC
            LIMIT {$limit}
        ";
        $rows = $DB->get_records_sql($sql, ['start' => $start, 'end' => $end]);

        $users = [];
        foreach ($rows as $r) {
            $totalxp = (int) $r->total;

            // --- Determine user level ---
            $level = 1;
            foreach ($leveldefs as $lvl => $def) {
                if ($totalxp >= $def['threshold']) {
                    $level = $lvl;
                }
            }

            // --- Progress to next level ---
            $nextLevel = min($level + 1, max(array_keys($leveldefs)));
            $currentThreshold = $leveldefs[$level]['threshold'];
            $nextThreshold = $leveldefs[$nextLevel]['threshold'] ?? $currentThreshold;

            $progress = $nextThreshold > $currentThreshold
                ? round((($totalxp - $currentThreshold) / ($nextThreshold - $currentThreshold)) * 100)
                : 100;

                
            // --- Add user record ---
            $users[] = [
                'userid'   => $r->id,
                'fullname' => fullname($r),
                'points'   => $totalxp,
                'level'    => $level,
                'color'    => $leveldefs[$level]['color'],
                'progress' => $progress,
                'nextxp'   => max(0, $nextThreshold - $totalxp)
            ];
        }

        return $users;
    }




     // ========================
    // Badge counts
    // ========================
    private static function get_badge_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart) {
            return [
                '1day'  => self::get_binned_counts('{badge_issued}', 'dateissued', $todayStart, $currentHourStart + 3600, 3600, $hoursSoFar),
                '7day'  => self::get_binned_counts('{badge_issued}', 'dateissued', $last7Start, $tomorrowStart, 86400, 7),
                '30day' => self::get_binned_counts('{badge_issued}', 'dateissued', $last28Start, $tomorrowStart, 7*86400, 4),
                '1year' => self::get_monthly_counts('{badge_issued}', 'dateissued', $yearStart, $nextYearStart),
            ];
    }

    // ========================
    // Forum posts
    // ========================
    private static function get_forum_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart) {
        return [
                '1day'  => self::get_binned_counts('{forum_posts}', 'created', $todayStart, $currentHourStart + 3600, 3600, $hoursSoFar),
                '7day'  => self::get_binned_counts('{forum_posts}', 'created', $last7Start, $tomorrowStart, 86400, 7),
                '30day' => self::get_binned_counts('{forum_posts}', 'created', $last28Start, $tomorrowStart, 7*86400, 4),
                '1year' => self::get_monthly_counts('{forum_posts}', 'created', $yearStart, $nextYearStart),
            ];
    }

    // ========================
    // Assignment submissions
    // ========================
    private static function get_assignment_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart) {
            return [
                '1day'  => self::get_binned_counts('{assign_submission}', 'timemodified', $todayStart, $currentHourStart + 3600, 3600, $hoursSoFar),
                '7day'  => self::get_binned_counts('{assign_submission}', 'timemodified', $last7Start, $tomorrowStart, 86400, 7),
                '30day' => self::get_binned_counts('{assign_submission}', 'timemodified', $last28Start, $tomorrowStart, 7*86400, 4),
                '1year' => self::get_monthly_counts('{assign_submission}', 'timemodified', $yearStart, $nextYearStart),
            ];
    }

    // ========================
    // Quiz attempts (attendance)
    // ========================
    private static function get_quizattempt_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart) {
            return [
                '1day'  => self::get_binned_counts('{quiz_attempts}', 'timestart', $todayStart, $currentHourStart + 3600, 3600, $hoursSoFar),
                '7day'  => self::get_binned_counts('{quiz_attempts}', 'timestart', $last7Start, $tomorrowStart, 86400, 7),
                '30day' => self::get_binned_counts('{quiz_attempts}', 'timestart', $last28Start, $tomorrowStart, 7*86400, 4),
                '1year' => self::get_monthly_counts('{quiz_attempts}', 'timestart', $yearStart, $nextYearStart),
            ];
    }

    // ========================
    // Quiz submissions (finished only)
    // ========================
    private static function get_quizsub_counts($todayStart, $currentHourStart, $hoursSoFar, $last7Start, $last28Start, $yearStart, $nextYearStart, $tomorrowStart) {
            return [
                '1day'  => self::get_binned_counts('{quiz_attempts}', 'timefinish', $todayStart, $currentHourStart + 3600, 3600, $hoursSoFar, "t.state = 'finished'"),
                '7day'  => self::get_binned_counts('{quiz_attempts}', 'timefinish', $last7Start, $tomorrowStart, 86400, 7, "t.state = 'finished'"),
                '30day' => self::get_binned_counts('{quiz_attempts}', 'timefinish', $last28Start, $tomorrowStart, 7*86400, 4, "t.state = 'finished'"),
                '1year' => self::get_monthly_counts('{quiz_attempts}', 'timefinish', $yearStart, $nextYearStart, "t.state = 'finished'"),
            ];
    }    
    // --- Helper: Top enrolled categories ---
    private static function get_top_categories_binned( $start, $end, int $limit = 5): array {
        global $DB;

        $sql = "
            SELECT cat.id AS categoryid, cat.name AS categoryname, COUNT(ue.id) AS total
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            JOIN {course_categories} cat ON cat.id = c.category
            WHERE ue.timecreated >= :start
            AND ue.timecreated < :end
        GROUP BY cat.id, cat.name
        ORDER BY total DESC 
              
        ";

        $records = $DB->get_records_sql($sql, ['start' => $start, 'end' => $end], $limit);

        foreach ($records as $r) {
            // Try to fetch category icon if set
            $icon = $DB->get_field('course_categories', 'idnumber', ['id' => $r->categoryid]);

            

            $r->categoryname = format_string($r->categoryname);
        }

        return $records;
    }

    
    // --- Helper: Top enrolled courses ---
    private static function get_top_courses_binned($start,  $end, int $limit = 5): array {
        global $DB;

        $sql = "
            SELECT c.id AS courseid, c.fullname, COUNT(ue.id) AS total
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            WHERE ue.timecreated >= :start
            AND ue.timecreated < :end
            GROUP BY c.id, c.fullname
            ORDER BY total DESC
        ";

        $records = $DB->get_records_sql($sql, ['start' => $start, 'end' => $end], 0, $limit);

        foreach ($records as $r) {
            $course = get_course($r->courseid);

            // Try to fetch real course image
            $image = \core_course\external\course_summary_exporter::get_course_image($course);

            // Fallback: Moodle logo (static path, no $OUTPUT)
            if (empty($image)) {
                $image = $GLOBALS['CFG']->wwwroot . '/pix/moodlelogo.png';
            }

            $r->image = $image;
        }

        return $records;
    }
    // --- Generic helper: monthly counts ---
    private static function get_monthly_counts(string $table, string $timefield, int $yearStart, int $nextYearStart, string $extrawhere = ''): array {
        global $DB;
        $where = $extrawhere ? "AND $extrawhere" : "";
        $sql = "
            SELECT MONTH(FROM_UNIXTIME(t.$timefield)) AS m,
                COUNT(t.id) AS total
            FROM {$table} t
            WHERE t.$timefield >= :s
            AND t.$timefield < :e
            $where
            GROUP BY m
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $yearStart, 'e' => $nextYearStart]);

        $series = array_fill(0, 12, 0);
        foreach ($rows as $r) {
            $m = (int)$r->m;
            if ($m >= 1 && $m <= 12) {
                $series[$m - 1] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Generic helper: binned counts ---
    private static function get_binned_counts(string $table, string $timefield, int $start, int $end, int $bin, int $bins, string $extrawhere = ''): array {
        global $DB;
        $where = $extrawhere ? "AND $extrawhere" : "";
        $sql = "
            SELECT FLOOR((t.$timefield - $start) / $bin) AS b,
                COUNT(t.id) AS total
            FROM {$table} t
            WHERE t.$timefield >= $start
            AND t.$timefield < $end
            $where
            GROUP BY b
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $start, 'e' => $end, 'bin' => $bin]);

        $series = array_fill(0, $bins, 0);
        foreach ($rows as $r) {
            $b = (int)$r->b;
            if ($b >= 0 && $b < $bins) {
                $series[$b] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: Enrolled vs Completed course counts ---
    private static function get_enrolled_vs_completed_counts( $start,  $end): array {
        global $DB;

        // Count enrolled users (distinct user enrolments in time range).
        $sql_enrolled = "
            SELECT COUNT(DISTINCT ue.userid) AS total
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            WHERE ue.timecreated >= :s
            AND ue.timecreated < :e
        ";
        $enrolled = (int)$DB->get_field_sql($sql_enrolled, ['s' => $start, 'e' => $end]);

        // Count completed users (course_completions in time range).
        $sql_completed = "
            SELECT COUNT(DISTINCT cc.userid) AS total
            FROM {course_completions} cc
            WHERE cc.timecompleted IS NOT NULL
            AND cc.timecompleted >= :s
            AND cc.timecompleted < :e
        ";
        $completed = (int)$DB->get_field_sql($sql_completed, ['s' => $start, 'e' => $end]);

        return ['enrolled' => $enrolled, 'completed' => $completed];
    }

    // --- Helper: course counts binned ---
    private static function get_course_counts_binned($start, $end, $binSeconds, $bins): array {
        global $DB;
        $sql = "
            SELECT FLOOR((c.timecreated - $start) / $binSeconds) AS bucket,
                COUNT(c.id) AS total
            FROM {course} c
            WHERE c.timecreated >= :s
            AND c.timecreated < :e
            GROUP BY bucket
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $start, 'e' => $end]);

        $series = array_fill(0, $bins, 0);
        foreach ($rows as $r) {
            $idx = (int)$r->bucket;
            if ($idx >= 0 && $idx < $bins) {
                $series[$idx] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: monthly courses created this year ---
    private static function get_course_counts_months_current_year($yearStart, $nextYearStart): array {
        global $DB;
        $sql = "
            SELECT MONTH(FROM_UNIXTIME(c.timecreated)) AS m,
                COUNT(c.id) AS total
            FROM {course} c
            WHERE c.timecreated >= :s
            AND c.timecreated < :e
            GROUP BY m
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $yearStart, 'e' => $nextYearStart]);

        $series = array_fill(0, 12, 0);
        foreach ($rows as $r) {
            $m = (int)$r->m;
            if ($m >= 1 && $m <= 12) {
                $series[$m - 1] = (int)$r->total;
            }
        }
        return $series;
    }




    // --- Helper for 24h usage logs ---
    // private static function get_usage_last24h($start, $end,  $bin,  $bins): array {
    //     global $DB;
    //     $sql = "
    //         SELECT FLOOR((ul.timestamp - :s) / :bin) AS b,
    //             AVG(ul.cpu_usage) AS cpu,
    //             AVG(ul.memory_usage) AS memory,
    //             AVG(ul.storage_usage) AS storage
    //         FROM {usage_log} ul
    //         WHERE ul.timestamp >= :start
    //         AND ul.timestamp < :end
    //     GROUP BY b
    //     ORDER BY b
    //     ";
    //     $rows = $DB->get_records_sql($sql, ['s' => $start,'start' => $start, 'end' => $end, 'bin' => $bin]);

    //     $cpu = $memory = $storage = array_fill(0, $bins, 0);
    //     foreach ($rows as $r) {
    //         $b = (int)$r->b;
    //         if ($b >= 0 && $b < $bins) {
    //             $cpu[$b] = (float)$r->cpu;
    //             $memory[$b] = (float)$r->memory;
    //             $storage[$b] = (float)$r->storage;
    //         }
    //     }
    //     return [
    //         'cpu' => $cpu,
    //         'memory' => $memory,
    //         'storage' => $storage
    //     ];
    // }

    // --- Helper for 24h usage logs ---
    private static function get_siteusage_last24h($start, $end, $bin, $bins): array {
        global $DB;

        $sql = "
            SELECT FLOOR((ul.timestamp - :start) / :bin) AS b,
                AVG(ul.cpu_usage) AS cpu,
                AVG(ul.memory_usage) AS memory,
                AVG(ul.storage_usage) AS storage
            FROM {usage_log} ul
            WHERE ul.timestamp >= :s
            AND ul.timestamp < :end
            GROUP BY b
            ORDER BY b
        ";

        $rows = $DB->get_records_sql($sql, [
            'start' => $start,
            's' => $start,
            'end'   => $end,
            'bin'   => $bin
        ]);

        $cpu = $memory = $storage = array_fill(0, $bins, 0);

        foreach ($rows as $r) {
            $b = (int)$r->b;
            if ($b >= 0 && $b < $bins) {
                $cpu[$b] = (float)$r->cpu;
                $memory[$b] = (float)$r->memory;
                $storage[$b] = (float)$r->storage;
            }
        }

        return [
            'cpu'     => $cpu,
            'memory'  => $memory,
            'storage' => $storage
        ];
    }


    // --- Helpers ---
    private static function get_login_counts_binned($start, $end, $binSeconds, $bins) {
        global $DB;
        $sql = "
            SELECT FLOOR((timecreated - :start) / :bin) AS bucket,
                   COUNT(DISTINCT CONCAT(userid, '-', FROM_UNIXTIME(timecreated, '%Y-%m-%d'))) AS total
            FROM {logstore_standard_log}
            WHERE action = 'loggedin'
            AND timecreated >= :s AND timecreated < :e
            GROUP BY bucket";
        $rows = $DB->get_records_sql($sql, ['start'=>$start,'s'=>$start,'e'=>$end,'bin'=>$binSeconds]);

        $series = array_fill(0, $bins, 0);
        foreach ($rows as $r) {
            $idx = (int)$r->bucket;
            if ($idx >= 0 && $idx < $bins) $series[$idx] = (int)$r->total;
        }
        return $series;
    }
    // --- Helper: monthly logins this year ---
    private static function get_login_counts_months_current_year($yearStart, $nextYearStart) {
        global $DB;
        $sql = "
            SELECT MONTH(FROM_UNIXTIME(timecreated)) AS m,
                   COUNT(DISTINCT CONCAT(userid, '-', DATE(FROM_UNIXTIME(timecreated)))) AS total
            FROM {logstore_standard_log}
            WHERE action = 'loggedin'
            AND timecreated >= :s AND timecreated < :e
            GROUP BY m";
        $rows = $DB->get_records_sql($sql, ['s'=>$yearStart,'e'=>$nextYearStart]);

        $series = array_fill(0, 12, 0);
        foreach ($rows as $r) {
            $m = (int)$r->m;
            if ($m >= 1 && $m <= 12) $series[$m-1] = (int)$r->total;
        }
        return $series;
    }
    // --- Helper: enrol counts binned ---
    private static function get_enrol_counts_binned($start, $end, $binSeconds, $bins) {
        global $DB;
        $sql = "
            SELECT FLOOR((ue.timecreated - :start) / :bin) AS bucket,
                   COUNT(ue.id) AS total
            FROM {user_enrolments} ue
            WHERE ue.timecreated >= :s AND ue.timecreated < :e
            GROUP BY bucket";
        $rows = $DB->get_records_sql($sql, ['start'=>$start,'s'=>$start,'e'=>$end,'bin'=>$binSeconds]);

        $series = array_fill(0, $bins, 0);
        foreach ($rows as $r) {
            $idx = (int)$r->bucket;
            if ($idx >= 0 && $idx < $bins) $series[$idx] = (int)$r->total;
        }
        return $series;
    }
    // --- Helper: monthly enrolments this year ---
    private static function get_enrol_counts_months_current_year($yearStart, $nextYearStart) {
        global $DB;
        $sql = "
            SELECT MONTH(FROM_UNIXTIME(ue.timecreated)) AS m,
                   COUNT(ue.id) AS total
            FROM {user_enrolments} ue
            WHERE ue.timecreated >= :s AND ue.timecreated < :e
            GROUP BY m";
        $rows = $DB->get_records_sql($sql, ['s'=>$yearStart,'e'=>$nextYearStart]);

        $series = array_fill(0, 12, 0);
        foreach ($rows as $r) {
            $m = (int)$r->m;
            if ($m >= 1 && $m <= 12) $series[$m-1] = (int)$r->total;
        }
        return $series;
    }



  
    // --- Helper: Web vs Mobile login counts ---
    private static function get_web_mobile_counts($start,  $end): array {
        global $DB;
        $sql = "
            SELECT origin, COUNT(DISTINCT userid) as total
            FROM {logstore_standard_log}
            WHERE action = 'loggedin'
            AND timecreated >= :s
            AND timecreated < :e
            GROUP BY origin
        ";
        $rows = $DB->get_records_sql($sql, ['s' => $start, 'e' => $end]);

        $web = 0; $mobile = 0;
        foreach ($rows as $r) {
            if ($r->origin === 'web') {
                $web = (int)$r->total;
            } else if ($r->origin === 'ws') {
                $mobile = (int)$r->total;
            }
        }
        return ['web' => $web, 'mobile' => $mobile];
    }

    // --- Helper: registered users counts binned ---
    private static function get_registered_counts_binned($start, $end, $binSeconds,  $bins): array {
        global $DB;

        // Use placeholders to avoid SQL injection
        $sql = "
            SELECT FLOOR((u.timecreated - :start) / :bin) AS bucket,
                COUNT(u.id) AS total
            FROM {user} u
            WHERE u.timecreated >= :s
            AND u.timecreated < :end
            GROUP BY bucket
        ";

        $rows = $DB->get_records_sql($sql, [
            'start' => $start,
            's' => $start,
            'end'   => $end,
            'bin'   => $binSeconds
        ]);

        $series = array_fill(0, $bins, 0);
        foreach ($rows as $r) {
            $idx = (int)$r->bucket;
            if ($idx >= 0 && $idx < $bins) {
                $series[$idx] = (int)$r->total;
            }
        }
        return $series;
    }

    // --- Helper: monthly registered users this year ---
    private static function get_registered_counts_months_current_year($yearStart, $nextYearStart) {
        global $DB;

      
        $sql = "
            SELECT MONTH(FROM_UNIXTIME(u.timecreated)) AS m,
                COUNT(u.id) AS total
            FROM {user} u
            WHERE u.timecreated >= $yearStart
            AND u.timecreated < $nextYearStart
            GROUP BY m
            ORDER BY m
        ";

       
        $rows = $DB->get_records_sql($sql, ['start' => $yearStart, 'end' => $nextYearStart]);

      

        $series = array_fill(0, 12, 0);
        foreach ($rows as $r) {
            $m = (int)$r->m;
            if ($m >= 1 && $m <= 12) {
                $series[$m - 1] = (int)$r->total;
            }
        }
        return $series;
    }



}
