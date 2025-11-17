<?php
require('../../config.php');
require_once($CFG->libdir . '/completionlib.php');

require_once('lib.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);

$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/customenrol/index.php', ['id' => $courseid]));
$PAGE->set_pagelayout('course');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/local/customenrol/styles.css');

$layout = (int)get_config('local_customenrol', 'layout') ?: 1;
$PAGE->requires->js_call_amd('local_customenrol/toggle_sections', 'init');
$PAGE->requires->js_call_amd('local_customenrol/lce_tabs', 'init');

// Gather data.
$modinfo = get_fast_modinfo($course);
$cms = $modinfo->get_cms();
$sections = $modinfo->get_section_info_all();
$studentscount = count_enrolled_users($context, 'moodle/course:view');
$tags = \core_tag_tag::get_item_tags_array('core', 'course', $courseid);
$teachers = get_enrolled_users($context, 'moodle/course:update');

$teachersdata = [];
if (!empty($teachers)) {
    foreach ($teachers as $teacher) {
        // Get teacher profile description (from user table or description field)
        $profiledesc = format_text($teacher->description, FORMAT_HTML, ['context' => context_user::instance($teacher->id)]);

        // Count how many courses they teach
        $teachercourses = enrol_get_users_courses($teacher->id, true, ['id', 'fullname']);
        $totalcourses = count($teachercourses);

        $teachersdata[] = [
            'teacherfullname' => fullname($teacher),
            'teacherprofileurl' => (new moodle_url('/user/profile.php', ['id' => $teacher->id]))->out(false),
            'teacheruserpicture' => $OUTPUT->user_picture($teacher, [
                'size' => 80,
                'includefullname' => false,
                'link' => false
            ]),
            'teacherdescription' => $profiledesc,
            'teachercoursecount' => $totalcourses
        ];
    }
}


//$templatecontext['teachers'] = $teachersdata;

$rating = local_customenrol_get_course_rating($courseid);
$previewurl = local_customenrol_get_preview_url($course);

$context_course = context_course::instance($course->id);

$context = context_course::instance($course->id);
$fs = get_file_storage();
// First, check overviewfiles area (where course images usually are in recent Moodle versions)
$files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'itemid, filepath, filename', false);

$courseimage = '';

// First, check overviewfiles area (where course images usually are in recent Moodle versions)
$courseimage = false;

/** @var \stored_file $file */
if (!empty($files)) {
    foreach ($files as $file) {
        $isimage = $file->is_valid_image();
        if ($isimage) {
            $courseimage = file_encode_url(
                "{$CFG->wwwroot}/pluginfile.php",
                "/{$file->get_contextid()}/{$file->get_component()}/" .
                    "{$file->get_filearea()}{$file->get_filepath()}{$file->get_filename()}",
                !$isimage
            );
        }
    }
}
// If no image found in overviewfiles, check course image area.

if (empty($courseimage)) {
    $courseimage = $OUTPUT->image_url('course-default', 'theme')->out();
}

// Build curriculum array.
$curriculum = [];
foreach ($sections as $sec) {
    if (!$sec->uservisible) {
        continue;
    }
    $items = [];
    foreach ($cms as $cm) {
        if ($cm->sectionnum != $sec->section || !$cm->uservisible) {
            continue;
        }
        
        // Module icon
        $iconurl = $OUTPUT->image_url('icon', $cm->modname)->out(false);
        $items[] = [
            'name' => format_string($cm->name),
            'modname' => $cm->modname,
            'url' => $cm->url ? $cm->url->out(false) : '',
            'iconurl' => $iconurl
        ];
    }
    $curriculum[] = [
        'name' => get_section_name($course, $sec),
        'summary' => format_text(
            file_rewrite_pluginfile_urls(
                $sec->summary,
                'pluginfile.php',
                $context_course->id,
                'course',
                'section',
                $sec->id
            ),
            $sec->summaryformat,
            ['context' => $context_course]
        ),
        'items' => $items,
        'count' => count($items),
    ];
}

// Extract \"What you\'ll learn\" (from course summary bullets or custom field shortname=whatyouwilllearn).
$whatlearn = [];
if (!empty($course->customfields) && is_array($course->customfields)) {
    foreach ($course->customfields as $cf) {
        if (!empty($cf['shortname']) && $cf['shortname'] === 'whatyouwilllearn' && !empty($cf['value'])) {
            // Split by newline to bullets.
            $lines = preg_split('/\r\n|\r|\n/', trim(strip_tags($cf['value'])));
            foreach ($lines as $ln) {
                if (trim($ln) !== '') {
                    $whatlearn[] = trim($ln);
                }
            }
        }
    }
}
if (empty($whatlearn) && !empty($course->summary)) {
    $plain = trim(strip_tags(format_text($course->summary)));
    $lines = preg_split('/\r\n|\r|\n|·|-|•|\*/', $plain);
    foreach ($lines as $ln) {
        if (trim($ln) !== '') {
            $whatlearn[] = trim($ln);
        }
    }
    $whatlearn = array_slice($whatlearn, 0, 8);
}

$tagsdata = [];
foreach ($tags as $tag) {
    $tagsdata[] = [
        'name' => $tag,
        'url' => core_tag_tag::make_url($tag, 'core_course', $course->id)->out(false)
    ];
}

//$templatecontext['tags'] = $tagsdata;

// Add enrolment options.
// Get enrolment instances for this course
// Get all enrol widgets available in this course.
$enrols = enrol_get_plugins(true);
$enrolinstances = enrol_get_instances($course->id, true);
// Get the course renderer
$courserenderer = $PAGE->get_renderer('core', 'course');
$plugins     = enrol_get_plugins(true);

// Mihir old method do not use it
// $widgets = [];
// foreach ($enrolinstances as $instance) {
//     if (!isset($enrols[$instance->enrol])) {
//         continue;
//     }
//     $widget = $enrols[$instance->enrol]->enrol_page_hook($instance);
//     if ($widget) {
//         $widgets[$instance->id] = $widget;
//     }
// }

// $enroloptionshtml = $courserenderer->enrolment_options(
//     $course,
//     $widgets,
//     $returnurl ? new \core\url($returnurl) : null
// );

$methodshtml = '';


foreach ($enrolinstances as $instance) {
    if ((int)$instance->status !== ENROL_INSTANCE_ENABLED) {
        continue;
    }
    if (!isset($plugins[$instance->enrol])) {
        continue;
    }
    $plugin = $plugins[$instance->enrol];
    // if (method_exists($plugin, 'get_info_icons')) {
    //     $icons = $plugin->get_info_icons($instance);
    //     echo 'here2';
    //     foreach ($icons as $icon) {
    //         $methodshtml .= $OUTPUT->render($icon);
    //     }
    // }
    if (method_exists($plugin, 'enrol_page_hook')) {
        $methodshtml .= $plugin->enrol_page_hook($instance);
    }
}
$enroloptionshtml = $methodshtml;
// Add to template context
$templatecontext['enroloptions'] = $enroloptionshtml;

// Get category record for the course.   added by rashid as on 17-09-25
$category = $DB->get_record('course_categories', ['id' => $course->category], '*', MUST_EXIST);
$categoryurl = new moodle_url('/course/index.php', ['categoryid' => $category->id]);

/* now get course custom field values */
// Get course custom fields
$customfields = local_customenrol_get_course_customfields($courseid);


// Limit to 200 words.
$summaryplain = format_text($course->summary, $course->summaryformat);

$limit = 200;
if (core_text::strlen($summaryplain) > $limit) {
    $summaryplain = core_text::substr($summaryplain, 0, $limit) . '...';
}
$templatecontext = [
    'courseid' => $course->id,
    'courseimage' => $courseimage,
    'courseshortname' => $course->shortname,
    'fullname' => format_string($course->fullname),
    'summary_html' => $summaryplain,
    'tags' => $tagsdata,
    'teachers' => $teachersdata,
    'studentscount' => $studentscount,
    'startdate' => $course->startdate ? userdate($course->startdate, '%d %B %Y') : '',
    'enddate'   => $course->enddate ? userdate($course->enddate, '%d %B %Y') : '',
    'modulescount' => count($cms),
    'sectionscount' => count($sections),
    'curriculum' => $curriculum,
    'whatlearn' => array_map(function ($s) {
        return ['text' => $s];
    }, $whatlearn),
    'rating' => $rating ? [
        'has' => true,
        'avg' => round($rating['avg'], 1),
        'count' => $rating['count'],
        'stars' => $rating['stars'],
    ] : ['has' => false],
    'previewurl' => $previewurl,
    'enroloptions' => $enroloptionshtml,
    'customfields'  => $customfields,
    // Added by rashid as on 17-09-25
    'coursecategory' => format_string($category->name),
    'coursecategoryurl' => $categoryurl->out(),
    'wwwroot' => $CFG->wwwroot,
];

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_customenrol/layout' . $layout, $templatecontext);

echo $OUTPUT->footer();
