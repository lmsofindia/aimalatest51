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
 * Subcat ajax for local_edzallcourse plugin.
 *
 * @package    local_edzallcourse
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */

define('AJAX_SCRIPT', true);

require('../../config.php');
require_once($CFG->libdir . '/externallib.php');

require_sesskey();
require_capability('local/edzallcourse:view', context_system::instance());

global $PAGE, $OUTPUT;
$PAGE->set_context(context_system::instance());

// === Params ===
$categoryid = optional_param('subcategoryid', 0, PARAM_RAW);
$limit      = optional_param('limit', 12, PARAM_INT);
$offset     = optional_param('offset', 0, PARAM_INT);
$filters    = optional_param_array('filters', [], PARAM_RAW);

$allcourses = \local_edzallcourse\local\service::get_courses_by_category($categoryid);

// === Category Details ===
$categorydescription = '';
$categorynamee       = '';

$category = \core_course_category::get($categoryid, IGNORE_MISSING);

if ($category && $category->parent != 0) {
    $context = context_coursecat::instance($categoryid);
    $categorydescription = format_text(
        file_rewrite_pluginfile_urls(
            $category->description,
            'pluginfile.php',
            $context->id,
            'coursecat',
            'description',
            ''
        ),
        $category->descriptionformat,
        [
            'context'   => $context,
            'noclean'   => true,
            'trusttext' => true,
        ]
    );
    $categorynamee = $category->get_formatted_name();
}

function get_course_gradient_color($id) {
    $colors = [
        'f7989c', 'aaf9a7', 'f7d58b', '8dc6e8',
        'c6a9d4', 'f7b8ab', 'abeef7', 'dbefc9',
    ];
    return '#' . $colors[$id % count($colors)];
}

function get_course_pattern_datauri($id) {
    $patterns = [
        '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
            <circle cx="0" cy="0" r="50" fill="rgba(255,255,255,0.15)"/>
            <circle cx="100" cy="0" r="50" fill="rgba(255,255,255,0.15)"/>
            <circle cx="0" cy="100" r="50" fill="rgba(255,255,255,0.15)"/>
            <circle cx="100" cy="100" r="50" fill="rgba(255,255,255,0.15)"/>
            <circle cx="50" cy="50" r="30" fill="rgba(255,255,255,0.1)"/>
        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80">
            <circle cx="20" cy="20" r="15" fill="rgba(255,255,255,0.15)"/>
            <circle cx="60" cy="20" r="15" fill="rgba(255,255,255,0.15)"/>
            <circle cx="20" cy="60" r="15" fill="rgba(255,255,255,0.15)"/>
            <circle cx="60" cy="60" r="15" fill="rgba(255,255,255,0.15)"/>
        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
            <polygon points="50,0 95,25 95,75 50,100 5,75 5,25"
                fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="5"/>
            <polygon points="50,15 80,32 80,68 50,85 20,68 20,32"
                fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="3"/>
        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
            <rect x="10" y="10" width="80" height="80" rx="5"
                fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="6"/>
            <rect x="25" y="25" width="50" height="50" rx="3"
                fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="4"/>
        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
            <polygon points="50,5 95,50 50,95 5,50"
                fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="5"/>
            <polygon points="50,20 80,50 50,80 20,50"
                fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="3"/>
        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="40">
            <path d="M0,20 C25,0 75,0 100,20 C125,40 175,40 200,20"
                fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="4"/>
            <path d="M0,30 C25,10 75,10 100,30 C125,50 175,50 200,30"
                fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3"/>
        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
            <polygon points="50,5 95,90 5,90"
                fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="5"/>
            <polygon points="50,25 78,75 22,75"
                fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="3"/>
        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">
            <circle cx="10" cy="10" r="5" fill="rgba(255,255,255,0.2)"/>
            <circle cx="30" cy="10" r="5" fill="rgba(255,255,255,0.2)"/>
            <circle cx="50" cy="10" r="5" fill="rgba(255,255,255,0.2)"/>
            <circle cx="10" cy="30" r="5" fill="rgba(255,255,255,0.2)"/>
            <circle cx="30" cy="30" r="5" fill="rgba(255,255,255,0.2)"/>
            <circle cx="50" cy="30" r="5" fill="rgba(255,255,255,0.2)"/>
            <circle cx="10" cy="50" r="5" fill="rgba(255,255,255,0.2)"/>
            <circle cx="30" cy="50" r="5" fill="rgba(255,255,255,0.2)"/>
            <circle cx="50" cy="50" r="5" fill="rgba(255,255,255,0.2)"/>
        </svg>',
    ];
    $svg    = $patterns[$id % count($patterns)];
    $base64 = base64_encode($svg);
    return 'data:image/svg+xml;base64,' . $base64;
}

$totalcourses = count($allcourses);
$totalpages   = $totalcourses > 0 ? (int) ceil($totalcourses / $limit) : 1;
$currentpage  = (int) floor($offset / $limit) + 1;
$courses      = array_slice($allcourses, $offset, $limit);

$pages = [];
for ($i = 1; $i <= $totalpages; $i++) {
    $pages[] = [
        'number'     => $i,
        'offset'     => ($i - 1) * $limit,
        'active'     => ($i === $currentpage),
        'categoryid' => (int) $categoryid,
    ];
}
foreach ($courses as &$course) {
    $courseobj = get_course($course['id']);

    $courseimage             = \core_course\external\course_summary_exporter::get_course_image($courseobj);
    $course['courseimage']   = $courseimage ?: null;
    $course['coursecolor']   = !$courseimage ? get_course_gradient_color($courseobj->id) : null;
    $course['coursepattern'] = !$courseimage ? get_course_pattern_datauri($courseobj->id) : null;

    $cat  = core_course_category::get($courseobj->category, IGNORE_MISSING);
    $course['categoryname'] = $cat ? $cat->get_formatted_name() : '';

    $course['startdate'] = $courseobj->startdate
        ? userdate($courseobj->startdate, get_string('strftimedate', 'langconfig'))
        : '';

    $context      = context_course::instance($courseobj->id);
    $teachers     = get_role_users(3, $context);
    $teachernames = [];
    foreach ($teachers as $t) {
        $teachernames[] = fullname($t);
    }

    if (!empty($teachernames)) {
        $firstteacher       = reset($teachernames);
        $total              = count($teachernames);
        $course['teachers'] = $total > 1
            ? $firstteacher . ' +' . ($total - 1)
            : $firstteacher;
    } else {
        $course['teachers'] = get_string('notyetassigned', 'local_edzallcourse');
    }

    if (!empty($filters['compliance']) && in_array(1, $filters['compliance'])) {
        $course['badge'] = 'COMPLIANCE';
    }
}
unset($course);

$template = $OUTPUT->render_from_template('local_edzallcourse/courselist', [
    'courses'        => $courses,
    'noresults'      => empty($courses),
    'haspagination'  => $totalpages > 1,
    'pages'          => $pages,
    'hasprev'        => $currentpage > 1,
    'hasnext'        => $currentpage < $totalpages,
    'prevoffset'     => max(0, $offset - $limit),
    'nextoffset'     => $offset + $limit,
    'categoryid'     => (int) $categoryid,
    'currentpage'    => $currentpage,
    'totalpages'     => $totalpages,
]);

echo json_encode([
    'html'         => $template,
    'noresults'    => empty($courses),
    'description'  => $categorydescription,
    'categoryname' => $categorynamee,
    'totalpages'   => $totalpages,
    'currentpage'  => $currentpage,
]);

die;