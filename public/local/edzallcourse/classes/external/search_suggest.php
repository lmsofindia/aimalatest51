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
 * External API: lightweight typeahead suggestions (courses + categories).
 *
 * Powers the "search programmes" box in the theme frontpage hero. Returns a
 * short, flat JSON list of matching course categories and courses with their
 * display names and destination URLs — deliberately much lighter than
 * {@see get_view}, which renders full catalogue HTML regions.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzallcourse\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use context_system;
use core_course_category;
use moodle_url;

/**
 * Return a short list of course + category suggestions for a search term.
 */
class search_suggest extends external_api {

    /** @var int Hard ceiling on suggestions returned, regardless of requested limit. */
    const MAX_LIMIT = 12;

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'q'     => new external_value(PARAM_TEXT, 'Search text'),
            'limit' => new external_value(PARAM_INT, 'Max total suggestions', VALUE_DEFAULT, 8),
        ]);
    }

    /**
     * Build the suggestion list.
     *
     * @param string $q
     * @param int $limit
     * @return array
     */
    public static function execute(string $q = '', int $limit = 8): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'q' => $q, 'limit' => $limit,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        // Public catalogue — no capability gate; only visible courses are returned.

        if (!get_config('local_edzallcourse', 'enable')) {
            return ['items' => []];
        }

        $q = trim($params['q']);
        $limit = min(self::MAX_LIMIT, max(1, (int)$params['limit']));

        // Require at least two characters — one-character searches are too noisy.
        if (\core_text::strlen($q) < 2) {
            return ['items' => []];
        }

        $items = [];
        $like  = '%' . $DB->sql_like_escape($q) . '%';

        // Split the budget: categories take up to a third, courses the rest.
        $catlimit    = max(2, (int)floor($limit / 3));
        $courselimit = $limit; // Fetch generously; the combined list is trimmed at the end.

        // --- Categories -------------------------------------------------------
        $catwhere  = [$DB->sql_like('cc.name', ':cn', false)];
        $catparams = ['cn' => $like];
        if (!has_capability('moodle/category:viewhiddencategories', $context)) {
            $catwhere[] = 'cc.visible = 1';
        }
        $catsql = "SELECT cc.id, cc.name, cc.idnumber
                     FROM {course_categories} cc
                    WHERE " . implode(' AND ', $catwhere) . "
                 ORDER BY cc.name ASC";
        $cats = $DB->get_records_sql($catsql, $catparams, 0, $catlimit);
        foreach ($cats as $cat) {
            $items[] = [
                'type' => 'category',
                'id'   => (int)$cat->id,
                'name' => format_string($cat->name, true, ['context' => $context, 'escape' => false]),
                'url'  => (new moodle_url('/local/edzallcourse/index.php', ['category' => (int)$cat->id]))->out(false),
                'meta' => get_string('category'),
            ];
        }

        // --- Courses ----------------------------------------------------------
        $coursewhere  = [
            'c.id <> :siteid',
            '(' . $DB->sql_like('c.fullname', ':q1', false) . ' OR '
                . $DB->sql_like('c.shortname', ':q2', false) . ')',
        ];
        $courseparams = ['siteid' => SITEID, 'q1' => $like, 'q2' => $like];
        if (!has_capability('moodle/course:viewhiddencourses', $context)) {
            $coursewhere[] = 'c.visible = 1';
        }
        $coursesql = "SELECT c.id, c.fullname, c.shortname, c.category
                        FROM {course} c
                       WHERE " . implode(' AND ', $coursewhere) . "
                    ORDER BY c.fullname ASC";
        $courses = $DB->get_records_sql($coursesql, $courseparams, 0, $courselimit);
        foreach ($courses as $course) {
            $catname = '';
            if (!empty($course->category)) {
                $cat = core_course_category::get((int)$course->category, IGNORE_MISSING, true);
                if ($cat) {
                    $catname = $cat->get_formatted_name();
                }
            }
            $items[] = [
                'type' => 'course',
                'id'   => (int)$course->id,
                'name' => format_string($course->fullname, true, ['context' => $context, 'escape' => false]),
                'url'  => (new moodle_url('/course/view.php', ['id' => (int)$course->id]))->out(false),
                'meta' => $catname,
            ];
        }

        // Trim the combined list to the requested overall limit (categories first).
        $items = array_slice($items, 0, $limit);

        return ['items' => $items];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'type' => new external_value(PARAM_ALPHA, 'Result type: course|category'),
                    'id'   => new external_value(PARAM_INT, 'Course or category id'),
                    'name' => new external_value(PARAM_TEXT, 'Display name'),
                    'url'  => new external_value(PARAM_URL, 'Destination URL'),
                    'meta' => new external_value(PARAM_TEXT, 'Secondary label (category name or type word)'),
                ])
            ),
        ]);
    }
}
