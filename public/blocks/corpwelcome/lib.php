<?php
// This file is part of Moodle - http://moodle.org/
//
// Corporate welcome block library.

defined('MOODLE_INTERNAL') || die();

/**
 * Serve the admin-uploaded background image from a block instance's file area.
 *
 * URL shape: /pluginfile.php/{blockcontextid}/block_corpwelcome/backgroundimage/0/{filename}
 *
 * @param stdClass $course        Course object (unused for block context).
 * @param stdClass $birecordorcm  Block instance record.
 * @param context  $context       Block context.
 * @param string   $filearea      File area name.
 * @param array    $args          [itemid, ...filepath, filename].
 * @param bool     $forcedownload Whether to force download.
 * @param array    $options       Additional options.
 * @return bool False if the file was not served.
 */
function block_corpwelcome_pluginfile($course, $birecordorcm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Only serve from block contexts, and only our one file area.
    if ($context->contextlevel != CONTEXT_BLOCK) {
        return false;
    }
    if ($filearea !== 'backgroundimage') {
        return false;
    }

    // The block renders only for logged-in, non-guest users, so gate the file too.
    require_login();
    if (isguestuser()) {
        return false;
    }

    $filename = array_pop($args);
    $itemid   = (int) array_shift($args); // Always 0 for this area.
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs   = get_file_storage();
    $file = $fs->get_file($context->id, 'block_corpwelcome', 'backgroundimage', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    // Cache for a day; images change rarely.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
