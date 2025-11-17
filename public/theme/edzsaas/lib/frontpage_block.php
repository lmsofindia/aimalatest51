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
 * Theme edzsaas block lib.
 *
 * @package   theme_edzsaas
 * @copyright 2025 ThemesEdzsaas  - https://edzlms.com/
 * @author    ThemesEDzsaas - Edzlms Team
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Frontpage block01.
 * @return url
 */
function theme_edzsaas_frontpageblock01() {
    $theme = theme_config::load('edzsaas');
    $templatecontext['block01enabled'] = $theme->settings->block01enabled;
    if (empty($templatecontext['block01enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block01caption'] = format_text($theme->settings->block01caption);
    $templatecontext['block01button'] = format_string($theme->settings->block01button);
    $templatecontext['block01buttonlink'] = $theme->settings->block01buttonlink;
    $templatecontext['block01color'] = $theme->settings->block01color;

    return $templatecontext;
}
/**
 * Frontpage block02.
 * @return url
 */
function theme_edzsaas_frontpageblock02() {
    global $OUTPUT;

    $theme = theme_config::load('edzsaas');
    $templatecontext['block02enabled'] = $theme->settings->block02enabled;
    if (empty($templatecontext['block02enabled'])) {
        return $templatecontext;
    }
    $count = $theme->settings->block02count;
    for ($i = 1, $j = 0; $i <= $count; $i++, $j++) {
        $block02img = "sliderimageblock02img{$i}";
        $block02icon = "block02icon{$i}";
        $block02title = "block02title{$i}";
        $block02caption = "block02caption{$i}";
        $block02button = "block02button{$i}";
        $block02buttonlink = "block02buttonlink{$i}";

        $str = $theme->settings->$block02icon;
        $newstr = substr(strstr($str, ":"), 1, (strlen(strstr($str, ":"))) - 1);
        $image = $theme->setting_file_url($block02img, $block02img);
        if (empty($image)) {
            $image = $OUTPUT->image_url('edzsaas/block02/'.$i, 'theme');
        }
        $templatecontext['block02'][$j]['icon'] = $newstr;
        $templatecontext['block02'][$j]['image'] = $image;
        $templatecontext['block02'][$j]['title'] = format_string($theme->settings->$block02title);
        $templatecontext['block02'][$j]['caption'] = format_text($theme->settings->$block02caption);
        $templatecontext['block02'][$j]['button'] = format_string($theme->settings->$block02button);
        $templatecontext['block02'][$j]['buttonurl'] = format_string($theme->settings->$block02buttonlink);
    }
    if ($count == 2) {
        $templatecontext['count'] = "col-lg-6";
    } else if ($count == 3) {
        $templatecontext['count'] = "col-lg-4";
    } else {
        $templatecontext['count'] = "col-lg-3";
    }
    return $templatecontext;
}
/**
 * Frontpage block03.
 * @return url
 */
function theme_edzsaas_frontpageblock03() {
    global $OUTPUT;
    $theme = theme_config::load('edzsaas');
    $templatecontext['block03enabled'] = $theme->settings->block03enabled;
    if (empty($templatecontext['block03enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block03design'.$theme->settings->block03design] = $theme->settings->block03design;
    $templatecontext['block03header'] = format_string($theme->settings->block03header);
    $count = 6;
    if ($theme->settings->block03design == 2) {
        $count = 6;
    }
    for ($i = 1, $j = 0; $i <= $count; $i++, $j++) {
        $block03icon = "block03icon{$i}";
        $block03title = "block03title{$i}";
        $block03caption = "block03caption{$i}";
        $block03link = "block03link{$i}";

        $str = $theme->settings->$block03icon;
        $newstr = substr(strstr($str, ":"), 1, (strlen(strstr($str, ":"))) - 1);
        $templatecontext['block03'][$j]['icon'] = $newstr;
        $templatecontext['block03'][$j]['title'] = format_string($theme->settings->$block03title);
        $templatecontext['block03'][$j]['caption'] = format_text($theme->settings->$block03caption);
        $templatecontext['block03'][$j]['link'] = format_string($theme->settings->$block03link);
    }
    return $templatecontext;
}
/**
 * Frontpage block04.
 * @return url
 */
function theme_edzsaas_frontpageblock04() {
    global $OUTPUT;
    $theme = theme_config::load('edzsaas');
    $templatecontext['block04enabled'] = $theme->settings->block04enabled;
    if (empty($templatecontext['block04enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block04design'.$theme->settings->block04design] = $theme->settings->block04design;
    $templatecontext['block04header'] = format_string($theme->settings->block04header);
    $templatecontext['block04button'] = format_string($theme->settings->block04button);
    $templatecontext['block04buttonlink'] = $theme->settings->block04buttonlink;
    $count = 8;
    if ($theme->settings->block04design == 1) {
        $count = 8;
    }
    for ($i = 1, $j = 0; $i <= $count; $i++, $j++) {
        $block04img = "sliderimageblock04img{$i}";
        $block04title = "block04title{$i}";
        $block04caption = "block04caption{$i}";
        $block04link = "block04link{$i}";
        $image = $theme->setting_file_url($block04img, $block04img);
        if (empty($image)) {
            $image = $OUTPUT->image_url('edzsaas/block04/'.$i, 'theme');
        }
        if ($i == 1) {
            $templatecontext['block04'][$j]['active'] = "1";
        } else {
            $templatecontext['block04'][$j]['active'] = "";
        }
        $templatecontext['block04'][$j]['image'] = $image;
        $templatecontext['block04'][$j]['title'] = format_string($theme->settings->$block04title);
        $templatecontext['block04'][$j]['caption'] = format_string($theme->settings->$block04caption);
        $templatecontext['block04'][$j]['link'] = format_string($theme->settings->$block04link);

    }
    for ($i = 1, $j = 0; $i <= 4; $i++, $j++) {
        $block04img = "sliderimageblock04img{$i}";
        $block04title = "block04title{$i}";
        $block04caption = "block04caption{$i}";
        $block04link = "block04link{$i}";
        $image = $theme->setting_file_url($block04img, $block04img);
        if (empty($image)) {
            $image = $OUTPUT->image_url('edzsaas/block04/'.$i, 'theme');
        }
        $templatecontext['block04_1'][$j]['image'] = $image;
        $templatecontext['block04_1'][$j]['title'] = format_string($theme->settings->$block04title);
        $templatecontext['block04_1'][$j]['caption'] = format_string($theme->settings->$block04caption);
        $templatecontext['block04_1'][$j]['link'] = format_string($theme->settings->$block04link);
    }
    for ($i = 5, $j = 0; $i <= 8; $i++, $j++) {
        $block04img = "sliderimageblock04img{$i}";
        $block04title = "block04title{$i}";
        $block04caption = "block04caption{$i}";
        $block04link = "block04link{$i}";
        $image = $theme->setting_file_url($block04img, $block04img);
        if (empty($image)) {
            $image = $OUTPUT->image_url('edzsaas/block04/'.$i, 'theme');
        }
        $templatecontext['block04_2'][$j]['image'] = $image;
        $templatecontext['block04_2'][$j]['title'] = format_string($theme->settings->$block04title);
        $templatecontext['block04_2'][$j]['caption'] = format_string($theme->settings->$block04caption);
        $templatecontext['block04_2'][$j]['link'] = format_string($theme->settings->$block04link);
    }
    return $templatecontext;
}
/**
 * Frontpage block05.
 * @return url
 */
function theme_edzsaas_frontpageblock05() {
    global $OUTPUT;

    $theme = theme_config::load('edzsaas');
    $templatecontext['block05enabled'] = $theme->settings->block05enabled;
    if (empty($templatecontext['block05enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block05design'.$theme->settings->block05design] = $theme->settings->block05design;
    $templatecontext['block05header'] = format_string($theme->settings->block05header);
    $image = $theme->setting_file_url('sliderimageblock05img', 'sliderimageblock05img');
    if (empty($image)) {
        $image = $OUTPUT->image_url('edzsaas/block05/1', 'theme');
    }
    $templatecontext['block05image'] = $image;
    for ($i = 1, $j = 0; $i <= 3; $i++, $j++) {
        $block05icon = "block05icon{$i}";
        $block05title = "block05title{$i}";
        $block05caption = "block05caption{$i}";
        $block05link = "block05link{$i}";
        if ( !empty($theme->settings->$block05title) && !empty($theme->settings->$block05caption) ) {
            $str = $theme->settings->$block05icon;
            $newstr = substr(strstr($str, ":"), 1, (strlen(strstr($str, ":"))) - 1 );
            $templatecontext['block05'][$j]['icon'] = $newstr;
            $templatecontext['block05'][$j]['title'] = format_string($theme->settings->$block05title);
            $templatecontext['block05'][$j]['caption'] = format_string($theme->settings->$block05caption);
            $templatecontext['block05'][$j]['link'] = format_string($theme->settings->$block05link);
        }
    }
    return $templatecontext;
}
/**
 * Frontpage block06.
 * @return url
 */
function theme_edzsaas_frontpageblock06() {
    global $OUTPUT;

    $theme = theme_config::load('edzsaas');
    $templatecontext['block06enabled'] = $theme->settings->block06enabled;
    if (empty($templatecontext['block06enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block06color'] = $theme->settings->block06color;
    $templatecontext['block06design'.$theme->settings->block06design] = $theme->settings->block06design;
    $templatecontext['block06header'] = format_string($theme->settings->block06header);
    $templatecontext['block06caption'] = format_text($theme->settings->block06caption);
    $templatecontext['block06button'] = format_string($theme->settings->block06button);
    $templatecontext['block06buttonlink'] = $theme->settings->block06buttonlink;
    $image = $theme->setting_file_url('sliderimageblock06img', 'sliderimageblock06img');
    if (empty($image)) {
        $image = $OUTPUT->image_url('edzsaas/block06/1', 'theme');
    }
    $templatecontext['block06image'] = $image;
    return $templatecontext;
}
/**
 * Frontpage block07.
 * @return url
 */
function theme_edzsaas_frontpageblock07() {
    GLOBAL  $CFG, $DB, $OUTPUT;
    $theme = theme_config::load('edzsaas');
    $templatecontext['block07enabled'] = $theme->settings->block07enabled;
    $templatecontext['block07teacherenabled'] = $theme->settings->block07teacherenabled;
    if (empty($templatecontext['block07enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block07design'.$theme->settings->block07design] = $theme->settings->block07design;
    $templatecontext['block07header'] = format_string($theme->settings->block07header);
    $templatecontext['block07button'] = format_string($theme->settings->block07button);
    $templatecontext['block07buttonlink'] = $theme->settings->block07buttonlink;
    $templatecontext['block07fullname'] = 0;
    $templatecontext['block07shortname'] = 0;
    if ($theme->settings->block07title == 'shortname') {
        $templatecontext['block07shortname'] = 1;
    } else {
        $templatecontext['block07fullname'] = 1;
    }
    require_once( $CFG->libdir . '/filelib.php' );
    $count = $theme->settings->block07count;
    // SQL Server.
    if ($CFG->dbtype === 'sqlsrv') {
        $sql = "SELECT TOP ". $count ." c.id, c.fullname, c.shortname, c.summary, c.timemodified, c.category, c.visible";
    } else {
        $sql = "SELECT  c.id, c.fullname, c.shortname, c.summary, c.timemodified, c.category, c.visible";
    }
    $sql = $sql." FROM {course} c";
    $sql = $sql." WHERE c.visible = 1";
    $sql = $sql." ORDER BY c.timemodified DESC";
    if ($CFG->dbtype != 'sqlsrv') {
        $sql = $sql." LIMIT ". $count;
    }
    $courses = $DB->get_records_sql($sql);
    foreach ($courses as $id => $course) {
        $category = $DB->get_record('course_categories', ['id' => $course->category]);
        if (!empty($category)) {
            $course->categoryName = $category->name;
            $course->categoryId = $category->id;
            $allcourses[$id] = $course;
        }
    };
    $j = 0;
    $sql = "SELECT  en.id, en.courseid, en.cost, en.currency";
    $sql = $sql." FROM {enrol} en";
    $sql = $sql." WHERE en.courseid = :courseid and en.status = 0 and en.cost != 'NULL'";
    $templatecontext['block07priceshow'] = $theme->settings->block07priceshow;
    if (!empty($allcourses)) {
        foreach ($allcourses as $id => $course) {
            $context = context_course::instance($id);
            $summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php',
            $context->id, 'course', 'summary', false);
            $templatecontext['block07'][$j]['fullname'] = format_string($course->fullname);
            $templatecontext['block07'][$j]['shortname'] = format_string($course->shortname);
            $templatecontext['block07'][$j]['summary'] = format_text($summary);
            $templatecontext['block07'][$j]['update'] = gmdate("M d,Y", $course->timemodified);
            $templatecontext['block07'][$j]['categoryName'] = format_string($course->categoryName);
            $templatecontext['block07'][$j]['courselink'] = "course/view.php?id=".$id;
            $templatecontext['block07'][$j]['categorylink'] = "course/index.php?categoryid=".$course->categoryId;
            $templatecontext['block07'][$j]['imgurl'] = edzsaas_get_course_image($id);
            $enrol = $DB->get_records_sql($sql, ['courseid' => $id]);
            if (empty($enrol)) {
                $templatecontext['block07'][$j]['currency'] = get_string('block07enrol', 'theme_edzsaas');
            } else {
                foreach ($enrol as $enrols) {
                    $templatecontext['block07'][$j]['cost'] = $enrols->cost;
                    $templatecontext['block07'][$j]['currency'] = $enrols->currency;
                };
            }
            $context = context_course::instance($id);
            $role = $theme->settings->block07studentrole;
            $students = get_role_users($role, $context);
            $templatecontext['block07'][$j]['studentscount'] = count($students);
            $role = $theme->settings->block07teacherrole;
            $teachers = get_role_users($role, $context);
            if (!empty($theme->settings->block07teacherenabled)) {
                foreach ($teachers as $id => $teacher) {
                    $templatecontext['block07'][$j]['teachername'] = format_string(fullname($teacher));
                    $teacher->imagealt = "teacher";
                    $templatecontext['block07'][$j]['userpicture'] = $OUTPUT->user_picture($teacher);
                }
            }
            $j++;
            if ($j > $count) {
                break;
            }
        };
    }
    return $templatecontext;
}
/**
 * Frontpage block08.
 * @return url
 */
function theme_edzsaas_frontpageblock08() {
    GLOBAL $CFG, $DB, $OUTPUT, $PAGE;
    $theme = theme_config::load('edzsaas');
    $templatecontext['block08enabled'] = $theme->settings->block08enabled;
    if (empty($templatecontext['block08enabled'])) {
        return $templatecontext;
    }
    $count = $theme->settings->block08count;
    $templatecontext['block08design'.$theme->settings->block08design] = $theme->settings->block08design;
    $templatecontext['block08header'] = format_string($theme->settings->block08header);
    $templatecontext['block08caption'] = format_text($theme->settings->block08caption);
    $teacherrole = $theme->settings->block08showrole;
    if ($CFG->dbtype === 'sqlsrv') {
        $sql = "SELECT TOP ". $count ." ra.userid, ra.roleid";
    } else {
        $sql = "SELECT  ra.userid, ra.roleid";
    }
    $sql = $sql." FROM {role_assignments} ra";
    $sql = $sql." JOIN {context} ctx on ra.contextid = ctx.id";
    $sql = $sql." WHERE ra.roleid = :roleid";
    $sql = $sql." GROUP by ra.userid, ra.roleid";
    if ($CFG->dbtype != 'sqlsrv') {
        $sql = $sql." LIMIT ". $count;
    }
    // And ctx.contextlevel = '50'?
    if (!empty($theme->settings->block08total)) {
        $courses = get_courses('all', 'c.timemodified DESC');
    }
    $roleassignments = $DB->get_records_sql($sql, ['roleid' => $teacherrole]);
    if (!empty($roleassignments)) {
        $j = 0;
        $coursecount = 0;
        $studentscount = 0;
        foreach ($roleassignments as $roleassignment) {
            $templatecontext['block08'][$j]['showdescription'] = $theme->settings->block08description;
            if ($user = $DB->get_record('user', ['id' => $roleassignment->userid])) {
                $personcontext = context_user::instance($user->id);
                $description = file_rewrite_pluginfile_urls($user->description, 'pluginfile.php',
                $personcontext->id, 'user', 'profile', false);
                $user->imagealt = "teacher";
                $templatecontext['block08'][$j]['teachername'] = format_string($user->firstname." ".$user->lastname);
                $templatecontext['block08'][$j]['description'] = format_text($description);
                $templatecontext['block08'][$j]['userpicture'] =
                    $OUTPUT->user_picture($user, ['class' => '', 'size' => '250']);
                $templatecontext['block08'][$j]['userURL'] =
                    new moodle_url('/user/profile.php', ['id' => $roleassignment->userid ]);
                $userpicture = new user_picture($user);
                $userpicture->size = 512;
                $url = $userpicture->get_url($PAGE)->out(false);
                $templatecontext['block08'][$j]['userpictureURL'] = $url;
            }
            $templatecontext['block08total'] = $theme->settings->block08total;
            if (!empty($templatecontext['block08total'])) {
                foreach ($courses as $id => $course) {
                    $context = context_course::instance($id);
                    $teachers = get_role_users($teacherrole, $context);
                    foreach ($teachers as $id => $teacher) {
                        if ($teacher->username == $user->username) {
                            $coursecount++;
                            $role = $DB->get_field('role', 'id', ['id' => $theme->settings->block08studentrole]);
                            $students = get_role_users($role, $context);
                            $studentscount = $studentscount + count($students);
                        }
                    }
                }
            }
            $templatecontext['block08'][$j]['coursecount'] = $coursecount;
            $templatecontext['block08'][$j]['studentscount'] = $studentscount;
            $coursecount = 0;
            $studentscount = 0;
            $j = $j + 1;
            if ($j == $theme->settings->block08count) {
                break;
            }
        }
    }
    return $templatecontext;
}
/**
 * Frontpage block09.
 * @return url
 */
function theme_edzsaas_frontpageblock09() {
    GLOBAL $CFG, $DB;
    require_once($CFG->libdir.'/formslib.php');
    $theme = theme_config::load('edzsaas');
    $templatecontext['block09enabled'] = $theme->settings->block09enabled;
    if (empty($templatecontext['block09enabled'])) {
         return $templatecontext;
    }
    $count = $theme->settings->block09count;
    $templatecontext['block09design'.$theme->settings->block09design] = $theme->settings->block09design;
    $templatecontext['block09boxshadow'] = $theme->settings->block09boxshadow;
    $templatecontext['block09header'] = format_string($theme->settings->block09header);
    $templatecontext['block09caption'] = format_string($theme->settings->block09caption);
    $templatecontext['block09background'] = $theme->settings->block09background;
    if ($CFG->dbtype === 'sqlsrv') {
        $sql = "SELECT TOP ". $count ." id, name, parent, coursecount, visible, depth, path";
    } else {
        $sql = "SELECT id, name, parent, coursecount, visible, depth, path";
    }
    $sql = $sql." FROM {course_categories}";
    $sql = $sql." WHERE coursecount > 0 and visible = 1";
    if (!empty($theme->settings->block09ctgid)) {
        $sql = $sql." and ". $theme->settings->block09ctgid;
    }
    $sql = $sql." ORDER BY coursecount DESC";
    if ($CFG->dbtype != 'sqlsrv') {
        $sql = $sql." LIMIT ". $count;
    }
    $categorys = $DB->get_records_sql($sql, []);
    if (!empty($categorys)) {
        $j = 0;
        foreach ($categorys as $category) {
            $templatecontext['block09'][$j]['catagoryname'] = format_string($category->name);
            $templatecontext['block09'][$j]['coursecount'] = $category->coursecount;
            $templatecontext['block09'][$j]['catagoryURL'] = new moodle_url('/course/index.php?categoryid='. $category->id);
            $templatecontext['block09'][$j]['bgcolor'] = "";
            if ($theme->settings->block09background == '1') {
                $templatecontext['block09'][$j]['bgcolor'] = theme_edzsaas_random_color();
            }
            $templatecontext['block09'][$j]['imgurl'] = "";
            if ($theme->settings->block09background == '2') {
                $courses = get_courses($category->id);
                if (!empty($courses)) {
                    foreach ($courses as $course) {
                        $imgurl = edzsaas_get_course_image($course->id);
                        if (!empty($imgurl)) {
                            $templatecontext['block09'][$j]['imgurl'] = $imgurl;
                            break;
                        }
                    }
                }
            }
            $j++;
            if ($j == $theme->settings->block09count) {
                break;
            }
        }
    }
    return $templatecontext;
}
/**
 * Frontpage block10.
 * @return url
 */
function theme_edzsaas_frontpageblock10() {
    global $OUTPUT;

    $theme = theme_config::load('edzsaas');
    $templatecontext['block10enabled'] = $theme->settings->block10enabled;
    if (empty($templatecontext['block10enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block10design'.$theme->settings->block10design] = $theme->settings->block10design;
    $templatecontext['block10header'] = format_string($theme->settings->block10header);
    $count = $theme->settings->block10count;
    for ($i = 1, $j = 0; $i <= $count; $i++, $j++) {
        $block10img = "sliderimageblock10img{$i}";
        $block10name = "block10name{$i}";
        $block10job = "block10job{$i}";
        $block10caption = "block10caption{$i}";
        $block10link = "block10link{$i}";
        if ($i == 1) {
            $templatecontext['block10'][$j]['active'] = "1";
        } else {
            $templatecontext['block10'][$j]['active'] = "";
        }
        $image = $theme->setting_file_url($block10img, $block10img);
        if (empty($image)) {
            $image = $OUTPUT->image_url('edzsaas/block10/'.$i, 'theme');
        }
        $templatecontext['block10'][$j]['block10image'] = $image;
        $templatecontext['block10'][$j]['block10name'] = format_string($theme->settings->$block10name);
        $templatecontext['block10'][$j]['block10job'] = format_string($theme->settings->$block10job);
        $templatecontext['block10'][$j]['block10caption'] = format_text($theme->settings->$block10caption);
        $templatecontext['block10'][$j]['block10linkurl'] = format_string($theme->settings->$block10link);
    }
    return $templatecontext;
}
/**
 * Frontpage block11.
 * @return url
 */
function theme_edzsaas_frontpageblock11() {
    // Site blog frontpage.
    global $CFG, $OUTPUT, $DB;
    $theme = theme_config::load('edzsaas');
    $templatecontext['block11enabled'] = $theme->settings->block11enabled;
    if ($CFG->bloglevel < BLOG_GLOBAL_LEVEL && (!isloggedin() || isguestuser())) {
        $templatecontext['block11enabled'] = "false";
        return $templatecontext;
    }
    if (empty($templatecontext['block11enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block11design'.$theme->settings->block11design] = $theme->settings->block11design;
    $templatecontext['block11header'] = format_string($theme->settings->block11header);
    $templatecontext['block11caption'] = format_text($theme->settings->block11caption);
    $count = $theme->settings->block11count;
    if ($CFG->dbtype === 'sqlsrv') {
        $sql = "SELECT TOP ". $count ." *";
    } else {
        $sql = "SELECT *";
    }
    $sql = $sql." FROM {post} pt";
    if (isloggedin()) {
        $sql = $sql." WHERE pt.publishstate = 'public' or pt.publishstate = 'site'";
    } else {
        $sql = $sql." WHERE pt.publishstate = 'public'";
    }
    $sql = $sql." ORDER BY pt.created DESC";
    if ($CFG->dbtype != 'sqlsrv') {
        $sql = $sql." LIMIT ". $count;
    }
    $posts = $DB->get_records_sql($sql, []);
    if (!empty($posts)) {
        $j = 0;
        foreach ($posts as $post) {
            $templatecontext['block11'][$j]['subject'] = format_string($post->subject);
            $templatecontext['block11'][$j]['summary'] = format_string($post->summary);
            $templatecontext['block11'][$j]['created'] = gmdate("d,m,Y", $post->created);
            $templatecontext['block11'][$j]['lastmodified'] = gmdate("d/m/Y", $post->lastmodified);
            $templatecontext['block11'][$j]['postURL'] = new moodle_url('/blog/index.php?entryid='. $post->id);
            $templatecontext['block11'][$j]['imgurl'] = edzsaas_get_blog_post_image($post->id);
            $templatecontext['block11'][$j]['tag'] = $OUTPUT->tag_list(core_tag_tag::get_item_tags('core', 'post', $post->id));
            if ($user = $DB->get_record('user', ['id' => $post->userid])) {
                $templatecontext['block11'][$j]['userpicture'] =
                    $OUTPUT->user_picture($user, ['size' => '25']);
                $templatecontext['block11'][$j]['userURL'] =
                    new moodle_url('/user/profile.php', ['id' => $post->userid ]);
                $templatecontext['block11'][$j]['username'] = format_string(fullname($user));
            }
            if ($j == 0) {
                $templatecontext['block11'][$j]['active'] = "1";
            } else {
                $templatecontext['block11'][$j]['active'] = "";
            }
            $by = new stdClass();
            $by->name = fullname($user);
            $by->date = userdate($post->created);
            $templatecontext['block11'][$j]['userdate'] = $OUTPUT->container( $by->date, 'userdate');
            $j++;
        }
    }
    return $templatecontext;
}
/**
 * Frontpage block18.
 * @return url
 */
function theme_edzsaas_frontpageblock18() {
    $theme = theme_config::load('edzsaas');
    $templatecontext['block18enabled'] = $theme->settings->block18enabled;
    if (empty($templatecontext['block18enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block18title'] = format_string($theme->settings->block18title);
    $templatecontext['block18caption'] = format_text($theme->settings->block18caption, FORMAT_HTML, ['noclean' => true]);
    $templatecontext['block18csslink'] = $theme->settings->block18csslink;
    $templatecontext['block18css'] = $theme->settings->block18css;
    return $templatecontext;
}
/**
 * Frontpage block19.
 * @return url
 */
function theme_edzsaas_frontpageblock19() {
    global $OUTPUT;
    $theme = theme_config::load('edzsaas');
    $templatecontext['block19enabled'] = $theme->settings->block19enabled;
    if (empty($templatecontext['block19enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block19design'.$theme->settings->block19design] = $theme->settings->block19design;
    $templatecontext['block19headerenabled'] = $theme->settings->block19headerenabled;
    if (!empty($templatecontext['block19headerenabled'])) {
        $templatecontext['block19header'] = format_string($theme->settings->block19header);
        $templatecontext['block19caption'] = format_text($theme->settings->block19caption);
    }
    $j = 0;
    for ($i = 1; $i <= 6; $i++) {
        $block19img = "sliderimageblock19img{$i}";
        $block19link = "block19link{$i}";
        $image = $theme->setting_file_url($block19img, $block19img);
        if (empty($image)) {
            $image = $OUTPUT->image_url('edzsaas/block19/'.$i, 'theme');
        }
        if (!empty($image)) {
            $templatecontext['block19'][$j]['image19'] = $image;
            $templatecontext['block19'][$j]['link'] = format_string($theme->settings->$block19link);
            $j++;
        }
    }
    return $templatecontext;
}
/**
 * Frontpage block20.
 * @return url
 */
function theme_edzsaas_frontpageblock20() {
    global $OUTPUT;

    $theme = theme_config::load('edzsaas');
    $templatecontext['block20enabled'] = $theme->settings->block20enabled;
    if (empty($templatecontext['block20enabled'])) {
        return $templatecontext;
    }
    $templatecontext['block20moodle'] = $theme->settings->block20moodle;
    $templatecontext['footerbackgroundcolor'] = $theme->settings->footerbackgroundcolor;
    switch ($theme->settings->block20logo) {
        case 'Logo':
            $templatecontext['block20logologo'] = true;
            break;
        case 'Small logo':
            $templatecontext['block20logosmall'] = true;
            break;
    }
    $templatecontext['block20col1header'] = format_string($theme->settings->block20col1header);
    $templatecontext['block20col1caption'] = format_string($theme->settings->block20col1caption);
    $templatecontext['block20col2header'] = format_string($theme->settings->block20col2header);
    $templatecontext['block20col2links'] = theme_edzsaas_links($theme->settings->block20col2link);
    $templatecontext['block20col3header'] = format_string($theme->settings->block20col3header);
    $templatecontext['block20col3links'] = theme_edzsaas_links($theme->settings->block20col3link);
    $templatecontext['block20col4header'] = format_string($theme->settings->block20col4header);
    $templatecontext['block20col4caption'] = format_text($theme->settings->block20col4caption);
    $templatecontext['block20social'] = format_text($theme->settings->block20social);
    $templatecontext['block20copyright'] = format_text($theme->settings->block20copyright);
    return $templatecontext;
}


/**
 * Added By Rashid as on 04-08-25
 * Frontpage  block  fro featuredcourses context.
 * @return array
 */
function theme_edzsaas_frontpagefeaturedcourses() {
    global $CFG, $OUTPUT, $DB;

    require_once($CFG->dirroot . '/course/lib.php');

    $theme = theme_config::load('edzsaas');
    $templatecontext = [];

    // Enabled from theme settings
    $templatecontext['fcourseenabled'] = $theme->settings->fcourseenabled ?? 0;
    if (empty($templatecontext['fcourseenabled'])) {
        return $templatecontext;
    }

    // Course ID from settings
    $courseid = $theme->settings->featuredcourseid ?? 0;
    if (empty($courseid)) {
        $templatecontext['fcourseenabled'] = false;
        return $templatecontext;
    }

    // ✅ Safe query: only get if course exists, visible, and valid category
    $course = $DB->get_record_select(
        'course',
        'id = :id AND visible = 1 AND category > 0',
        ['id' => $courseid]
    );

    if (!$course) {
        $templatecontext['fcourseenabled'] = false;
        return $templatecontext;
    }

    $context = context_course::instance($course->id);

    // Course summary image
    $imageurl = \core_course\external\course_summary_exporter::get_course_image($course);
    if (empty($imageurl)) {
        $imageurl = $CFG->wwwroot . '/theme/edzsaas/pix/edzsaas/defaultcourse.webp';
    }

    // Course category name
    $categoryname = $DB->get_field('course_categories', 'name', ['id' => $course->category]) ?? '';

    // Disable if no meaningful content
    if (empty(trim($course->summary)) && empty($course->startdate)) {
        $templatecontext['fcourseenabled'] = false;
        return $templatecontext;
    }

    // Fill template context
    $templatecontext['courseid'] = $course->id;
    $templatecontext['coursename'] = format_string($course->fullname, true, ['context' => $context]);
    $templatecontext['courseurl'] = new moodle_url('/course/view.php', ['id' => $course->id]);
    $templatecontext['enrolledusers'] = count_enrolled_users($context);
    $templatecontext['startdate'] = userdate($course->startdate, get_string('strftimedate', 'langconfig'));
    $templatecontext['courseimage'] = $imageurl;
    $templatecontext['categoryname'] = $categoryname;
    $templatecontext['coursedescription'] = format_text($course->summary, $course->summaryformat, ['context' => $context]);

    // Theme settings
    $templatecontext['coursecarddesccolor'] = $theme->settings->coursecarddesccolor ?? '#333333';
    $templatecontext['coursecardbg'] = $theme->settings->coursecardbg ?? '#ffffff';
    $templatecontext['coursecardtitle'] = format_string($theme->settings->coursecardtitle ?? 'Featured Course');
    $templatecontext['coursecardsubtitle'] = format_string($theme->settings->coursecardsubtitle ?? 'Start Learning Today');
    $templatecontext['coursenamecolor'] = $theme->settings->coursenamecolor ?? '#000000';
    $templatecontext['enrolledcolor'] = $theme->settings->enrolledcolor ?? '#888888';
    $templatecontext['datecolor'] = $theme->settings->datecolor ?? '#666666';
    $templatecontext['viewbtnbg'] = $theme->settings->viewbtnbg ?? '#0066cc';
    $templatecontext['viewbtntextcolor'] = $theme->settings->viewbtntextcolor ?? '#ffffff';
    $templatecontext['viewbtntext'] = format_string($theme->settings->viewbtntext ?? 'View Course');
    $templatecontext['courseboxbgclr'] = $theme->settings->courseboxbgclr ?? '#ffffff';
    $templatecontext['coursecardtitleclr'] = $theme->settings->coursecardtitleclr ?? '#0066cc';
    $templatecontext['coursecardsubtitleclr'] = $theme->settings->coursecardsubtitleclr ?? '#0066cc';
    $templatecontext['courseinfobgcolor'] = $theme->settings->courseinfobgcolor ?? '#d3d9e0ff';
    $templatecontext['courseinfotexticoncolor'] = $theme->settings->courseinfotexticoncolor ?? '#ffffff';

    return $templatecontext;
}


/**
 * Added By Rashid as on 04-08-25
 * Frontpage  block  for hero section.
 * @return array
 */

function theme_edzsaas_get_hero_settings() {
    global $OUTPUT,$CFG;

    // If plan is NOT business, hide settings page completely
    // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
    //     return []; // stop here, no settings added
    // }

    $theme = theme_config::load('edzsaas');
    $templatecontext = [];

    // General hero settings
    $templatecontext['heroenabled'] = get_config('theme_edzsaas', 'heroenabled');
    $templatecontext['herodesign'] = get_config('theme_edzsaas', 'herodesign');
    $templatecontext['herobgcolor'] = get_config('theme_edzsaas', 'herobgcolor');
    $templatecontext['herotitlecolor'] = get_config('theme_edzsaas', 'herotitlecolor');
    $templatecontext['herodesccolor'] = get_config('theme_edzsaas', 'herodesccolor');
    $templatecontext['herobtnbgcolor'] = get_config('theme_edzsaas', 'herobtnbgcolor');
    $templatecontext['herobtntextcolor'] = get_config('theme_edzsaas', 'herobtntextcolor');
    $templatecontext['herobtnhoverbg'] = get_config('theme_edzsaas', 'herobtnhoverbg');
    $templatecontext['herobgoverlay'] = get_config('theme_edzsaas', 'herobgoverlay');

    // Hero 1
    $templatecontext['hero1enabled'] = ($templatecontext['herodesign'] == 1);
    $templatecontext['hero1title'] = format_string(get_config('theme_edzsaas', 'nicehero1title'));
    $templatecontext['hero1desc'] = format_text(get_config('theme_edzsaas', 'nicehero1desc'), FORMAT_HTML);
    $templatecontext['nicehero1bg'] = get_config('theme_edzsaas', 'nicehero1bg');
    $nicehero1image = $theme->setting_file_url('nicehero1image', 'nicehero1image');
    if (empty($nicehero1image)) {
        $nicehero1image = $CFG->wwwroot . '/theme/edzsaas/pix/edzsaas/hero/hero1.png';
    }
    $templatecontext['nicehero1image'] = $nicehero1image;


    // Hero 2
    $templatecontext['hero2enabled'] = ($templatecontext['herodesign'] == 2);
    $hero2bg = $theme->setting_file_url('hero2bg', 'hero2bg');
    if (empty($hero2bg)) {
        $hero2bg = $CFG->wwwroot . '/theme/edzsaas/pix/edzsaas/hero/hero2.png';
    }
    $templatecontext['hero2bg'] = $hero2bg;
    $templatecontext['hero2title'] = format_string(get_config('theme_edzsaas', 'hero2title'));
    $templatecontext['hero2desc'] = format_text(get_config('theme_edzsaas', 'hero2desc'), FORMAT_HTML);
    $templatecontext['hero2searchtoggle'] = get_config('theme_edzsaas', 'hero2searchtoggle');
    $templatecontext['hero2searchplaceholder'] = get_config('theme_edzsaas', 'hero2searchplaceholder');
    $templatecontext['hero2iconcolor'] = get_config('theme_edzsaas', 'hero2iconcolor');
    $templatecontext['hero2searchbg'] = get_config('theme_edzsaas', 'hero2searchbg');
    $templatecontext['hero2textcolor'] = get_config('theme_edzsaas', 'hero2textcolor');

    // Hero 3
    $templatecontext['hero3enabled'] = ($templatecontext['herodesign'] == 3);
    $hero3bg = $theme->setting_file_url('hero3bg', 'hero3bg');
    if (empty($hero3bg)) {
        $hero3bg = $CFG->wwwroot . '/theme/edzsaas/pix/edzsaas/hero/hero3.png';
    }
    $templatecontext['hero3bg'] = $hero3bg;

    $templatecontext['hero3title'] = format_string(get_config('theme_edzsaas', 'hero3title'));
    $templatecontext['hero3btntext'] = format_string(get_config('theme_edzsaas', 'hero3btntext'));
    $templatecontext['hero3btnbg'] = get_config('theme_edzsaas', 'hero3btnbg');
    $templatecontext['hero3btntextcolor'] = get_config('theme_edzsaas', 'hero3btntextcolor');

    // Hero 4
    $templatecontext['hero4enabled'] = ($templatecontext['herodesign'] == 4);
    $hero4bg = $theme->setting_file_url('hero4bg', 'hero4bg');
    if (empty($hero4bg)) {
        $hero4bg = $CFG->wwwroot . '/theme/edzsaas/pix/edzsaas/hero/hero4.png';
    }
    $templatecontext['hero4bg'] = $hero4bg;


    $templatecontext['hero4title'] = format_string(get_config('theme_edzsaas', 'hero4title'));
    $templatecontext['hero4desc'] = format_text(get_config('theme_edzsaas', 'hero4desc'), FORMAT_HTML);

    return $templatecontext;
}



/**
 * Added By Partik as on 12-08-25
 * Frontpage  block  for counter section.
 * @return array
 */

function theme_edzsaas_frontpageblock21() {
    global $CFG;
    // If plan is NOT business, hide settings page completely
    // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
    //     return []; // stop here, no settings added
    // }

    $theme = theme_config::load('edzsaas');

    $templatecontext = [];

    $templatecontext['block21enabled'] = !empty($theme->settings->block21enabled);
    if (empty($templatecontext['block21enabled'])) {
        return $templatecontext;
    }

    // Use format_text for caption because it's a textarea, allowing HTML.
    $templatecontext['block21caption'] = format_text($theme->settings->block21caption, FORMAT_HTML);

    // Button text and URL.
    $templatecontext['block21buttontext'] = format_string($theme->settings->block21buttontext);
    $templatecontext['block21buttonurl'] = $theme->settings->block21buttonurl;

    // Background color - raw, used in inline style.
    $templatecontext['block21bgcolor'] = !empty($theme->settings->block21bgcolor) ? $theme->settings->block21bgcolor : '#ffffff';

    // Counter values and descriptions.
    $templatecontext['block21_counter1'] = isset($theme->settings->block21_counter1) ? $theme->settings->block21_counter1 : '';
    $templatecontext['block21_desc1']    = isset($theme->settings->block21_desc1) ? $theme->settings->block21_desc1 : '';

    $templatecontext['block21_counter2'] = isset($theme->settings->block21_counter2) ? $theme->settings->block21_counter2 : '';
    $templatecontext['block21_desc2']    = isset($theme->settings->block21_desc2) ? $theme->settings->block21_desc2 : '';

    $templatecontext['block21_counter3'] = isset($theme->settings->block21_counter3) ? $theme->settings->block21_counter3 : '';
    $templatecontext['block21_desc3']    = isset($theme->settings->block21_desc3) ? $theme->settings->block21_desc3 : '';

    $templatecontext['block21_counter4'] = isset($theme->settings->block21_counter4) ? $theme->settings->block21_counter4 : '';
    $templatecontext['block21_desc4']    = isset($theme->settings->block21_desc4) ? $theme->settings->block21_desc4 : '';
    $templatecontext['startcounter'] = 0;
    return $templatecontext;
}


/**
 * Added By Partik as on 12-08-25
 * Frontpage  block  for faq section.
 * @return array
 */



function theme_edzsaas_frontpageblock22() {

    // global $CFG;
    // // If plan is NOT business, hide settings page completely
    // if (empty($CFG->plan) || strtolower($CFG->plan) !== 'business') {
    //     return []; // stop here, no settings added
    // }

    $theme = theme_config::load('edzsaas');

    $templatecontext = [];

    $templatecontext['block22enabled'] = !empty($theme->settings->block22enabled);
    if (empty($templatecontext['block22enabled'])) {
        return $templatecontext;
    }

    $templatecontext['block22title'] = format_string(!empty($theme->settings->block22title) ? $theme->settings->block22title : '');
    $templatecontext['block22bgcolor'] = !empty($theme->settings->block22bgcolor) ? $theme->settings->block22bgcolor : '#f5f5f5';

    $templatecontext['questions'] = [];
    for ($i = 1; $i <= 10; $i++) {
        $qkey = 'block22question' . $i;
        $akey = 'block22answer' . $i;

        $q = !empty($theme->settings->{$qkey}) ? format_string($theme->settings->{$qkey}) : '';
        $a = !empty($theme->settings->{$akey}) ? format_text($theme->settings->{$akey}, FORMAT_HTML) : '';

        if (!empty($q) && !empty($a)) {
            $templatecontext['questions'][] = [
                'index' => $i,
                'question' => $q,
                'answer' => $a
            ];
        }
    }

    return $templatecontext;
}

function theme_edzsaas_frontpageblock24() {
    global $DB, $OUTPUT;

    $theme = theme_config::load('edzsaas');
    $context = [];
     //echo "rashidsss"; die();
    // Check if block is enabled.
    $context['block24enabled'] = !empty($theme->settings->block24enabled);
    if (empty($context['block24enabled'])) {
        return $context;
    }

    // Get and convert selected categories.
    $selectedcategories = $theme->settings->block24categories ?? '';
    if (!is_array($selectedcategories)) {
        $selectedcategories = array_filter(explode(',', $selectedcategories));
    }
    if (empty($selectedcategories)) {
         $context['block24enabled'] = false;
        return $context;
    }
    // echo "<pre>";
    // echo print_r($selectedcategories); 
    // echo "rashid"; die();
    // Header/button texts.
    $context['block24header'] = format_text($theme->settings->block24header, FORMAT_HTML);
    $context['block24button'] = format_text($theme->settings->block24button, FORMAT_HTML);
    $context['block24buttonlink'] = $theme->settings->block24buttonlink;

    // Build filter list.
    $filters = [];
    foreach ($selectedcategories as $catid) {
        if ($cat = core_course_category::get($catid, IGNORE_MISSING)) {
            $filters[] = [
                'id' => $catid,
                'name' => $cat->get_formatted_name()
            ];
        }
    }
    $context['filters'] = $filters;

    // Gather courses.
    $coursesdata = [];
    foreach ($selectedcategories as $catid) {
        $courses = get_courses(
            $catid,
            'fullname ASC',
            'c.id, c.fullname, c.shortname, c.summary, c.category,c.timemodified'
        );

        foreach ($courses as $course) {
            $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
            $courseimage = \core_course\external\course_summary_exporter::get_course_image($course);
            $update = isset($course->timemodified) ? date('d M Y', $course->timemodified) : '';

            $coursesdata[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'categoryid' => $course->category,
                'categoryname' => $filters[array_search($course->category, array_column($filters, 'id'))]['name'] ?? '',
                'image' => $courseimage,
                'url' => $courseurl->out(),
                'update'=> $update,  // make sure key is 'update'
                
            ];
        }
    }
    $context['courses'] = $coursesdata;
    $context['showarrows'] = count($coursesdata) > 4;

    return $context;
}