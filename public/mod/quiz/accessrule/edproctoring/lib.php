<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Plugin lib — pluginfile hook for serving snapshots + base images.
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve files stored by quizaccess_edproctoring.
 *
 * @param \stdClass $course      course record
 * @param \stdClass $cm          course module record
 * @param \context  $context     file context
 * @param string    $filearea    file area name ('snapshots' or 'base_images')
 * @param array     $args        remaining URL path segments
 * @param bool      $forcedownload
 * @param array     $options
 * @return bool|void
 */
function quizaccess_edproctoring_pluginfile($course, $cm, $context, $filearea,
        $args, $forcedownload, array $options = []) {

    global $DB, $USER;

    require_login($course, false, $cm);

    if ($filearea !== 'snapshots' && $filearea !== 'base_images') {
        send_file_not_found();
    }

    // URL format: /pluginfile.php/{contextid}/quizaccess_edproctoring/{filearea}/{itemid}/{userid}/{filename}
    // For snapshots the itemid is the proctoring SESSION id; filepath is "/{userid}/".
    $itemid = (int) array_shift($args);

    // Access control.
    if ($filearea === 'snapshots') {
        // Students can view own snapshots; teachers/admins view all.
        if (!has_capability('quizaccess/edproctoring:viewreport', $context)) {
            $session = $DB->get_record('quizaccess_edproctoring_session', ['id' => $itemid]);
            if (!$session || $session->userid != $USER->id) {
                send_file_not_found();
            }
        }
    } else {
        // Base image access: own image OR teacher/admin.
        if (!has_capability('quizaccess/edproctoring:uploadbaseimage', $context)) {
            $img = $DB->get_record('quizaccess_edproctoring_baseimg', ['id' => $itemid]);
            if (!$img || $img->userid != $USER->id) {
                send_file_not_found();
            }
        }
    }

    $fs = get_file_storage();
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $file = $fs->get_file($context->id, 'quizaccess_edproctoring', $filearea,
        $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
