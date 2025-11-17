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
 * Helper functions for EDZ Category View
 *
 * @package    local_edzcategoryview
 * @copyright  2025 YOUR NAME
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get all subcategory IDs recursively.
 *
 * @param int $parentid Category ID
 * @return array
 */
function local_edzcategoryview_get_all_subcategory_ids($parentid) {
    global $DB;
    $subcats = $DB->get_records('course_categories', ['parent' => $parentid], '', 'id');
    $ids = [];

    foreach ($subcats as $subcat) {
        $ids[] = $subcat->id;
        $ids = array_merge($ids, local_edzcategoryview_get_all_subcategory_ids($subcat->id));
    }

    return $ids;
}

/**
 * Recursive function to get category tree with course counts.
 *
 * @param int $parentid Parent category ID.
 * @param int $excludeid Category ID to exclude (optional).
 * @return array
 */
function local_edzcategoryview_get_category_tree($parentid = 0, $excludeid = 0) {
    global $DB;

    // Fetch categories under the parent.
    $categories = $DB->get_records('course_categories', ['parent' => $parentid], 'name ASC');
    $result = [];

    foreach ($categories as $cat) {
        if ($excludeid && $cat->id == $excludeid) {
            continue; // Skip excluded category.
        }

        // Count only direct courses in this category.
        $directcourses = $DB->count_records('course', ['category' => $cat->id]);

        // Collect this category + all subcategories.
        $allcatids = local_edzcategoryview_get_all_subcategory_ids($cat->id);
        $allcatids[] = $cat->id;

        // Count all courses including subcategories.
        list($insql, $params) = $DB->get_in_or_equal($allcatids, SQL_PARAMS_NAMED);
        $totalcourses = (int)$DB->count_records_select('course', "category $insql", $params);

        // Recursive call for child categories.
        $children = local_edzcategoryview_get_category_tree($cat->id, $excludeid);

        $result[] = [
            'id' => $cat->id,
            'name' => $cat->name,
            'url' => (new moodle_url('/course/index.php', ['categoryid' => $cat->id]))->out(false),
            'coursecount' => $directcourses,   // Only this category’s courses.
            'totalcourses' => $totalcourses,   // Including all subcategories.
            'subcategories' => $children
        ];
    }

    return $result;
}
