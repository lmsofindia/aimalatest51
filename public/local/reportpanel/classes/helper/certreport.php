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
 * Certificate reporting (mod_customcert): issued counts per certificate in a course
 * (full mode), and the current user's own issued certificates (self mode).
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certreport {

    /**
     * Is the certificate activity available?
     *
     * @return bool
     */
    public static function available(): bool {
        return catalogue::mod_installed('customcert');
    }

    /**
     * Per-certificate issued counts for a course.
     *
     * @param int $courseid
     * @return array row contexts for cert_full.mustache
     */
    public static function course_cert_stats(int $courseid): array {
        global $DB;
        if (!self::available()) {
            return [];
        }

        $sql = "SELECT cc.id, cc.name, cm.id AS cmid,
                       (SELECT COUNT(ci.id) FROM {customcert_issues} ci
                         WHERE ci.customcertid = cc.id) AS issued
                  FROM {customcert} cc
                  JOIN {course_modules} cm ON cm.instance = cc.id AND cm.course = cc.course
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'customcert'
                 WHERE cc.course = :courseid AND cm.deletioninprogress = 0
              ORDER BY cc.name ASC";
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        $rows = [];
        foreach ($records as $r) {
            $viewurl = new \moodle_url('/mod/customcert/view.php', ['id' => (int)$r->cmid]);
            $rows[] = [
                'name' => format_string($r->name),
                'url' => $viewurl->out(false),
                'issued' => (int)$r->issued,
            ];
        }
        return $rows;
    }

    /**
     * The current user's own issued certificates across courses.
     *
     * @param int $userid
     * @return array row contexts for cert_self.mustache
     */
    public static function own_certificates(int $userid): array {
        global $DB;
        if (!self::available()) {
            return [];
        }

        $sql = "SELECT ci.id, ci.timecreated, cc.name, c.fullname AS coursename, cm.id AS cmid
                  FROM {customcert_issues} ci
                  JOIN {customcert} cc ON cc.id = ci.customcertid
                  JOIN {course} c ON c.id = cc.course
                  JOIN {course_modules} cm ON cm.instance = cc.id AND cm.course = cc.course
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'customcert'
                 WHERE ci.userid = :userid AND cm.deletioninprogress = 0
              ORDER BY ci.timecreated DESC";
        $records = $DB->get_records_sql($sql, ['userid' => $userid]);

        $dateformat = get_string('strftimedate', 'langconfig');
        $rows = [];
        foreach ($records as $r) {
            $viewurl = new \moodle_url('/mod/customcert/view.php', ['id' => (int)$r->cmid]);
            $rows[] = [
                'name' => format_string($r->name),
                'url' => $viewurl->out(false),
                'coursename' => format_string($r->coursename),
                'issuedate' => $r->timecreated
                    ? userdate((int)$r->timecreated, $dateformat) : '—',
            ];
        }
        return $rows;
    }
}
