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

namespace local_reportpanel\helper;

/**
 * Catalogue lookups: categories, courses in a category, and activity instances
 * (quizzes / certificates) in a course. Used by the full-mode pickers.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue {

    /**
     * Categories the current user can see, as id => indented name.
     *
     * @return array
     */
    public static function categories(): array {
        return \core_course_category::make_categories_list();
    }

    /**
     * Visible courses in a category, ordered by full name.
     *
     * @param int $categoryid
     * @return array list of {id, fullname, shortname}
     */
    public static function courses_in_category(int $categoryid): array {
        if ($categoryid <= 0) {
            return [];
        }
        $courses = get_courses($categoryid, 'c.fullname ASC',
            'c.id, c.fullname, c.shortname, c.visible');
        $out = [];
        foreach ($courses as $c) {
            if ((int)$c->id === SITEID) {
                continue;
            }
            $out[] = [
                'id' => (int)$c->id,
                'fullname' => format_string($c->fullname),
                'shortname' => format_string($c->shortname),
            ];
        }
        return $out;
    }

    /**
     * Activity instances of a given module type in a course.
     *
     * @param int $courseid
     * @param string $modname e.g. 'quiz' or 'customcert'
     * @return \cm_info[] indexed by instance id, ordered by name
     */
    public static function instances_in_course(int $courseid, string $modname): array {
        if ($courseid <= 0) {
            return [];
        }
        $modinfo = get_fast_modinfo($courseid);
        $instances = [];
        foreach ($modinfo->get_instances_of($modname) as $cm) {
            if ($cm->deletioninprogress) {
                continue;
            }
            $instances[(int)$cm->instance] = $cm;
        }
        \core_collator::asort_objects_by_property($instances, 'name');
        return $instances;
    }

    /**
     * Is a module type installed and available on this site?
     *
     * @param string $modname
     * @return bool
     */
    public static function mod_installed(string $modname): bool {
        global $DB;
        return (bool)$DB->record_exists('modules', ['name' => $modname]);
    }

    /**
     * Get a course record or null.
     *
     * @param int $courseid
     * @return \stdClass|null
     */
    public static function course(int $courseid): ?\stdClass {
        global $DB;
        if ($courseid <= 0) {
            return null;
        }
        return $DB->get_record('course', ['id' => $courseid]) ?: null;
    }
}
