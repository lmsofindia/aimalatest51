<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * English language strings.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Faculty Dashboard';
$string['dashboardtitle'] = 'Faculty Dashboard';

// Capabilities.
$string['edzfaculty:view'] = 'View own faculty dashboard';
$string['edzfaculty:viewall'] = 'View any teacher\'s faculty dashboard';
$string['edzfaculty:managesettings'] = 'Manage faculty dashboard settings';

// Access notice.
$string['noaccess'] = 'The Faculty Dashboard is for teaching staff. You are not teaching any courses.';
$string['viewingas'] = 'Viewing the dashboard of {$a} (read-only).';

// Admin / manager overview.
$string['overviewtitle'] = 'Faculty Overview';
$string['allfaculty'] = 'All faculty';
$string['backtoall'] = 'All faculty';
$string['col_faculty'] = 'Faculty';
$string['col_tograde'] = 'To grade';
$string['col_unanswered'] = 'Unanswered';
$string['col_atrisk'] = 'At-risk';
$string['sum_faculty'] = 'Teaching faculty';
$string['sum_tograde'] = 'Total to grade';
$string['sum_atrisk'] = 'Total at-risk';
$string['nofaculty'] = 'No teaching faculty found.';

// Settings — overview access.
$string['overviewaccess'] = 'Faculty Overview access';
$string['overviewaccess_desc'] = 'Who sees the admin "All Faculty" overview and can open another teacher\'s dashboard. Choose "Site admins only" when faculty also hold the Manager role site-wide, so they land on their own teacher dashboard.';
$string['oa_admin'] = 'Site admins only';
$string['oa_manager'] = 'Admins and managers';

// Settings — terminology.
$string['terminology'] = 'Terminology';
$string['terminology_desc'] = 'Wording used across the dashboard. Academic suits a college (Students / Sections / Semesters — AIMA). Corporate suits executive programs (Learners / Batches / Programs — GAIL).';
$string['term_academic'] = 'Academic (Students / Sections)';
$string['term_corporate'] = 'Corporate (Learners / Batches)';

// Settings — at-risk.
$string['atriskhdr'] = 'At-risk detection';
$string['minattendance'] = 'Minimum attendance %';
$string['minattendance_desc'] = 'Attendance below this percentage contributes to a student\'s risk score.';
$string['maxloginstale'] = 'Max days since last access';
$string['maxloginstale_desc'] = 'No course access for more than this many days contributes to the risk score.';
$string['minscore'] = 'Minimum average score %';
$string['minscore_desc'] = 'Average score below this percentage contributes to the risk score.';
$string['scoredrop'] = 'Score-drop % threshold';
$string['scoredrop_desc'] = 'A downward score trend larger than this contributes to the risk score.';
$string['maxmisseddeadlines'] = 'Missed-deadline threshold';
$string['maxmisseddeadlines_desc'] = 'This many missed deadlines or more contributes to the risk score.';

// Settings — cache.
$string['cachehdr'] = 'Cache & analytics';
$string['refreshthrottle'] = 'Manual refresh throttle (minutes)';
$string['refreshthrottle_desc'] = 'A teacher may rebuild their own analytics no more than once per this many minutes.';
$string['engagementdays'] = 'Engagement window (days)';
$string['engagementdays_desc'] = 'Number of days counted as "this month" for engagement metrics.';
$string['showai'] = 'Show AI features';
$string['showai_desc'] = 'Show AI-assist badges, at-risk reasons and insight text. (Phase 2 wires the models to axis-ai.)';

// Settings — attendance source.
$string['attendhdr'] = 'Attendance source';
$string['attendancesource'] = 'Attendance data source';
$string['attendancesource_desc'] = 'Where student attendance % comes from. Auto prefers mod_attendance and falls back to Zoom participant reports.';
$string['attendsrc_auto'] = 'Auto (mod_attendance, else Zoom)';
$string['attendsrc_attendance'] = 'mod_attendance only';
$string['attendsrc_zoom'] = 'Zoom participant reports only';
$string['attendsrc_off'] = 'Off (ignore attendance)';
$string['zoomminminutes'] = 'Zoom minimum minutes present';
$string['zoomminminutes_desc'] = 'A student must be present in a Zoom class for at least this many minutes to be counted attended.';

// Nudge messaging.
$string['messageprovider:nudge'] = 'At-risk student nudge from a teacher';
$string['nudgesubject'] = 'A message from your instructor';
$string['nudgedefault'] = 'Hi {$a->student}, I noticed you may have fallen behind in {$a->course}. Is everything okay? Please reach out if you need any help catching up. — {$a->teacher}';
$string['nudgesent'] = 'Nudge sent.';
$string['nudgefailed'] = 'The nudge could not be sent.';
$string['nudgeplaceholder'] = 'Edit the message, or send as-is…';
$string['nudgesendbtn'] = 'Send nudge';
$string['nudgetitle'] = 'Send a nudge to {$a}';
$string['cancel'] = 'Cancel';

// Terminology variants — resolved by helper\terminology.
$string['student_academic'] = 'Student';
$string['student_corporate'] = 'Learner';
$string['students_academic'] = 'Students';
$string['students_corporate'] = 'Learners';
$string['section_academic'] = 'Section';
$string['section_corporate'] = 'Batch';
$string['sections_academic'] = 'Sections';
$string['sections_corporate'] = 'Batches';
$string['course_academic'] = 'Course';
$string['course_corporate'] = 'Program';
$string['courses_academic'] = 'Courses';
$string['courses_corporate'] = 'Programs';

// Zone 0 — Course Focus.
$string['coursefocus'] = 'Course Focus';
$string['allcourses'] = 'All courses';
$string['ctx_all'] = 'Showing totals across all {$a} of your courses';
$string['stat_live'] = 'Live classes upcoming';
$string['stat_assign'] = 'Assignments to review';
$string['stat_disc'] = 'Discussion new posts';
$string['stat_quiz'] = 'Quizzes to grade';
$string['qa_all'] = 'Quick actions — all courses';
$string['qa_course'] = 'Quick actions — {$a}';
$string['qa_review'] = 'Review Assignments';
$string['qa_attendance'] = 'Check Attendance';
$string['qa_discuss'] = 'Post in Discussion';
$string['qa_upload'] = 'Upload Course Content';
$string['qa_schedule'] = 'Schedule Live Class';
$string['qa_assess'] = 'Create Assessment';

// Zone 1 — triage.
$string['needsattention'] = 'Needs Your Attention';
$string['kpi_tograde'] = 'To Grade';
$string['kpi_unanswered'] = 'Unanswered Questions';
$string['kpi_atrisk'] = 'At-Risk';
$string['kpi_nextclass'] = 'Next Live Class';
$string['tab_grading'] = 'Grading';
$string['tab_discussions'] = 'Discussions';
$string['tab_atrisk'] = 'At-Risk';
$string['tab_overdue'] = 'Overdue';
$string['waiting'] = 'oldest waiting {$a}';
$string['submissions'] = '{$a} submissions';
$string['grade'] = 'Grade';
$string['reply'] = 'Reply';
$string['view'] = 'View';
$string['nogradingdue'] = 'Nothing waiting to grade. You\'re all caught up.';
$string['nounanswered'] = 'No unanswered student questions.';
$string['noatrisk'] = 'No students currently flagged at risk.';
$string['nooverdue'] = 'Nothing overdue.';

// Timeline.
$string['todayupcoming'] = 'Today & Upcoming';
$string['join'] = 'Join';
$string['tag_exam'] = 'Exam';
$string['tag_assessment'] = 'Assessment';
$string['tag_liveclass'] = 'Live class';
$string['notimeline'] = 'No classes or exams scheduled.';

// Zone 2 — courses.
$string['mycourses'] = 'My {$a}';
$string['contentdelivered'] = '{$a}% content delivered';
$string['avgscore'] = 'Avg score';
$string['atrisklabel'] = 'At-risk';

// Zone 3 — insight.
$string['sectionperformance'] = 'Section performance';
$string['engagementtitle'] = 'Engagement — this month';
$string['eng_active'] = 'Active students';
$string['eng_views'] = 'Content views';
$string['eng_assess'] = 'Assessments taken';
$string['eng_score'] = 'Avg score';
$string['vslastmonth'] = 'vs last month';
$string['flat'] = 'flat';
$string['aiinsight'] = 'AI insight';
$string['refresh'] = 'Refresh data';
$string['refreshthrottled'] = 'Analytics were refreshed recently. Try again later.';
$string['refreshqueued'] = 'Refresh queued — updated figures will appear shortly.';
$string['lastupdated'] = 'Data updated {$a} ago';

// Student 360.
$string['student360'] = 'Student overview';
$string['whyflagged'] = 'Why flagged';
$string['recentactivity'] = 'Recent activity';
$string['sendnudge'] = 'Send nudge';
$string['fullrecord'] = 'Full record';
$string['lastlogin'] = 'Last login';
$string['misseddeadlines'] = 'Missed deadlines';
$string['forumposts'] = 'Forum posts';
$string['scoretrend'] = 'Score trend';
$string['attendance'] = 'Attendance';

// Risk reasons.
$string['reason_nologin'] = 'No course access for {$a} days';
$string['reason_lowattendance'] = 'Attendance below {$a}%';
$string['reason_scoredrop'] = 'Score dropped {$a}% recently';
$string['reason_lowscore'] = 'Average score below {$a}%';
$string['reason_misseddeadlines'] = '{$a} missed deadlines';

// Risk levels.
$string['risk_ok'] = 'On track';
$string['risk_watch'] = 'Watch';
$string['risk_critical'] = 'Critical';

// Task / privacy.
$string['task_refresh_cache'] = 'Rebuild faculty dashboard analytics cache';
$string['privacy:metadata:cache'] = 'Derived per-student analytics shown to their teachers on the faculty dashboard.';
$string['privacy:metadata:cache:userid'] = 'The student the metrics describe.';
$string['privacy:metadata:cache:courseid'] = 'The course the metrics are for.';
$string['privacy:metadata:cache:riskscore'] = 'The computed at-risk score.';
$string['privacy:metadata:cache:avgscore'] = 'The average score used in analytics.';
