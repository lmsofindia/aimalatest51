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
 * Brand terminology helper for local_edzallcourse.
 *
 * Lets AIMA / GAIL instances override a few words (e.g. "Faculty" vs
 * "Trainer") via config, falling back to the language pack.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzallcourse\helper;

/**
 * Resolve brand-specific wording.
 */
class terminology {

    /**
     * Resolve a term: config override `term_<key>` wins, else the lang string `term_<key>`.
     *
     * @param string $key e.g. 'teacher', 'category'
     * @return string
     */
    public static function get(string $key): string {
        $override = get_config('local_edzallcourse', 'term_' . $key);
        if ($override !== false && trim((string)$override) !== '') {
            return $override;
        }
        return get_string('term_' . $key, 'local_edzallcourse');
    }

    /**
     * Label used for the course contact on cards.
     *
     * @return string
     */
    public static function teacher_label(): string {
        return self::get('teacher');
    }
}
