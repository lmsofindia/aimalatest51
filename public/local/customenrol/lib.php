<?php
defined('MOODLE_INTERNAL') || die();


/** Helper to pick course preview video (custom field \"previewvideo\" or plugin default). */
function local_customenrol_get_preview_url(stdClass $course): string
{
    global $CFG;
    // Try course custom field named "previewvideo".
    if (class_exists('core_customfield\handler') && !empty($course->customfield_previewvideo)) {
        return (string)$course->customfield_previewvideo;
    }

    // Support core custom fields API if present via data API.
    if (!empty($course->customfields) && is_array($course->customfields)) {
        foreach ($course->customfields as $cf) {
            if (!empty($cf['shortname']) && $cf['shortname'] === 'previewvideo' && !empty($cf['value'])) {
                return (string)$cf['value'];
            }
        }
    }

    // Fallback to plugin default.
    $default = (string)get_config('local_customenrol', 'defaultpreview');
    return $default ?? '';
}

/** Helper to collect rating from an external admin tool if available. */
function local_customenrol_get_course_rating(int $courseid): ?array
{
    // Expected return: ['avg' => float, 'count' => int, 'stars' => 0..5].

    // 1) tool_courserating API (example): \tool_courserating\api::get_course_rating($courseid)
    if (class_exists('tool_courserating\\api') && method_exists('tool_courserating\\api', 'get_course_rating')) {
        try {
            $r = \tool_courserating\api::get_course_rating($courseid);
            if (is_array($r) && isset($r['average'], $r['count'])) {
                return ['avg' => (float)$r['average'], 'count' => (int)$r['count'], 'stars' => round((float)$r['average'])];
            }
        } catch (Throwable $e) {
        }
    }

    // 2) tool_rating (generic) function example: tool_rating_get_course_rating($courseid)
    if (function_exists('tool_rating_get_course_rating')) {
        try {
            $r = tool_rating_get_course_rating($courseid);
            if (is_array($r) && isset($r['average'], $r['count'])) {
                return ['avg' => (float)$r['average'], 'count' => (int)$r['count'], 'stars' => round((float)$r['average'])];
            }
        } catch (Throwable $e) {
        }
    }

    // 3) Not available.
    return null;
}

/**
 * Get the module icon URL for a given module name.
 *
 * @param string $modname The Moodle module name (e.g. 'quiz', 'assign').
 * @return string|null Icon URL or null if not found.
 */
function local_customenrol_get_mod_icon_url(string $modname): ?string
{
    global $OUTPUT, $CFG;

    $iconpath = "/mod/{$modname}/pix/icon.svg";
    $fsiconpath = $CFG->dirroot . $iconpath;

    if (file_exists($fsiconpath)) {
        return $CFG->wwwroot . $iconpath;
    }

    // Fallback to PNG if no SVG.
    $iconpath = "/mod/{$modname}/pix/icon.png";
    if (file_exists($CFG->dirroot . $iconpath)) {
        return $CFG->wwwroot . $iconpath;
    }

    // Final fallback to core activity icon.
    $default = $OUTPUT->image_url('i/unknown', 'moodle');
    return $default->out(false);
}
/** Get custom fields for a course. */
// Returns an associative array with field shortnames as keys.
// For normal fields, the value is the field value.
// For the "previewvideo" field, the value is HTML with embedded files.
// This function uses the core_customfield API if available.
// If not available, it falls back to the data API.
// This allows it to work with both Moodle 4.0+ and earlier versions.
function local_customenrol_get_course_customfields($courseid)
{
    global $DB, $CFG;

    require_once($CFG->dirroot . '/customfield/lib.php');

    $fields = [];

    // Get all field data records for this course
    $records = $DB->get_records('customfield_data', ['instanceid' => $courseid]);

    foreach ($records as $record) {
        // Create field controller
        $field = \core_customfield\field_controller::create($record->fieldid);

        // Create data controller
        $data = \core_customfield\data_controller::create(0, $record, $field);
        $context = \context_course::instance($courseid);

        $value = $data->export_value();

        /*

        if ($field->get('type') === 'textarea' && !empty($value)) {

            /*
            $videotype = array(
                'webm',
                'mpg',
                'mp2',
                'mpeg',
                'mpe',
                'mpv',
                'ogg',
                'mp4',
                'm4p',
                'm4v',
                'avi',
                'wmv',
                'mov',
                'qt',
                'avchd'
            );;
            $videotype = implode('|\.', $videotype);

            preg_match_all('/(http|https):\/\/[^ ]+(\.' . $videotype . ')/', $value, $out);

            if (!isset($out[0][0])) {
                return;
            }

            $name = true;
            $file_parts = pathinfo($out[0][0]);

            $encode = file_encode_url(
                "$CFG->wwwroot/pluginfile.php",
                '/' . $context->id . '/customfield_textarea/value/' . $record->id . '/' . rawurlencode($file_parts['basename']),
                false
            );



            $rawvalue = file_rewrite_pluginfile_urls(
                $data->export_value(),         // the HTML content of editor
                'pluginfile.php',
                $context->id,                 // course context
                'customfield_textarea',
                $record->id,                  // **itemid = data record id**
                false
            );

            $processed = file_rewrite_pluginfile_urls(
                $data->export_value(),
                'pluginfile.php',
                $context->id,
                'customfield_textarea',
                'value',
                $record->id
            );
        }

          echo $processed;
        */
        // Export the value


        $value = $data->export_value();
        $fields[$field->get('shortname')] = $value;

        //$key = strtolower($field->get('name'));
        //$fields[$key] = $value;
    }

    return $fields;
}
