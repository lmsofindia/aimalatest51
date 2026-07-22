<?php
/**
 * Per-user progress read/update + completion recompute.
 *
 * @package   mod_edztrackvideo
 */

namespace mod_edztrackvideo;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

use completion_info;

class progress_manager {

    /** @var \stdClass */
    protected $edztrackvideo;
    /** @var \stdClass|\cm_info */
    protected $cm;
    /** @var int */
    protected $courseid;
    /** @var \moodle_database */
    protected $db;

    public function __construct($edztrackvideo, $cm) {
        global $DB;
        $this->edztrackvideo = $edztrackvideo;
        $this->cm = $cm;
        $this->courseid = $cm->course;
        $this->db = $DB;
    }

    /**
     * Get (or synthesise an empty) progress record for a user.
     *
     * @param int $userid
     * @return \stdClass
     */
    public function get_progress_for_user($userid) {
        $rec = $this->db->get_record('edztrackvideo_progress', [
            'edztrackvideoid' => $this->edztrackvideo->id,
            'userid' => $userid,
        ]);
        if (!$rec) {
            $rec = new \stdClass();
            $rec->edztrackvideoid = $this->edztrackvideo->id;
            $rec->userid = $userid;
            $rec->maxwatched = 0;
            $rec->last_position = 0;
            $rec->duration = 0;
            $rec->first_view_done = 0;
            $rec->completed = 0;
            $rec->dismissed = 0;
            $rec->lastupdate = 0;
            $rec->last_viewed_on = null;
        }
        return $rec;
    }

    /**
     * Update progress for a user and recompute completion.
     *
     * @param array $data userid, current_time, duration
     * @return \stdClass updated record
     */
    public function update_progress($data) {
        $userid = (int)$data['userid'];
        $currenttime = (float)$data['current_time'];
        $duration = (float)$data['duration'];
        $now = time();

        $rec = $this->db->get_record('edztrackvideo_progress', [
            'edztrackvideoid' => $this->edztrackvideo->id,
            'userid' => $userid,
        ]);

        if (!$rec) {
            $rec = new \stdClass();
            $rec->edztrackvideoid = $this->edztrackvideo->id;
            $rec->userid = $userid;
            $rec->maxwatched = $currenttime;
            $rec->last_position = $currenttime;
            $rec->duration = $duration;
            $rec->first_view_done = 0;
            $rec->completed = 0;
            $rec->dismissed = 0;
            $rec->lastupdate = $now;
            $rec->last_viewed_on = $now;
            $rec->id = $this->db->insert_record('edztrackvideo_progress', $rec);
        } else {
            if ($currenttime > $rec->maxwatched) {
                $rec->maxwatched = $currenttime;
            }
            $rec->last_position = $currenttime;
            if ($duration > 0) {
                $rec->duration = $duration;
            }
            $rec->lastupdate = $now;
            $rec->last_viewed_on = $now;
            $this->db->update_record('edztrackvideo_progress', $rec);
        }

        // Update the internal completed mirror when the watch threshold is met.
        $threshold = (int)$this->edztrackvideo->completion_threshold;
        if ($rec->duration > 0) {
            $percent = ($rec->maxwatched / $rec->duration) * 100;
            if ($percent >= $threshold && empty($rec->completed)) {
                $rec->completed = 1;
                $rec->first_view_done = 1;
                $this->db->update_record('edztrackvideo_progress', $rec);
            }
        }

        // Let the Completion API re-evaluate via our custom rule (single source of truth).
        $cm = get_coursemodule_from_instance('edztrackvideo', $this->edztrackvideo->id);
        if ($cm) {
            $course = get_course($cm->course);
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
            }
        }

        return $rec;
    }

    /**
     * Record that the user dismissed the resume prompt.
     *
     * @param int $userid
     */
    public function mark_dismissed($userid) {
        $rec = $this->db->get_record('edztrackvideo_progress', [
            'edztrackvideoid' => $this->edztrackvideo->id,
            'userid' => $userid,
        ]);
        if ($rec) {
            $rec->dismissed = 1;
            $rec->lastupdate = time();
            $rec->last_viewed_on = $rec->lastupdate;
            $this->db->update_record('edztrackvideo_progress', $rec);
        }
    }
}
