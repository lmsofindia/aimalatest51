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
  * Helper functions for local_panelmenu plugin.
  *
  * @package    local_panelmenu
  * @category   helper
  * @author     Rashid
  * @copyright  2025 Rashid
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

namespace local_panelmenu;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Get all top-level categories with their subcategories.
     *
     * @return array Category tree.
     */
    public static function get_categories_with_subs(): array {
        global $DB;

        $categories = $DB->get_records('course_categories', null, 'parent ASC, sortorder ASC');
        $tree = [];

        // First, collect all top-level categories.
        foreach ($categories as $category) {
            if ((int)$category->parent === 0) {
                $tree[$category->id] = [
                    'name' => $category->name,
                    'subcategories' => []
                ];
            }
        }

        // Then, attach subcategories to their parents.
        foreach ($categories as $category) {
            if ((int)$category->parent !== 0 && isset($tree[$category->parent])) {
                $tree[$category->parent]['subcategories'][] = [
                    'name' => $category->name,
                    'url'  => new \moodle_url('/course/index.php', ['categoryid' => $category->id])
                ];
            }
        }

        return array_values($tree);
    }
}
