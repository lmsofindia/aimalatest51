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
 * Lib for local_edzallcourse plugin.
 *
 * @package    local_edzallcourse
 * @copyright  2025 Rashid <abdul.rashid@edzlms.com>
 * @license    https://edzlms.com/
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Add a link under "Site home" navigation for convenience (optional).
 */
function local_edzallcourse_extend_navigation(global_navigation $nav)
{
    if (!has_capability('local/edzallcourse:view', context_system::instance())) {
        return;
    }
    $node = $nav->add(
        get_string('viewpage', 'local_edzallcourse'),
        new moodle_url('/local/edzallcourse/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_edzallcourse',
        new pix_icon('i/filter', '')
    );
    $node->showinflatnavigation = true;
}


/**
 * Pluginfile for local_edzallcourse
 */
function local_edzallcourse_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG;

    // Only allow course category files
    if ($context->contextlevel != CONTEXT_COURSECAT) {
        return false;
    }

    if (!in_array($filearea, ['description'])) {
        return false;
    }

    $itemid = (int) array_shift($args) ?: 0;

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'coursecat', $filearea, $itemid, '/', $args[0]);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Get formatted category description (with images)
 */
function edzallcourse_fp_category_description($categoryid) {
    $category = \core_course_category::get($categoryid, IGNORE_MISSING, false);
    if (!$category) {
        return '';
    }

    $context = context_coursecat::instance($categoryid);

    return format_text(
        file_rewrite_pluginfile_urls(
            $category->description,
            'pluginfile.php',
            $context->id,
            'coursecat',
            'description',
            0
        ),
        $category->descriptionformat,
        [
            'context' => $context,
            'noclean' => true,
            'trusttext' => true
        ]
    );
}

/**
 * Get all images attached in category description
 */
function edzallcourse_fp_all_images($categoryid) {
    $context = context_coursecat::instance($categoryid);
    $imageurls = [];

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'coursecat', 'description', 0, 'filename', false);

    foreach ($files as $file) {
        if ($file->is_valid_image()) {
            $imageurls[] = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
        }
    }
    return $imageurls;
}

/**
 * Convert encoded URLs in $text from the @@PLUGINFILE@@/... form to an actual URL.
 * Passing a new option reverse = true in the $options var will make the function to convert actual URLs in $text to encoded URLs
 * in the @@PLUGINFILE@@ form.
 *
 * @param   string  $text The content that may contain ULRs in need of rewriting.
 * @param   string  $file The script that should be used to serve these files. pluginfile.php, draftfile.php, etc.
 * @param   int     $contextid This parameter and the next two identify the file area to use.
 * @param   string  $component
 * @param   string  $filearea helps identify the file area.
 * @param   ?int    $itemid helps identify the file area.
 * @param   array   $options
 *          bool    $options.forcehttps Force the user of https
 *          bool    $options.reverse Reverse the behaviour of the function
 *          mixed   $options.includetoken Use a token for authentication. True for current user, int value for other user id.
 *          string  The processed text.
 */
function edzfile_rewrite_pluginfile_urls($text, $file, $contextid, $component, $filearea, $itemid, ?array $options=null) {

    echo "rashid"; die();
    global $CFG, $USER;
    $options = (array)$options;
    if (!isset($options['forcehttps'])) {
        $options['forcehttps'] = false;
    }

    $baseurl = "{$CFG->wwwroot}/{$file}";
    if (!empty($options['includetoken'])) {
        $userid = $options['includetoken'] === true ? $USER->id : $options['includetoken'];
        $token = get_user_key('core_files', $userid);
        $finalfile = basename($file);
        $tokenfile = "token{$finalfile}";
        $file = substr($file, 0, strlen($file) - strlen($finalfile)) . $tokenfile;
        $baseurl = "{$CFG->wwwroot}/{$file}";

        if (!$CFG->slasharguments) {
            $baseurl .= "?token={$token}&file=";
        } else {
            $baseurl .= "/{$token}";
        }
    }

    $baseurl .= "/{$contextid}/{$component}/{$filearea}/";

    if ($itemid !== null) {
        $baseurl .= "$itemid/";
    }

    if ($options['forcehttps']) {
        $baseurl = str_replace('http://', 'https://', $baseurl);
    }

    if (!empty($options['reverse'])) {
        return str_replace($baseurl, '@@PLUGINFILE@@/', $text ?? '');
    } else {
        return str_replace('@@PLUGINFILE@@/', $baseurl, $text ?? '');
    }
}



function get_course_gradient_color($courseid) {
    $colors = [
        '#81ecec',
        '#74b9ff',
        '#a29bfe',
        '#55efc4',
        '#00b894',
        '#fdcb6e',
        '#fd79a8',
        '#e17055',
        '#6c5ce7',
        '#ffeaa7',
    ];
    return $colors[$courseid % count($colors)];
}