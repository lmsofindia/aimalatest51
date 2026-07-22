<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves academic vs corporate wording from plugin config.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class terminology {

    /**
     * Current terminology mode.
     *
     * @return string 'academic' or 'corporate'
     */
    public static function mode(): string {
        $mode = get_config('local_edzfaculty', 'terminology');
        return ($mode === 'corporate') ? 'corporate' : 'academic';
    }

    /**
     * Resolve a term key to the configured wording, e.g. term('students').
     *
     * @param string $key base key (student, students, section, sections, course, courses)
     * @return string
     */
    public static function term(string $key): string {
        return get_string($key . '_' . self::mode(), 'local_edzfaculty');
    }

    /**
     * Export the label set for templates / JS.
     *
     * @return array
     */
    public static function labels(): array {
        return [
            'student'  => self::term('student'),
            'students' => self::term('students'),
            'section'  => self::term('section'),
            'sections' => self::term('sections'),
            'course'   => self::term('course'),
            'courses'  => self::term('courses'),
        ];
    }
}
