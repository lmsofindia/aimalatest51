<?php


/**
 * search information for local_navsearch
 *
 * @package    local_navsearch
 * @copyright  2025 Edz Lms <marketing@edzlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context(context_system::instance());
require_capability('local/navsearch:view', $context);

$query = required_param('query', PARAM_RAW_TRIMMED);
if (strlen($query) < 2) {
    echo html_writer::div(get_string('nosearchresults', 'local_navsearch'), 'alert alert-info');
    exit;
}

//$areas = get_config('local_navsearch', 'searchareas');
//$areas = $areas ? unserialize($areas) : [];

$areasraw = get_config('local_navsearch', 'searchareas');
$areas = [];
if (!empty($areasraw)) {
    // Try unserialize first
    $temp = @unserialize($areasraw);
    if ($temp !== false || $areasraw === 'b:0;') {
        $areas = $temp;
    } else {
        // Try JSON decode
        $temp = json_decode($areasraw, true);
        if (is_array($temp)) {
            $areas = $temp;
        } else {
            // Maybe it's a comma-separated list
            $areas = array_fill_keys(explode(',', $areasraw), 1);
        }
    }
}

// Keep only selected keys
$areas = array_keys(array_filter($areas));



if (empty($areas)) {
    echo html_writer::div(get_string('nosearchresults', 'local_navsearch'), 'alert alert-info');
    exit;
}

$results = [];
$querylike = '%' . $DB->sql_like_escape($query) . '%';

/**
 * Helper to avoid duplicates
 */
function add_result(&$results, $id, $name, $type, $icon, $url) {
    $key = $type . '_' . $id;
    if (!isset($results[$key])) {
        $results[$key] = [
            'id' => $id,
            'name' => $name,
            'type' => $type,
            'icon' => $icon,
            'url' => $url
        ];
    }
}

/** 1. Course name */
if (in_array('course_name', $areas)) {
    $records = $DB->get_records_select('course', 'fullname LIKE ?', [$querylike], '', 'id, fullname');
    foreach ($records as $rec) {
        add_result($results, $rec->id, $rec->fullname, 'Course', 'i/course',
            new moodle_url('/course/view.php', ['id' => $rec->id]));
    }
}

/** 2. Course description */
if (in_array('course_desc', $areas)) {
    $records = $DB->get_records_select('course', 'summary LIKE ?', [$querylike], '', 'id, fullname');
    foreach ($records as $rec) {
        add_result($results, $rec->id, $rec->fullname, 'Course', 'i/course',
            new moodle_url('/course/view.php', ['id' => $rec->id]));
    }
}

/** 3. Activity name */
// activity name search (works on MySQL / Moodle 5)
if (in_array('activity_name', $areas)) {
    // build query as UNIONs; each SELECT returns the same 4 columns
    $sql = "
        SELECT cm.id AS cmid, cm.course AS courseid, m.name AS modname, inst.name AS instance_name
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        JOIN {assign} inst ON (m.name = 'assign' AND inst.id = cm.instance AND inst.name LIKE ?)

        UNION ALL

        SELECT cm.id AS cmid, cm.course AS courseid, m.name AS modname, inst.name AS instance_name
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        JOIN {quiz} inst ON (m.name = 'quiz' AND inst.id = cm.instance AND inst.name LIKE ?)

        UNION ALL

        SELECT cm.id AS cmid, cm.course AS courseid, m.name AS modname, inst.name AS instance_name
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        JOIN {page} inst ON (m.name = 'page' AND inst.id = cm.instance AND inst.name LIKE ?)

        UNION ALL

        SELECT cm.id AS cmid, cm.course AS courseid, m.name AS modname, inst.name AS instance_name
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        JOIN {forum} inst ON (m.name = 'forum' AND inst.id = cm.instance AND inst.name LIKE ?)

        UNION ALL

        SELECT cm.id AS cmid, cm.course AS courseid, m.name AS modname, inst.name AS instance_name
        FROM {course_modules} cm
        JOIN {modules} m ON m.id = cm.module
        JOIN {book} inst ON (m.name = 'book' AND inst.id = cm.instance AND inst.name LIKE ?)
        LIMIT 200
    ";

    // one param for each '?' in the SQL (5 SELECTs => 5 params)
    $params = [$querylike, $querylike, $querylike, $querylike, $querylike];

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $rec) {
        // CM id is the identifier for the module link
        $modname = $rec->modname;
        $title = $rec->instance_name;
        $url = new moodle_url('/mod/' . $modname . '/view.php', ['id' => $rec->cmid]);
        add_result($results, $rec->cmid, $title, ucfirst($modname), 'i/' . $modname, $url);
    }
    $rs->close();
}



/** 4. Activity description */
if (in_array('activity_desc', $areas)) {
    // Limited example: only for pages and assignments (extend for others)
    $sql = "SELECT cm.id AS cmid, m.name AS modname, inst.name, c.id AS courseid
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            JOIN {course} c ON c.id = cm.course
            JOIN {page} inst ON (m.name = 'page' AND inst.id = cm.instance AND inst.content LIKE :search1)
            WHERE inst.id IS NOT NULL
            UNION
            SELECT cm.id AS cmid, m.name AS modname, inst.name, c.id AS courseid
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            JOIN {course} c ON c.id = cm.course
            JOIN {assign} inst ON (m.name = 'assign' AND inst.id = cm.instance AND inst.intro LIKE :search2)
            WHERE inst.id IS NOT NULL";
    $params = ['search1' => $querylike, 'search2' => $querylike];
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $rec) {
        add_result($results, $rec->cmid, $rec->name, ucfirst($rec->modname), 'icon',
            new moodle_url('/mod/' . $rec->modname . '/view.php', ['id' => $rec->cmid]));
    }
    $rs->close();
}

/** 5. Course tag */
if (in_array('course_tag', $areas)) {
    $sql = "SELECT t.id AS tagid, c.id AS courseid, c.fullname
            FROM {tag_instance} ti
            JOIN {tag} t ON t.id = ti.tagid
            JOIN {course} c ON c.id = ti.itemid
            WHERE ti.itemtype = 'course' AND t.name LIKE :search";
    $params = ['search' => $querylike];
    $records = $DB->get_records_sql($sql, $params);
    foreach ($records as $rec) {
        add_result($results, $rec->courseid, $rec->fullname, 'Course', 'i/course',
            new moodle_url('/course/view.php', ['id' => $rec->courseid]));
    }
}

/** 6. Activity tag */
if (in_array('activity_tag', $areas)) {
    $sql = "SELECT cm.id AS cmid, m.name AS modname, inst.name
            FROM {tag_instance} ti
            JOIN {tag} t ON t.id = ti.tagid
            JOIN {course_modules} cm ON cm.id = ti.itemid
            JOIN {modules} m ON m.id = cm.module
            JOIN (
                SELECT id, name FROM {assign}
                UNION ALL SELECT id, name FROM {quiz}
                UNION ALL SELECT id, name FROM {page}
                UNION ALL SELECT id, name FROM {forum}
                UNION ALL SELECT id, name FROM {book}
            ) inst ON inst.id = cm.instance
            WHERE ti.itemtype = 'course_modules' AND t.name LIKE :search";
    $params = ['search' => $querylike];
    $records = $DB->get_records_sql($sql, $params);
    foreach ($records as $rec) {
        add_result($results, $rec->cmid, $rec->name, ucfirst($rec->modname), 'icon',
            new moodle_url('/mod/' . $rec->modname . '/view.php', ['id' => $rec->cmid]));
    }
}

/** 7. Category name */
if (in_array('category_name', $areas)) {
    $records = $DB->get_records_select('course_categories', 'name LIKE ?', [$querylike], '', 'id, name');
    foreach ($records as $rec) {
        add_result($results, $rec->id, $rec->name, 'Category', 'i/category',
            new moodle_url('/course/index.php', ['categoryid' => $rec->id]));
    }
}

/** 8. Category description */
if (in_array('category_desc', $areas)) {
    $records = $DB->get_records_select('course_categories', 'description LIKE ?', [$querylike], '', 'id, name');
    foreach ($records as $rec) {
        add_result($results, $rec->id, $rec->name, 'Category', 'i/category',
            new moodle_url('/course/index.php', ['categoryid' => $rec->id]));
    }
}

/** Output results */
/** Output results */
if (empty($results)) {
    echo html_writer::div(
        $OUTPUT->pix_icon('i/search', '') . ' ' . get_string('nosearchresults', 'local_navsearch'),
        'alert alert-warning d-flex align-items-center'
    );
    exit;
}


$output = html_writer::start_div('navsearch-results list-group');
$count = 0;


foreach ($results as $res) {
    if ($count >= 10) break;

	$result_type = strtolower($res['type']);


	if($result_type == 'course' || $result_type == 'category') {
		$icon = $OUTPUT->pix_icon($res['icon'], '', '', ['class' => 'mr-2']);
	} else {
	
	$component = 'mod_' . strtolower($res['type']);
        $res['icon'] = ['icon' => 'icon', 'component' => $component];
        $icon = $OUTPUT->pix_icon($res['icon']['icon'], '', $res['icon']['component'], ['class' => 'mr-2']);
	}

/*
	$component = 'mod_' . strtolower($res['type']);
        $res['icon'] = ['icon' => 'icon', 'component' => $component];
        $icon = $OUTPUT->pix_icon($res['icon']['icon'], '', $res['icon']['component'], ['class' => 'mr-2']);

*/


    $label = html_writer::span($res['name'], 'font-weight-bold fnsize');
    $type = html_writer::tag('small', " ({$res['type']})", ['class' => 'text-muted fnsize']);

    $output .= html_writer::link(
        $res['url'],
        $icon . $label . $type,
        ['class' => 'list-group-item list-group-item-action d-flex align-items-center navsearchresult-pad']
    );

    $count++;
}

$output .= html_writer::end_div();
echo $output;
