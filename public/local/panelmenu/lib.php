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
 * Library functions for the local_panelmenu plugin.
 *
 * @package   local_panelmenu
 * @category  admin
 * @author    Rashid
 * @copyright  2025 Rashid
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_http_headers;

/**
 * Renders the panel menu link in the Moodle navbar.
 *
 * This function adds a custom panel menu icon/link to the Moodle
 * primary navigation bar. It uses strings from the plugin's language
 * file for accessibility and localization.
 *
 * @return string|null HTML for the panel menu navbar link, or null if not applicable.
 */
function local_panelmenu_render_navbar_output(): ?string
{
    global $OUTPUT,$CFG;

    // Check plan from config.php.

    // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
    //     return null; // stop here, no settings added
    // }
    // Check if enabled.
    if (!get_config('local_panelmenu', 'enable')) {
        return null;
    }
    require_once(__DIR__ . '/classes/helper.php');
    $categories = \local_panelmenu\helper::get_categories_with_subs();

    $content = new stdClass();
    $content->categories = $categories;

    return \html_writer::tag(
        'a',
        \html_writer::tag('i', '', [
            'class' => 'fa  fa-big fa-gear',
            'style' => 'margin-right:6px;'
        ]) . get_string('panelmenutitle', 'local_panelmenu'),
        [
            'href' => '#',
            'class' => 'custom-panel-trigger custom-panel-link nav-link',
            'title' => get_string('openpanel', 'local_panelmenu'),
            'role' => 'button',
        ]
    );
}



// function render_custom_panel_menu(): string {
//     global $USER, $OUTPUT, $DB,$PAGE,$COURSE;


//     //get course context
//     $courseid=$COURSE->id;
//     $coursecontext = \context_course::instance($courseid);



//     $isadmin = is_siteadmin($USER);

//     // Get colors from settings.
//     $bgcolor   = get_config('local_panelmenu', 'bgcolor');
//     $textcolor = get_config('local_panelmenu', 'textcolor');

//     $context = [
//         'isadmin'   => $isadmin,
//         'bgcolor'   => $bgcolor,
//         'textcolor' => $textcolor,
//     ];

//     if ($isadmin) {
//         // Admin menu URLs
//         $context += [
//             'createcourseurl'  => new moodle_url('/course/edit.php'),
//             'categoryurl'      => new moodle_url('/course/management.php'),
//             'createuserurl'    => new moodle_url('/user/editadvanced.php', ['id' => -1]),
//             'createcohorturl'  => new moodle_url('/cohort/index.php'),
//         ];

//         // Get last accessed course from logs
//         $lastaccess = $DB->get_record_sql("
//             SELECT c.id, c.fullname
//             FROM {logstore_standard_log} l
//             JOIN {course} c ON c.id = l.courseid
//             WHERE l.userid = :userid
//               AND l.courseid != 1 -- exclude site home
//               AND l.courseid > 0
//             ORDER BY l.timecreated DESC
//             LIMIT 1
//         ", ['userid' => $USER->id]);

//         if ($lastaccess) {
//             $context['lastcourse'] = [
//                 'id'   => $lastaccess->id,
//                 'name' => format_string($lastaccess->fullname),
//                 'url'  => new moodle_url('/course/view.php', ['id' => $lastaccess->id]),
//             ];

//             // Check if the user is a teacher in that course
//             $context['isteacherincourse'] = is_enrolled(
//                 $coursecontext,
//                 $USER,
//                 'moodle/course:update'
//             );

//             if ($context['isteacherincourse']) {
//                 $context['course_links'] = [
//                     'settings'     => new moodle_url('/course/edit.php', ['id' => $courseid]),
//                     'participants' => new moodle_url('/user/index.php', ['id' => $courseid]),
//                     'grades'       => new moodle_url('/grade/report/index.php', ['id' => $courseid]),
//                     'activities'   => new moodle_url('/course/resources.php', ['id' => $courseid]),
//                     'more'         => new moodle_url('/report/log/index.php', ['id' => $courseid]),
//                 ];
//             }
//         }

//     } else {
//         // Non-admin menu URLs
//         $context += [
//             'singlecoursesurl' => new moodle_url('/course/index.php'),
//             'programsurl'      => new moodle_url('/programs/index.php'),
//             'materialsurl'     => new moodle_url('/materials/index.php'),
//             'topicurl'         => new moodle_url('/browse/topic.php'),
//             'departmenturl'    => new moodle_url('/browse/department.php'),
//             'providerurl'      => new moodle_url('/browse/provider.php'),
//             'profileurl'       => new moodle_url('/user/profile.php', ['id' => $USER->id]),
//             'gradesurl'        => new moodle_url('/grade/report/overview/index.php', ['id' => $USER->id]),
//             'calendarurl'      => new moodle_url('/calendar/view.php'),
//             'reportsurl'       => new moodle_url('/report/overview/index.php'),
//         ];
//     }

//    return $OUTPUT->render_from_template('local_panelmenu/custom_panel', $context);
// }


function render_custom_panel_menu_notused(): string
{
    global $USER, $OUTPUT, $DB, $PAGE, $COURSE;

    // Get colors from settings
    $bgcolor   = get_config('local_panelmenu', 'bgcolor');
    $textcolor = get_config('local_panelmenu', 'textcolor');

    // Get course context
    $courseid = $COURSE->id;
    $coursecontext = \context_course::instance($courseid);

    // Base context
    $context = [
        'isadmin'           => $isadmin,
        'bgcolor'           => $bgcolor,
        'textcolor'         => $textcolor,
        'isteacherincourse' => is_enrolled($coursecontext, $USER, 'moodle/course:update'),
    ];

    $isadmin = is_siteadmin($USER);

    // Below menu if only for admin to create courses, users, cohorts etc.
    if ($isadmin) {
        $context['admin_links'] += [
            'createcourseurl'  => new moodle_url('/course/edit.php'),
            'categoryurl'      => new moodle_url('/course/management.php'),
            'createuserurl'    => new moodle_url('/user/editadvanced.php', ['id' => -1]),
            'createcohorturl'  => new moodle_url('/cohort/index.php'),
        ];
    }

    $isteacherincourse = false;
    // Below menu is for admin and teacher with in the course.
    if (has_capability('moodle/course:update', $coursecontext, $USER->id) || $isadmin) {
        $isteacherincourse = true;
        $context['course_links'] += [
            'settings'     => new moodle_url('/course/edit.php', ['id' => $courseid]),
            'participants' => new moodle_url('/user/index.php', ['id' => $courseid]),
            'grades'       => new moodle_url('/grade/report/index.php', ['id' => $courseid]),
            'activities'   => new moodle_url('/course/resources.php', ['id' => $courseid]),
            'more'         => new moodle_url('/report/log/index.php', ['id' => $courseid]),
        ];
    }

    // Below menu is for NON - ADMIN
    if (!$isadmin && !$isteacherincourse) {
        $context['student_teacher_links'] += [
            'profileurl'       => new moodle_url('/user/profile.php', ['id' => $USER->id]),
            'gradesurl'        => new moodle_url('/grade/report/overview/index.php', ['id' => $USER->id]),
            'calendarurl'      => new moodle_url('/calendar/view.php'),
            'reportsurl'       => new moodle_url('/report/overview/index.php'),
        ];
    }

    // Below menu is for all users and common links.
    $context['common_links'] += [
        'singlecoursesurl' => new moodle_url('/course/index.php'),
        'programsurl'      => new moodle_url('/programs/index.php'),
        'materialsurl'     => new moodle_url('/materials/index.php'),
        'topicurl'         => new moodle_url('/browse/topic.php'),
        'departmenturl'    => new moodle_url('/browse/department.php'),
        'providerurl'      => new moodle_url('/browse/provider.php'),
    ];

    // Get last accessed course
    $lastaccess = local_panelmenu_get_last_accessed_course();
    if ($lastaccess) {
        $context['lastcourse'] = [
            'id'   => $lastaccess->id,
            'name' => format_string($lastaccess->fullname),
            'url'  => new moodle_url('/course/view.php', ['id' => $lastaccess->id]),
        ];
    }





    return $OUTPUT->render_from_template('local_panelmenu/custom_panel', $context);
}

function render_custom_panel_menu(): string
{
    global $USER, $OUTPUT, $COURSE;

    // Get colors from settings
    $bgcolor   = get_config('local_panelmenu', 'bgcolor');
    $textcolor = get_config('local_panelmenu', 'textcolor');
    $headingcolor = get_config('local_panelmenu', 'headingcolor');
    $hovercolor   = get_config('local_panelmenu', 'hovercolor');
    $hoverbgcolor   = get_config('local_panelmenu', 'hoverbgcolor');

    // Get course context
    $courseid = $COURSE->id ?? 1;
    $coursecontext = \context_course::instance($courseid);

    // Role flags
    $isadmin = is_siteadmin($USER);
    $isteacherincourse = has_capability('moodle/course:update', $coursecontext, $USER->id);

    // Base context
    $context = [
        'isadmin'               => $isadmin,
        'isteacherincourse'     => $isteacherincourse,
        'bgcolor'               => $bgcolor,
        'textcolor'             => $textcolor,
        'headingcolor'         => $headingcolor,
        'hovercolor'            => $hovercolor,
        'hoverbgcolor'            => $hoverbgcolor,
        'users_links'           => [],
        'course_links'          => [],
        'branding_links'        => [],
        'server_links'          => [],
        'reports_links'         => [],
        'coursesetting_links'   => [],
        
    ];
  


    //users Llinks
    if ($isadmin) {
        $context['users_links'] = [
            [
                'url' => (new moodle_url('/admin/user.php'))->out(),
                'label' => get_string('browselistusers', 'local_panelmenu'),
                'description' => get_string('browselistusers_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/user/editadvanced.php', ['id' => $USER->id]))->out(),
                'label' => get_string('addnewusers', 'local_panelmenu'),
                'description' => get_string('addnewusers_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/user/user_bulk.php'))->out(),
                'label' => get_string('bulkuseractions', 'local_panelmenu'),
                'description' => get_string('bulkuseractions_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/tool/uploaduser/index.php'))->out(),
                'label' => get_string('uploadusers', 'local_panelmenu'),
                'description' => get_string('uploadusers_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/user/profile/index.php'))->out(),
                'label' => get_string('userprofilefields', 'local_panelmenu'),
                'description' => get_string('userprofilefields_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/cohort/index.php'))->out(),
                'label' => get_string('cohorts', 'local_panelmenu'),
                'description' => get_string('cohorts_desc', 'local_panelmenu')
            ],
        ];
    }

    //Course Links
    if ($isadmin) {
        $context['course_links'] = [
            [
                'url' => (new moodle_url('/course/management.php'))->out(),
                'label' => get_string('managecourseandcategory', 'local_panelmenu'),
                'description' => get_string('managecourseandcategory_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/course/editcategory.php?parent=0'))->out(),
                'label' => get_string('addacategory', 'local_panelmenu'),
                'description' => get_string('addacategory_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/course/edit.php?category=0'))->out(),
                'label' => get_string('addnewcourse', 'local_panelmenu'),
                'description' => get_string('addnewcourse_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/tool/uploadcourse/index.php'))->out(),
                'label' => get_string('uploadcourse', 'local_panelmenu'),
                'description' => get_string('uploadcourse_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/settings.php?section=coursesettings'))->out(),
                'label' => get_string('coursedefaultsettings', 'local_panelmenu'),
                'description' => get_string('coursedefaultsettings_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/course/customfield.php'))->out(),
                'label' => get_string('coursecustomfields', 'local_panelmenu'),
                'description' => get_string('coursecustomfields_desc', 'local_panelmenu')
            ],
        ];
    }

    //Branding Links
    if ($isadmin) {
        $context['branding_links'] = [
            [
                'url' => (new moodle_url('/admin/settings.php?section=logos'))->out(),
                'label' => get_string('logos', 'local_panelmenu'),
                'description' => get_string('logos_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/settings.php?section=coursecolors'))->out(),
                'label' => get_string('coursecardcolours', 'local_panelmenu'),
                'description' => get_string('coursecardcolours_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/settings.php?section=navigation'))->out(),
                'label' => get_string('navigation', 'local_panelmenu'),
                'description' => get_string('navigation_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/settings.php?section=themesettingsadvanced'))->out(),
                'label' => get_string('advancethemesettings', 'local_panelmenu'),
                'description' => get_string('advancethemesettings_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/settings.php?section=themesettingedzsaas'))->out(),
                'label' => get_string('theme', 'local_panelmenu'),
                'description' => get_string('theme_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/my/indexsys.php'))->out(),
                'label' => get_string('default_dashboard', 'local_panelmenu'),
                'description' => get_string('default_dashboard_desc', 'local_panelmenu')
            ],
        ];
    }

    //Server Links
    if ($isadmin) {
        $context['server_links'] = [
            [
                'url' => (new moodle_url('/admin/settings.php?section=supportcontact'))->out(),
                'label' => get_string('supportcontact', 'local_panelmenu'),
                'description' => get_string('supportcontact_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/settings.php?section=maintenancemode'))->out(),
                'label' => get_string('maintennancemode', 'local_panelmenu'),
                'description' => get_string('maintenancemode_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/environment.php'))->out(),
                'label' => get_string('environment', 'local_panelmenu'),
                'description' => get_string('environment_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/tool/task/scheduledtasks.php'))->out(),
                'label' => get_string('scheduledtasks', 'local_panelmenu'),
                'description' => get_string('scheduledtasks_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/settings.php?section=outgoingmailconfig'))->out(),
                'label' => get_string('outgoingmailconfig', 'local_panelmenu'),
                'description' => get_string('outgoingmailconfig_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/admin/purgecaches.php'))->out(),
                'label' => get_string('purgecaches', 'local_panelmenu'),
                'description' => get_string('purgecaches_desc', 'local_panelmenu')
            ],
        ];
    }
    //Reports Links
    if ($isadmin) {
        $context['reports_links'] = [
            [
                'url' => (new moodle_url('/report/log/index.php?id=0'))->out(),
                'label' => get_string('logs', 'local_panelmenu'),
                'description' => get_string('logs_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/reportbuilder/index.php'))->out(),
                'label' => get_string('customreports', 'local_panelmenu'),
                'description' => get_string('customreports_desc', 'local_panelmenu')
            ],
            [
                'url' => (new moodle_url('/local/edzdashboardview'))->out(),
               'label' => get_string('analytics', 'local_panelmenu'),
                'description' => get_string('analytics_desc', 'local_panelmenu'),
            ],
            [
                'url' => (new moodle_url('/local/edzworkplacerpt/index.php'))->out(),
               'label' => get_string('managerreport', 'local_panelmenu'),
                'description' => get_string('managerreport_desc', 'local_panelmenu'),
            ],
            [
                'url' => (new moodle_url('/report/lmsace_reports/index.php'))->out(),
               'label' => get_string('adminreport', 'local_panelmenu'),
                'description' => get_string('adminreport_desc', 'local_panelmenu'),
            ],
	    [
                'url' => (new moodle_url('/local/edztrainingcalendar/report.php'))->out(),
               'label' => get_string('trainingcalendarreport', 'local_panelmenu'),
                'description' => get_string('trainingcalendarreport_desc', 'local_panelmenu'),
            ],
        ];
    }

    // Admin or Teacher in course
    if($courseid !=1){
        if ($isadmin || $isteacherincourse) {
            $context['coursesetting_links'] = [
                [
                    'url' => (new moodle_url('/course/edit.php', ['id' => $courseid]))->out(),
                    'label' => get_string('coursesettings', 'local_panelmenu'),
                    'description' => get_string('coursesettings_desc', 'local_panelmenu')
                ],
                [
                    'url' => (new moodle_url('/user/index.php', ['id' => $courseid]))->out(),
                    'label' => get_string('courseparticipants', 'local_panelmenu'),
                    'description' => get_string('courseparticipants_desc', 'local_panelmenu')
                ],
                [
                    'url' => (new moodle_url('/grade/report/index.php', ['id' => $courseid]))->out(),
                    'label' => get_string('coursegrades', 'local_panelmenu'),
                    'description' => get_string('coursegrades_desc', 'local_panelmenu')
                ],
                [
                    'url' => (new moodle_url('/report/view.php', ['courseid' => $courseid]))->out(),
                    'label' => get_string('coursereports', 'local_panelmenu'),
                    'description' => get_string('coursereports_desc', 'local_panelmenu')
                ],
                [
                    'url' => (new moodle_url('/course/completion.php', ['id' => $courseid]))->out(),
                    'label' => get_string('coursecompletion', 'local_panelmenu'),
                    'description' => get_string('coursecompletion_desc', 'local_panelmenu')
                ],
            ];
        }   
    }


   
    // Last accessed course
    $lastaccess = local_panelmenu_get_last_accessed_course();
    if ($lastaccess) {
        $context['lastcourse'] = [
            'name' => format_string($lastaccess->fullname),
            'url'  => (new moodle_url('/course/view.php', ['id' => $lastaccess->id]))->out(),
        ];
    }

    $context['has_course_links'] = !empty($context['course_links']);
    $context['has_admin_links'] = !empty($context['admin_links']);
    $context['has_common_links'] = !empty($context['common_links']);
    $context['has_student_teacher_links'] = !empty($context['student_teacher_links']);

    //suman changes
    $context['has_users_links'] = !empty($context['users_links']);
    $context['has_course_links'] = !empty($context['course_links']);
    $context['has_branding_links'] = !empty($context['branding_links']);
    $context['has_server_links'] = !empty($context['server_links']);
    $context['has_reports_links'] = !empty($context['reports_links']);
    $context['has_coursesetting_links'] = !empty($context['coursesetting_links']);
    return $OUTPUT->render_from_template('local_panelmenu/custom_panel', $context);
}



/**
 * Get the last accessed course for a given user.
 *
 * @param int|null $userid The Moodle user ID. Defaults to current user.
 * @return stdClass|null Returns course object with id and fullname or null.
 */
function local_panelmenu_get_last_accessed_course(?int $userid = null): ?stdClass
{
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $lastaccess = $DB->get_record_sql("
        SELECT c.id, c.fullname
        FROM {logstore_standard_log} l
        JOIN {course} c ON c.id = l.courseid
        WHERE l.userid = :userid
          AND l.courseid != 1 -- exclude site home
          AND l.courseid > 0
        ORDER BY l.timecreated DESC
        LIMIT 1
    ", ['userid' => $userid]);

    return $lastaccess ?: null;
}

