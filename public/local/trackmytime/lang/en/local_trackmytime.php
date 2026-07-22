<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Language strings for local_trackmytime.
 *
 * @package    local_trackmytime
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Track My Time';

// Performance page.
$string['performance_title']    = 'Performance & Achievements';
$string['study_heatmap']        = 'Study activity (26 weeks)';
$string['grade_overview']       = 'Grade overview';
$string['badges_earned']        = 'Badges earned';
$string['certificates_wallet']  = 'Certificates';
$string['no_data_yet']          = 'No data recorded yet. Keep learning to see your stats here.';
$string['no_badges']            = 'No badges earned yet';
$string['no_certificates']      = 'No certificates yet';
$string['minutes_studied']      = 'Minutes studied';

// Time analytics.
$string['time_analytics']  = 'Time analytics';
$string['stat_today']      = 'Today';
$string['stat_thisweek']   = 'This week';
$string['stat_lastweek']   = 'Last week';
$string['stat_thismonth']  = 'This month';
$string['stat_thisquarter']= 'This quarter';
$string['stat_total']      = 'All-time';
$string['weekly_activity'] = 'Weekly activity (last 12 weeks)';
$string['monthly_activity']= 'Monthly activity (last 6 months)';
$string['quarterly_activity']= 'Quarterly activity (last 8 quarters)';
$string['study_time']      = 'Study time';
$string['chart_period']    = 'Chart period';
$string['period_weekly']   = 'Weekly · last 12 weeks';
$string['period_monthly']  = 'Monthly · last 6 months';
$string['period_quarterly']= 'Quarterly · last 8 quarters';
$string['no_activity_yet'] = 'No study time recorded yet. Open a course activity and your time will start appearing here.';
$string['recent_activity'] = 'Recent activity';
$string['coursetime_title']= 'Time by course';
$string['download_pdf']    = 'Download as PDF';
$string['pdf_generated']   = 'Generated {$a}';
$string['xp_points']       = 'XP';
$string['xp_level']        = 'Level';
$string['heatmap_less']         = 'Less';
$string['heatmap_more']         = 'More';

// Settings.
$string['enabletracking']        = 'Enable time tracking';
$string['enabletracking_desc']   = 'Record time-on-task while learners view course activities. When enabled, a lightweight heartbeat runs on activity pages and logs seconds spent. Disable to stop all tracking.';
$string['showstudyheatmap']      = 'Show study heatmap';
$string['showstudyheatmap_desc'] = 'Show the 26-week study activity heatmap on the performance page.';

// Privacy.
$string['privacy:metadata']                     = 'The Track My Time plugin stores time-on-task session logs for each learner.';
$string['privacy:metadata:trackmytime:userid']    = 'The ID of the user whose study time is recorded.';
$string['privacy:metadata:trackmytime:courseid']  = 'The course in which the time was spent.';
$string['privacy:metadata:trackmytime:cmid']      = 'The course module being studied.';
$string['privacy:metadata:trackmytime:timespent'] = 'The number of seconds spent in the session.';
