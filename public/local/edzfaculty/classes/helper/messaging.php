<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\helper;

use core_user;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Sends a one-click "nudge" from a teacher to an at-risk student.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class messaging {

    /**
     * Build the default nudge body for a student in a course.
     *
     * @param int $studentid
     * @param int $teacherid
     * @param int $courseid
     * @return string
     */
    public static function default_body(int $studentid, int $teacherid, int $courseid): string {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], 'fullname', MUST_EXIST);
        $a = (object)[
            'student' => fullname(core_user::get_user($studentid)),
            'teacher' => fullname(core_user::get_user($teacherid)),
            'course'  => format_string($course->fullname),
        ];
        return get_string('nudgedefault', 'local_edzfaculty', $a);
    }

    /**
     * Send the nudge as a Moodle notification from teacher to student.
     *
     * @param int $teacherid sender
     * @param int $studentid recipient
     * @param int $courseid course context
     * @param string $body message text
     * @return int|false message id or false
     */
    public static function send(int $teacherid, int $studentid, int $courseid, string $body) {
        $body = trim($body);
        if ($body === '') {
            $body = self::default_body($studentid, $teacherid, $courseid);
        }

        $message = new \core\message\message();
        $message->component         = 'local_edzfaculty';
        $message->name              = 'nudge';
        $message->userfrom          = core_user::get_user($teacherid);
        $message->userto            = core_user::get_user($studentid);
        $message->subject           = get_string('nudgesubject', 'local_edzfaculty');
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = nl2br(s($body));
        $message->smallmessage      = $body;
        $message->notification      = 1;
        $message->courseid          = $courseid;
        $message->contexturl        = (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
        $message->contexturlname    = get_string('course');

        return message_send($message);
    }
}
