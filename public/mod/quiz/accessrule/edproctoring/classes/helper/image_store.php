<?php
// This file is part of Moodle - http://moodle.org/

namespace quizaccess_edproctoring\helper;

/**
 * Moodledata file storage wrapper for proctoring snapshots and base images.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class image_store {

    const FILEAREA_SNAPSHOTS  = 'snapshots';
    const FILEAREA_BASEIMAGES = 'base_images';
    const COMPONENT           = 'quizaccess_edproctoring';

    /**
     * Store a base64-encoded PNG snapshot in Moodledata.
     *
     * @param int    $contextid  module context id (cmid context)
     * @param int    $sessionid  proctoring session id (used as itemid)
     * @param int    $userid     student user id
     * @param string $base64data raw base64 string (no data: prefix)
     * @param string $capturetype 'interval'|'burst'|'prequiz'
     * @return string pathnamehash of stored file
     */
    public static function store_snapshot(int $contextid, int $sessionid,
            int $userid, string $base64data, string $capturetype = 'interval'): string {

        $imagedata = base64_decode($base64data, true);
        if ($imagedata === false) {
            throw new \moodle_exception('invalidimagedata', 'quizaccess_edproctoring');
        }

        $fs       = get_file_storage();
        $filename = sprintf('snap_%s_%d.png', $capturetype, time());

        $fileinfo = [
            'contextid' => $contextid,
            'component' => self::COMPONENT,
            'filearea'  => self::FILEAREA_SNAPSHOTS,
            'itemid'    => $sessionid,
            'filepath'  => "/$userid/",
            'filename'  => $filename,
        ];

        // Delete any existing file with same name to avoid duplicates.
        $existing = $fs->get_file(
            $contextid, self::COMPONENT, self::FILEAREA_SNAPSHOTS,
            $sessionid, "/$userid/", $filename
        );
        if ($existing) {
            $existing->delete();
        }

        $file = $fs->create_file_from_string($fileinfo, $imagedata);

        return $file->get_pathnamehash();
    }

    /**
     * Get a served URL for a snapshot file by pathnamehash.
     *
     * @param string $pathnamehash
     * @return \moodle_url|null
     */
    public static function get_snapshot_url(string $pathnamehash): ?\moodle_url {
        $fs   = get_file_storage();
        $file = $fs->get_file_by_hash($pathnamehash);
        if (!$file) {
            return null;
        }
        return \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            self::COMPONENT,
            self::FILEAREA_SNAPSHOTS,
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }

    /**
     * Delete all snapshot files for a session.
     *
     * @param int $contextid
     * @param int $sessionid
     */
    public static function delete_session_files(int $contextid, int $sessionid): void {
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, self::COMPONENT, self::FILEAREA_SNAPSHOTS, $sessionid);
    }

    /**
     * Delete all files (snapshots + base images) for a user — GDPR erasure.
     *
     * @param int $userid
     */
    public static function delete_user_files(int $userid): void {
        global $DB;

        $fs = get_file_storage();

        // Delete all snapshot files across all session/context combos for this user.
        $sessions = $DB->get_records('quizaccess_edproctoring_session', ['userid' => $userid]);
        foreach ($sessions as $session) {
            $context = \context_module::instance($session->cmid);
            $fs->delete_area_files($context->id, self::COMPONENT,
                self::FILEAREA_SNAPSHOTS, $session->id);
        }

        // Delete base images stored in user context.
        $usercontext = \context_user::instance($userid);
        $fs->delete_area_files($usercontext->id, self::COMPONENT, self::FILEAREA_BASEIMAGES);
    }

    /**
     * Delete snapshot files older than a given Unix timestamp site-wide.
     * Used by the cleanup scheduled task.
     *
     * @param int $before Unix timestamp — files older than this are deleted
     * @return int number of files deleted
     */
    public static function delete_snapshots_older_than(int $before): int {
        global $DB;

        $snaps = $DB->get_records_select(
            'quizaccess_edproctoring_snap',
            'timecaptured < :before',
            ['before' => $before],
            '',
            'id, sessionid, pathnamehash'
        );

        if (empty($snaps)) {
            return 0;
        }

        $fs    = get_file_storage();
        $count = 0;

        foreach ($snaps as $snap) {
            if (!empty($snap->pathnamehash)) {
                $file = $fs->get_file_by_hash($snap->pathnamehash);
                if ($file) {
                    $file->delete();
                    $count++;
                }
            }
        }

        // Delete snap records (keep violation records for audit trail).
        list($in, $params) = $DB->get_in_or_equal(array_keys($snaps));
        $DB->delete_records_select('quizaccess_edproctoring_snap', "id $in", $params);

        return $count;
    }
}
