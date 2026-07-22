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
 * English strings for local_reportpanel.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Reports Hub';
$string['panelheading'] = 'Reports Hub';
$string['panelintro'] = 'Open a report. You see only what your role allows.';
$string['open'] = 'Open';
$string['nocards'] = 'No reports are available to you right now.';
$string['mode_full'] = 'All users';
$string['mode_self'] = 'My data';

// Capabilities.
$string['reportpanel:view'] = 'View the report panel and own reports';
$string['reportpanel:viewall'] = 'View all-user reports (full mode)';

// Cards.
$string['card_leaderboard'] = 'Leaderboard';
$string['carddesc_leaderboard'] = 'Points, levels and the class ranking ladder.';
$string['card_sitereports'] = 'Site reports';
$string['carddesc_sitereports'] = 'Site-wide analytics and activity reports.';
$string['card_logreport'] = 'Log report';
$string['carddesc_logreport'] = 'Detailed event logs across the site.';
$string['card_teamreports'] = 'Team reports';
$string['carddesc_teamreports'] = 'Compliance and progress for your team.';
$string['card_quizreports'] = 'Quiz reports';
$string['carddesc_quizreports'] = 'Attempts, highest and lowest grades per quiz.';
$string['card_certreports'] = 'Certificate reports';
$string['carddesc_certreports'] = 'Certificates issued, per course.';
$string['card_consolidated'] = 'Consolidated user report';
$string['carddesc_consolidated'] = 'One report: completion, grades, certificates, badges.';
$string['card_courseconsolidated'] = 'Consolidated course report';
$string['carddesc_courseconsolidated'] = 'Pick a course: every learner\'s completion, grade & status, with charts.';
$string['card_badges'] = 'Badges & achievements';
$string['carddesc_badges'] = 'Badges earned across courses.';

// Settings.
$string['settings_general'] = 'General';
$string['settings_heading'] = 'Panel heading';
$string['settings_heading_desc'] = 'Title shown at the top of the report panel.';
$string['settings_cards'] = 'Report cards';
$string['settings_cards_desc'] = 'Enable each external card and set the URL it opens. Use a relative path (e.g. /badges/mybadges.php) or a full URL.';
$string['settings_url'] = '{$a} URL';
$string['settings_url_desc'] = 'Where this card links to.';
$string['settings_enablecard_desc'] = 'Show this card in the panel.';

// Common report UI.
$string['backtopanel'] = 'Back to Reports Hub';
$string['selectcategory'] = 'Course category';
$string['selectcourse'] = 'Course';
$string['choosecategory'] = 'Choose a category…';
$string['choosecourse'] = 'Choose a course…';
$string['nocoursesincategory'] = 'No courses in this category.';
$string['course'] = 'Course';
$string['name'] = 'Name';
$string['email'] = 'Email';
$string['download'] = 'Download';
$string['downloadpdf'] = 'Download PDF';
$string['downloadcsv'] = 'Download CSV';
$string['generatedon'] = 'Generated';
$string['printedby'] = 'Printed by';
$string['nodata'] = 'No data to display.';

// Quiz report.
$string['quiz_title'] = 'Quiz reports';
$string['quiz_intro_full'] = 'Choose a category and course to list its quizzes.';
$string['quiz_intro_self'] = 'Your quiz attempts and grades.';
$string['quiz_name'] = 'Quiz';
$string['quiz_attempts'] = 'Participants';
$string['quiz_myattempts'] = 'Attempts';
$string['quiz_highest'] = 'Highest grade';
$string['quiz_lowest'] = 'Lowest grade';
$string['quiz_average'] = 'Average grade';
$string['quiz_mygrade'] = 'My grade';
$string['quiz_lastattempt'] = 'Last attempt';
$string['quiz_noquizzes'] = 'This course has no quizzes.';
$string['quiz_noattempts'] = 'You have no quiz attempts yet.';

// Certificate report.
$string['cert_title'] = 'Certificate reports';
$string['cert_intro_full'] = 'Choose a category and course to list its certificates.';
$string['cert_intro_self'] = 'Certificates you have been issued.';
$string['cert_name'] = 'Certificate';
$string['cert_issued'] = 'Issued (count)';
$string['cert_issuedate'] = 'Issue date';
$string['cert_nocerts'] = 'This course has no certificates.';
$string['cert_none'] = 'You have not been issued any certificates yet.';
$string['cert_notinstalled'] = 'The certificate activity (mod_customcert) is not installed on this site.';

// Consolidated report.
$string['cons_title'] = 'Consolidated user report';
$string['cons_intro_full'] = 'Search for a user to build their consolidated report.';
$string['cons_intro_self'] = 'Your consolidated learning report.';
$string['cons_searchuser'] = 'Search user (name or email)';
$string['cons_searchplaceholder'] = 'Type at least 2 characters…';
$string['cons_build'] = 'Build report';
$string['cons_completionpct'] = 'Completion %';
$string['cons_completiondate'] = 'Completion date';
$string['cons_grade'] = 'Grade';
$string['cons_certdate'] = 'Certificate date';
$string['cons_badges'] = 'Badge(s)';
$string['cons_nocourses'] = 'This user is not enrolled in any courses.';
$string['cons_pickuser'] = 'Choose a user above to build the report.';
$string['cons_reportfor'] = 'Report for {$a}';
$string['pdf_title'] = 'Consolidated learning report';

// Consolidated course report.
$string['coursecons_title'] = 'Consolidated course report';
$string['coursecons_intro'] = 'Choose a category and course to see every enrolled learner\'s progress.';
$string['coursecons_activities'] = 'Activities';
$string['coursecons_status'] = 'Status';
$string['coursecons_noenrol'] = 'No users are enrolled in this course.';
$string['status_completed'] = 'Completed';
$string['status_inprogress'] = 'In progress';
$string['status_notstarted'] = 'Not started';

// Summary stat cards.
$string['stat_enrolled'] = 'Enrolled';
$string['stat_completionrate'] = 'Completion rate';
$string['stat_avggrade'] = 'Average grade';

// Charts.
$string['chart_completionstatus'] = 'Completion status';
$string['chart_gradedist'] = 'Grade distribution';
$string['chart_gradepercourse'] = 'Grade per course';
$string['chart_users'] = 'Users';
$string['chart_learners'] = 'Learners';

// Table filters (course report).
$string['filter_name'] = 'Filter by name';
$string['filter_email'] = 'Filter by email';
$string['filter_status'] = 'Filter by status';
$string['filter_typeph'] = 'Type to filter…';
$string['filter_allstatuses'] = 'All statuses';
$string['filter_nomatch'] = 'No learners match the current filters.';

// Table pagination.
$string['page_perpage'] = 'Per page';
$string['page_all'] = 'All';
$string['page_prev'] = 'Prev';
$string['page_next'] = 'Next';
$string['page_info'] = 'Showing {from}–{to} of {total}';
$string['page_pageof'] = 'Page {page} of {pages}';

// User search.
$string['cons_noresults'] = 'No matching users found.';

// Errors.
$string['error_nouser'] = 'No user selected.';
$string['error_nocourse'] = 'No course selected.';
$string['error_nopermission'] = 'You do not have permission to view this report.';

// Privacy.
$string['privacy:metadata'] = 'The Reports Hub plugin does not store any personal data. It displays data owned by other parts of the site and streams downloadable exports without saving them.';

// Engagement report (Domain A — time & engagement).
$string['card_engagement'] = 'Time & engagement';
$string['carddesc_engagement'] = 'Time on task, activity trends and a weekly usage heatmap.';
$string['settings_engagement'] = 'Engagement report';
$string['settings_activedays'] = 'Active-user window (days)';
$string['settings_activedays_desc'] = 'A learner counts as "active" if they have any tracked time in this many days. Default 7.';
$string['filter_range'] = 'Date range';
$string['filter_from'] = 'From';
$string['filter_to'] = 'To';
$string['filter_apply'] = 'Apply';
$string['exportcsv'] = 'Download CSV';
$string['backtohub'] = 'Back to hub';
$string['col_course'] = 'Course';
$string['col_timeontask'] = 'Time on task';
$string['col_seconds'] = 'Seconds';
$string['deletedcourse'] = 'Deleted course';
$string['chart_minutes'] = 'Minutes';
$string['kpi_totaltime'] = 'Total time on task';
$string['kpi_activeusers'] = 'Active users (last {$a} days)';
$string['kpi_inactiveusers'] = 'Inactive users';
$string['kpi_avgactive'] = 'Avg per active user';
$string['eng_trend_title'] = 'Engagement trend';
$string['eng_notrend'] = 'No tracked time in this period yet.';
$string['eng_topcourses_title'] = 'Top courses by time';
$string['eng_nocourses'] = 'No course time recorded in this period.';
$string['eng_heatmap_title'] = 'Weekly usage heatmap';
$string['eng_heatmap_help'] = 'Darker cells = more time on task. Rows are days (Mon–Sun); columns are hours (00–23).';
$string['eng_noheatmap'] = 'Not enough data to draw the heatmap yet.';
$string['range_last7'] = 'Last 7 days';
$string['range_last30'] = 'Last 30 days';
$string['range_last90'] = 'Last 90 days';
$string['range_thismonth'] = 'This month';
$string['range_lastmonth'] = 'Last month';
$string['range_custom'] = 'Custom range…';
$string['engagement_fullmodeonly'] = 'The Time & engagement report shows site-wide analytics and is available to managers only.';
$string['engagement_gotomine'] = 'View my own performance';
$string['engagement_nodata'] = 'Time tracking is not available. Install and enable local_trackmytime to populate this report.';

// Shared (Domain D + B).
$string['managersonly'] = 'This report shows site-wide analytics and is available to managers only.';
$string['filter_category'] = 'Category';
$string['filter_topn'] = 'Show top';
$string['col_name'] = 'Name';
$string['col_value'] = 'Value';
$string['col_metric'] = 'Metric';
$string['deleteduser'] = 'Deleted user';

// Ranking reports (Domain D).
$string['card_rankings'] = 'Rankings';
$string['carddesc_rankings'] = 'Top courses and most-active learners by enrolment, completion and time.';
$string['rank_allcategories'] = 'All categories';
$string['col_enrolments'] = 'Enrolments';
$string['col_completions'] = 'Completions';
$string['rank_enrol_title'] = 'Top courses by enrolment';
$string['rank_completion_title'] = 'Top courses by completions';
$string['rank_time_title'] = 'Top courses by time on task';
$string['rank_learners_title'] = 'Most-active learners';
$string['rank_nodata'] = 'No data for this filter yet.';

// Site-wide overview (Domain B).
$string['card_overview'] = 'Site overview';
$string['carddesc_overview'] = 'Site-wide KPIs, trends and health at a glance.';
$string['ov_totalusers'] = 'Total users';
$string['ov_activeusers'] = 'Active users (last {$a} days)';
$string['ov_newregs'] = 'New registrations';
$string['ov_enrolments'] = 'Enrolments';
$string['ov_coursecomp'] = 'Course completions';
$string['ov_activitycomp'] = 'Activity completions';
$string['ov_certs'] = 'Certificates issued';
$string['ov_timeontask'] = 'Time on task';
$string['ov_completions'] = 'Completions';
$string['ov_trend_regs'] = 'New registrations';
$string['ov_trend_completions'] = 'Course completions';
$string['ov_trend_time'] = 'Time on task (minutes/day)';
$string['ov_notrend'] = 'No data in this period.';
$string['ov_registration_title'] = 'Registration breakdown';
$string['ov_reg_confirmed'] = 'Confirmed';
$string['ov_reg_unconfirmed'] = 'Unconfirmed';
$string['ov_reg_suspended'] = 'Suspended';
$string['ov_reg_deleted'] = 'Deleted';
$string['ov_health_title'] = 'Enrolment health';
$string['ov_health_nocourses'] = 'Users in no courses';
$string['ov_health_multicourse'] = 'Users in more than one course';
$string['ov_health_notaccessed'] = 'Not accessed in {$a} days';
$string['ov_topcourses_title'] = 'Top courses (completions)';
$string['ov_allrankings'] = 'All rankings';
$string['ov_notopcourses'] = 'No completions in this period.';
$string['ov_timeunavailable'] = 'Time-on-task metrics are unavailable (install local_trackmytime to populate them).';

// Quick-win UI polish.
$string['delta_new'] = 'new';
$string['asof'] = 'As of {$a}';
$string['group_manager'] = 'Manager reports';
$string['group_myreports'] = 'My reports';

// Activity coverage (Domain E).
$string['act_title'] = 'Activity coverage';
$string['act_activity'] = 'Activity';
$string['act_type'] = 'Type';
$string['act_completion'] = 'Completed';
$string['act_overdue'] = 'Overdue';
$string['act_details'] = 'Details';
$string['act_assignsummary'] = '{$a->submitted} submitted · {$a->graded} graded · {$a->late} late';
