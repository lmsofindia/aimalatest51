<?php
/**
 * Streams a "Server file" source video with access control + HTTP range support.
 *
 * The file itself is identified by the activity instance (not a user-supplied path),
 * and is confined to the admin-configured base directory. Only users who can view
 * the activity may stream it.
 *
 * @package   mod_edztrackvideo
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/edztrackvideo/lib.php');

$id = required_param('id', PARAM_INT); // Course module id.

$cm = get_coursemodule_from_id('edztrackvideo', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$ed = $DB->get_record('edztrackvideo', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/edztrackvideo:view', $context);

if ($ed->sourcetype !== 'serverfile' || empty($ed->serverpath)) {
    send_file_not_found();
}

$full = edztrackvideo_resolve_server_file($ed->serverpath);
if ($full === false) {
    send_file_not_found();
}

$filename = basename($full);
$mimetype = mimeinfo('type', $filename);
if (empty($mimetype) || $mimetype === 'document/unknown') {
    $mimetype = 'video/mp4';
}

// send_file() byte-serves (HTTP range) local files, so seeking/buffering works.
send_file($full, $filename, null, 0, false, false, $mimetype);
