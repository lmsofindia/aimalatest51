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

/**.
 * Services
 * @package    local_edzallcourse
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */

namespace local_edzallcourse\local;
use core_course_category;

defined('MOODLE_INTERNAL') || die();



class service
{

    /**
     * Return enabled custom field shortnames → metadata (label, id, field_controller).
     * @return array shortname => ['id'=>int, 'label'=>string, 'controller'=>field_controller]
     */
    public static function enabled_fields(): array
    {
        global $DB;
        $result = [];

        // Check if plugin is enabled.
        if (!get_config('local_edzallcourse', 'enable')) {
            return $result;
        }

         // Course category filter
          $hascategories = $DB->record_exists('course_categories', ['parent' => 0]);
          $hascourses    = $DB->record_exists('course', ['visible' => 1]);

    if ($hascategories && $hascourses) {
        $result['course_category'] = [
            'id' => 0,   // fake ID to avoid customfield query
            'label' => 'Category',
            'controller' => null,
        ];
    }

        return $result;
    }

public static function options_for_enabled_fields(): array {
    global $DB;

    $fields = self::enabled_fields();
    $out = [];
    // echo "<pre>";
    // print_r($fields); die();

    // -------------------------------
    // 2. Course Category Options
    // -------------------------------
    if (isset($fields['course_category'])) {
        $categories = $DB->get_records(
            'course_categories',
            ['parent' => 0],  // Only top-level categories
            'sortorder ASC',
            'id, name'
        );
        $out['course_category'] = [];

        foreach ($categories as $cat) {
            $out['course_category'][] = [
                'value' => $cat->id,
                'label' => $cat->name
            ];
        }
    }
    // echo "<pre>";
    // print_r($out); die();
    return $out;
}


    /**
     * Search/filter courses and return array ready for templates.
     * @param string $q search text
     * @param array $filters ['shortname'=>'value', ...] (empty values ignored)
     * @param int $limit
     * @param int $offset
     * @return array of courses (id, fullname, shortname, summary, summaryformat, courseimage, url)
     */
    public static function search_courses(string $q = '', array $filters = [], int $limit = 24, int $offset = 0, string $sort = 'popular'): array {
    global $DB, $CFG, $OUTPUT;

    $wheres = ["c.visible = 1", "c.id <> 1"];
    $params = [];

    if ($q !== '') {
        $wheres[] = "(c.fullname LIKE :q1 OR c.shortname LIKE :q2)";
        $params['q1'] = "%{$q}%";
        $params['q2'] = "%{$q}%";
    }

    // 🔹 Handle customfield filters (already implemented)
    $i = 0;
    foreach ($filters as $shortname => $value) {
        if (empty($value)) {
            continue;
        }
        $values = is_array($value) ? $value : [$value];
        $i++;

            // Added by Rashid for category filter as on 17-09-25
            if ($shortname === 'course_category') {
                list($insql, $inparams) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED, "cat{$i}");
                $wheres[] = "c.category {$insql}";
                $params += $inparams;
                continue;
            }


        list($insql, $inparams) = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED, "val{$i}");

        $wheres[] = "EXISTS (
            SELECT 1
              FROM {customfield_data} d{$i}
              JOIN {customfield_field} f{$i} ON f{$i}.id = d{$i}.fieldid
             WHERE d{$i}.instanceid = c.id
               AND f{$i}.shortname = :sn{$i}
               AND d{$i}.value {$insql}
        )";

        $params["sn{$i}"] = $shortname;
        $params += $inparams;
    }

    $where = $wheres ? ('WHERE ' . implode(' AND ', $wheres)) : '';

    // 🔹 Decide ORDER BY based on $sort
    switch ($sort) {
        case 'created':
            $orderby = 'c.timecreated DESC';
            break;

        case 'duration':
            // if courses have startdate & enddate
            $orderby = '(c.enddate - c.startdate) ASC';
            break;

        case 'popular':
        default:
            // Use enrolments count as popularity
            $orderby = 'enrolledusers DESC';
            break;
    }

    // 🔹 Build SQL
    $sql = "SELECT c.id,
                   c.fullname,
                   c.shortname,
                   c.summary,
                   c.summaryformat,
                   c.timecreated,
                   c.startdate,
                   c.enddate,
                   COUNT(ue.id) AS enrolledusers
              FROM {course} c
         LEFT JOIN {enrol} e ON e.courseid = c.id
         LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                $where
          GROUP BY c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.timecreated, c.startdate, c.enddate
          ORDER BY $orderby";

    $courses = $DB->get_records_sql($sql, $params, $offset, $limit);

    // Attach image + URL
    $out = [];
    foreach ($courses as $c) {
        $context = \context_course::instance($c->id, IGNORE_MISSING);
        $imgurl = '';
        if ($context) {
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);
            if ($files) {
                $file = reset($files);
                $imgurl = file_encode_url(
                    "$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . '/' . $file->get_itemid() . $file->get_filepath() . $file->get_filename(),
                    false
                );
            }
        }
        $out[] = [
            'id' => $c->id,
            'fullname' => format_string($c->fullname),
            'shortname' => format_string($c->shortname),
            'summary' => format_text($c->summary, $c->summaryformat),
            'courseimage' => $imgurl,
            'url' => (new \moodle_url('/course/view.php', ['id' => $c->id]))->out(false),
        ];
    }
    return $out;
}
    /**
     * full category path like "Parent / Child / Subchild"
     *
     * @param int $categoryid
     * @return string
     */
    public static function get_category_full_path($categoryid) {
        $path = [];
        $category = core_course_category::get($categoryid);

        if (!$category) {
            return '';
        }

        while ($category) {
            $path[] = $category->name;
            if ($category->parent == 0) break;
            $category = core_course_category::get($category->parent);
        }

        return implode(' / ', array_reverse($path));
    }

/**
 * Full tree paths starting from a parent category ID
 *
 * @param int $parentcatid
 * @return array
 */
/**
 * Full tree paths starting from a parent category ID
 *
 * @param int $parentcatid
 * @return array
 */
// public static function get_parent_category_tree_paths($parentcatid) {

//     $result = [
//         'parent' => null,
//         'paths'  => []
//     ];

//     // Get parent category
//     $parent = core_course_category::get($parentcatid);
//     if (!$parent) {
//         return $result;
//     }

//     $categorydescription = format_text(
//     file_rewrite_pluginfile_urls(
//         $category->description,
//         'pluginfile.php',
//         $context->id,
//         'coursecat',
//         'description',
//         ''
//     ),
//     $category->descriptionformat,
//     [
//         'context' => $context,
//         'noclean' => true,
//         'trusttext' => true
//     ]
// );


//     // Parent info
//     $result['parent'] = [
//         'id' => $parent->id,
//         'name' => $parent->name,
//         'description' => $parent->description,
//         'visible' => (bool)$parent->visible,
//         'parent_id' => $parent->parent
//     ];

//     // Include the parent itself as first path
//     $paths = [
//         [
//             'id' => $parent->id,
//             'path' => self::get_category_full_path($parent->id)
//         ]
//     ];

//     // Get all children recursively with IDs
//     $childrenPaths = self::get_children_paths_with_ids($parent);

//     $result['paths'] = array_merge($paths, $childrenPaths);

//     return $result;
// }

    public static function get_parent_category_tree_paths($parentcatid) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $result = [
            'parent' => null,
            'paths'  => []
        ];

        // Get parent category
        $parent = \core_course_category::get($parentcatid, IGNORE_MISSING);
        if (!$parent) {
            return $result;
        }

        // Get the category context
        $context = \context_coursecat::instance($parentcatid);

        // Format and rewrite the description (with images)
        $categorydescription = format_text(
            \file_rewrite_pluginfile_urls(
                $parent->description,
                'pluginfile.php',
                $context->id,
                'coursecat',
                'description',
                ''
            ),
            $parent->descriptionformat,
            [
                'context' => $context,
                'noclean' => true,
                'trusttext' => true
            ]
        );

        // Parent info
        $result['parent'] = [
            'id' => $parent->id,
            'name' => $parent->get_formatted_name(),
            'description' => $categorydescription,
            'visible' => (bool)$parent->visible,
            'parent_id' => $parent->parent
        ];

        // Include the parent itself as first path
        $paths = [
            [
                'id' => $parent->id,
                'path' => self::get_category_full_path($parent->id)
            ]
        ];

        $childrenPaths = self::get_children_paths_with_ids($parent);

        $result['paths'] = array_merge($paths, $childrenPaths);

        return $result;
    }

/**
 * Recursive helper: fetch children paths under a given category object
 *
 * Returns array of ['id' => category_id, 'path' => full_path]
 *
 * @param core_course_category $category
 * @return array
 */
private static function get_children_paths_with_ids($category) {
    $paths = [];

    $children = $category->get_children();
    if (!$children) {
        return $paths;
    }

    foreach ($children as $child) {
        // Add this child with full path
        $paths[] = [
            'id' => $child->id,
            'path' => self::get_category_full_path($child->id)
        ];

        // Recurse into sub-children
        $paths = array_merge($paths, self::get_children_paths_with_ids($child));
    }

    return $paths;
}
    /**
     * Get all courses for a given category ID
     *
     * @param int $categoryid
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function get_courses_by_category(int $categoryid): array {
        global $DB, $CFG;

        // If no category is passed, return empty
        if (!$categoryid) {
            return [];
        }
 
        // Fetch courses in the category (excluding site course id=1)
        $sql = "SELECT c.id,
                    c.fullname,
                    c.shortname,
                    c.summary,
                    c.summaryformat,
                    c.timecreated,
                    c.startdate,
                    c.enddate,
                    COUNT(ue.id) AS enrolledusers
                FROM {course} c
            LEFT JOIN {enrol} e ON e.courseid = c.id
            LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE c.visible = 1
                AND c.id <> 1
                AND c.category = :catid
            GROUP BY c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.timecreated, c.startdate, c.enddate
            ORDER BY c.timecreated DESC";

        $params = ['catid' => $categoryid];

        // Fetch all courses
        $courses = $DB->get_records_sql($sql, $params);

        $out = [];
        foreach ($courses as $c) {
            $context = \context_course::instance($c->id, IGNORE_MISSING);
            $imgurl = '';

            // Get first course overview image
            if ($context) {
                $fs = get_file_storage();
                $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);
                if ($files) {
                    $file = reset($files);
                    $imgurl = file_encode_url(
                        "$CFG->wwwroot/pluginfile.php",
                        '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . '/' . $file->get_itemid() . $file->get_filepath() . $file->get_filename(),
                        false
                    );
                }
            }

            $out[] = [
                'id' => $c->id,
                'fullname' => format_string($c->fullname),
                'shortname' => format_string($c->shortname),
                'summary' => format_text($c->summary, $c->summaryformat),
                'courseimage' => $imgurl,
                'enrolledusers' => $c->enrolledusers,
                'description' => $description,
                'url' => (new \moodle_url('/course/view.php', ['id' => $c->id]))->out(false),
            ];
        }

        return $out;
    }


}
