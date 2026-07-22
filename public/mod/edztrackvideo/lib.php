<?php
/**
 * Library of interface functions and constants for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/edztrackvideo/classes/progress_manager.php');

/**
 * File area name for the locally uploaded video.
 */
define('MOD_EDZTRACKVIDEO_FILEAREA', 'videofile');

/**
 * Add a new instance.
 *
 * @param stdClass $data
 * @param mod_edztrackvideo_mod_form|null $mform
 * @return int new instance id
 */
function edztrackvideo_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    $record = edztrackvideo_prepare_record($data);
    $record->timecreated = $data->timecreated;
    $record->timemodified = $data->timemodified;

    $id = $DB->insert_record('edztrackvideo', $record);
    $data->id = $id;

    // Persist uploaded video file from the draft area (upload source).
    edztrackvideo_save_files($data);

    return $id;
}

/**
 * Update an existing instance.
 *
 * @param stdClass $data
 * @param mod_edztrackvideo_mod_form|null $mform
 * @return bool
 */
function edztrackvideo_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $existing = $DB->get_record('edztrackvideo', ['id' => $data->id]);
    if (!$existing) {
        return false;
    }

    $record = edztrackvideo_prepare_record($data);
    $record->id = $data->id;
    $record->timemodified = time();

    $DB->update_record('edztrackvideo', $record);

    edztrackvideo_save_files($data);

    return true;
}

/**
 * Map form data to a DB record, resolving the source type and extracting ids.
 *
 * @param stdClass $data
 * @return stdClass
 */
function edztrackvideo_prepare_record($data) {
    $record = new stdClass();
    $record->course = $data->course;
    $record->name = $data->name;
    $record->intro = $data->intro ?? '';
    $record->introformat = $data->introformat ?? FORMAT_HTML;

    $sourcetype = $data->sourcetype ?? 'youtube';
    if (!in_array($sourcetype, ['youtube', 'vimeo', 'upload', 'serverfile', 'directurl'], true)) {
        $sourcetype = 'youtube';
    }
    $record->sourcetype = $sourcetype;

    $record->externalurl = '';
    $record->videoid = '';
    $record->videohash = '';
    $record->serverpath = '';

    if ($sourcetype === 'youtube') {
        $url = trim($data->externalurl ?? '');
        $record->externalurl = $url;
        $record->videoid = edztrackvideo_extract_youtube_id($url);
    } else if ($sourcetype === 'vimeo') {
        $url = trim($data->externalurl ?? '');
        $record->externalurl = $url;
        [$vid, $vhash] = edztrackvideo_extract_vimeo_id($url);
        $record->videoid = $vid;
        $record->videohash = $vhash;
    } else if ($sourcetype === 'directurl') {
        // Only honoured if the admin enabled this source.
        if (get_config('mod_edztrackvideo', 'enable_directurl')) {
            $record->externalurl = trim($data->externalurl ?? '');
        } else {
            // Fall back safely if disabled between form load and save.
            $record->sourcetype = 'youtube';
        }
    } else if ($sourcetype === 'serverfile') {
        $rel = (string)($data->serverpath ?? '');
        // Store only if it resolves inside the configured base directory.
        if ($rel !== '' && edztrackvideo_resolve_server_file($rel) !== false) {
            $record->serverpath = $rel;
        }
    }
    // For 'upload' the file lives in the file area; no url/id columns needed.

    $record->no_forward_seek_first_view = !empty($data->no_forward_seek_first_view) ? 1 : 0;
    $record->allow_speed = !empty($data->allow_speed) ? 1 : 0;

    $maxspeed = (float)($data->max_speed ?? 1.0);
    if (!in_array($maxspeed, [1.0, 1.25, 1.5, 2.0], true)) {
        $maxspeed = 1.0;
    }
    // If speed control is off, force the cap to 1.0.
    $record->max_speed = $record->allow_speed ? $maxspeed : 1.0;

    $record->enable_completion_percent = !empty($data->enable_completion_percent) ? 1 : 0;
    $record->completion_threshold = (int)($data->completion_threshold ?? 100);
    if ($record->completion_threshold < 1) {
        $record->completion_threshold = 1;
    }
    if ($record->completion_threshold > 100) {
        $record->completion_threshold = 100;
    }

    return $record;
}

/**
 * Save the uploaded video file from the draft area into the module file area.
 *
 * @param stdClass $data must include ->id (instance) and ->coursemodule (or be resolvable)
 */
function edztrackvideo_save_files($data) {
    if (empty($data->videofile)) {
        return;
    }
    if (empty($data->coursemodule)) {
        return;
    }
    $context = context_module::instance($data->coursemodule);
    file_save_draft_area_files(
        $data->videofile,
        $context->id,
        'mod_edztrackvideo',
        MOD_EDZTRACKVIDEO_FILEAREA,
        0,
        edztrackvideo_filemanager_options()
    );
}

/**
 * Filemanager options for the video upload element.
 *
 * @return array
 */
function edztrackvideo_filemanager_options() {
    return [
        'subdirs' => 0,
        'maxfiles' => 1,
        'accepted_types' => ['.mp4', '.webm', '.ogv', '.ogg', '.mov', '.m4v'],
    ];
}

/**
 * List video files available in the admin-configured server video folder.
 *
 * Recurses into subfolders, returns [relativepath => relativepath] for select menus.
 * Capped to keep the form responsive on very large folders.
 *
 * @return array
 */
function edztrackvideo_list_server_files() {
    $base = get_config('mod_edztrackvideo', 'videobasedir');
    if (empty($base) || !is_dir($base)) {
        return [];
    }
    $realbase = realpath($base);
    if ($realbase === false) {
        return [];
    }

    $exts = ['mp4', 'webm', 'ogv', 'ogg', 'mov', 'm4v'];
    $out = [];
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realbase, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iter as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if (!in_array(strtolower($file->getExtension()), $exts, true)) {
                continue;
            }
            $full = $file->getRealPath();
            if ($full === false || strpos($full, $realbase . DIRECTORY_SEPARATOR) !== 0) {
                continue;
            }
            $rel = ltrim(substr($full, strlen($realbase)), DIRECTORY_SEPARATOR);
            // Normalise to forward slashes for storage/display.
            $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            $out[$rel] = $rel;
            if (count($out) >= 500) {
                break;
            }
        }
    } catch (Exception $e) {
        return $out;
    }
    ksort($out);
    return $out;
}

/**
 * Resolve a stored relative server path to an absolute path, confined to the base dir.
 *
 * Rejects path traversal and anything outside the configured folder.
 *
 * @param string $relpath
 * @return string|false absolute path, or false if invalid
 */
function edztrackvideo_resolve_server_file($relpath) {
    $base = get_config('mod_edztrackvideo', 'videobasedir');
    if (empty($base) || $relpath === '') {
        return false;
    }
    $realbase = realpath($base);
    if ($realbase === false) {
        return false;
    }
    // Reject obvious traversal before touching the filesystem.
    $relpath = str_replace('\\', '/', $relpath);
    if (strpos($relpath, '..') !== false) {
        return false;
    }
    $candidate = $realbase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relpath, '/'));
    $full = realpath($candidate);
    if ($full === false || !is_file($full)) {
        return false;
    }
    // Must live inside the base directory.
    if ($full !== $realbase && strpos($full, $realbase . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }
    return $full;
}

/**
 * Delete an instance.
 *
 * @param int $id
 * @return bool
 */
function edztrackvideo_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('edztrackvideo', ['id' => $id])) {
        return false;
    }

    // Remove uploaded files for this module context.
    $cm = get_coursemodule_from_instance('edztrackvideo', $id);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_edztrackvideo', MOD_EDZTRACKVIDEO_FILEAREA);
    }

    $DB->delete_records('edztrackvideo_progress', ['edztrackvideoid' => $id]);
    $DB->delete_records('edztrackvideo', ['id' => $id]);

    return true;
}

/**
 * Extract a YouTube video id from a URL.
 *
 * @param string $url
 * @return string
 */
function edztrackvideo_extract_youtube_id($url) {
    if (empty($url)) {
        return '';
    }
    $id = '';
    if (preg_match('/youtu\.be\/([^\?&\/]+)/', $url, $m)) {
        $id = $m[1];
    } else if (preg_match('/[?&]v=([^\?&\/]+)/', $url, $m)) {
        $id = $m[1];
    } else if (preg_match('/embed\/([^\?&\/]+)/', $url, $m)) {
        $id = $m[1];
    } else if (preg_match('/youtube\.com\/shorts\/([^\?&\/]+)/', $url, $m)) {
        $id = $m[1];
    } else {
        $parts = explode('/', rtrim($url, '/'));
        $id = end($parts);
    }
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', $id);
}

/**
 * Extract a Vimeo video id (and optional private hash) from a URL.
 *
 * Handles:
 *   https://vimeo.com/123456789
 *   https://vimeo.com/123456789/abcdef0123   (unlisted private hash)
 *   https://player.vimeo.com/video/123456789
 *   https://player.vimeo.com/video/123456789?h=abcdef0123
 *
 * @param string $url
 * @return array [string $id, string $hash]
 */
function edztrackvideo_extract_vimeo_id($url) {
    if (empty($url)) {
        return ['', ''];
    }
    $id = '';
    $hash = '';

    if (preg_match('/player\.vimeo\.com\/video\/(\d+)/', $url, $m)) {
        $id = $m[1];
        if (preg_match('/[?&]h=([0-9a-zA-Z]+)/', $url, $hm)) {
            $hash = $hm[1];
        }
    } else if (preg_match('/vimeo\.com\/(\d+)(?:\/([0-9a-zA-Z]+))?/', $url, $m)) {
        $id = $m[1];
        if (!empty($m[2])) {
            $hash = $m[2];
        }
    } else if (preg_match('/(\d{6,})/', $url, $m)) {
        // Bare numeric id fallback.
        $id = $m[1];
    }

    $id = preg_replace('/[^0-9]/', '', $id);
    $hash = preg_replace('/[^0-9a-zA-Z]/', '', $hash);
    return [$id, $hash];
}

/**
 * Serve files from the videofile file area (local upload source).
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if not found
 */
function edztrackvideo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    if ($filearea !== MOD_EDZTRACKVIDEO_FILEAREA) {
        return false;
    }

    require_login($course, true, $cm);
    require_capability('mod/edztrackvideo:view', $context);

    $itemid = (int)array_shift($args); // Always 0 for our single-file area.
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_edztrackvideo', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    // send_stored_file honours HTTP range requests, so HTML5 seeking/buffering works.
    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}

/**
 * Declare supported features.
 *
 * @param string $feature
 * @return mixed
 */
function edztrackvideo_supports(string $feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return defined('MOD_PURPOSE_CONTENT') ? MOD_PURPOSE_CONTENT : null;
        default:
            return null;
    }
}

/**
 * Provide course module info, including custom completion rule data.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|false
 */
function edztrackvideo_get_coursemodule_info($coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat, completion_threshold, enable_completion_percent';
    $ed = $DB->get_record('edztrackvideo', ['id' => $coursemodule->instance], $fields);
    if (!$ed) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $ed->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('edztrackvideo', $ed, $coursemodule->id, false);
    }

    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC && !empty($ed->enable_completion_percent)) {
        $info->customdata['customcompletionrules']['completion_threshold'] =
            (int)($ed->completion_threshold ?? 100);
    }

    return $info;
}

/**
 * Describe active custom completion rules for display in the course.
 *
 * @param cm_info|stdClass $cm
 * @return array
 */
function mod_edztrackvideo_get_completion_active_rule_descriptions($cm) {
    global $DB;

    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $ed = $DB->get_record('edztrackvideo', ['id' => $cm->instance], '*', IGNORE_MISSING);
    if (!$ed) {
        return [];
    }

    $threshold = (int)($ed->completion_threshold ?? 100);
    if (empty($ed->enable_completion_percent)) {
        return [];
    }

    return [get_string('completion_watchpercent', 'mod_edztrackvideo', $threshold)];
}
