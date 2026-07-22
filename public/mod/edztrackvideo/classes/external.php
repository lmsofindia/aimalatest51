<?php
/**
 * External (AJAX) API for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

namespace mod_edztrackvideo;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/edztrackvideo/lib.php');
require_once($CFG->dirroot . '/mod/edztrackvideo/classes/progress_manager.php');
require_once($CFG->dirroot . '/mod/edztrackvideo/classes/source_resolver.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

class external extends external_api {

    // ---- get_progress ----

    public static function get_progress_parameters() {
        return new external_function_parameters([
            'edztrackvideoid' => new external_value(PARAM_INT, 'edztrackvideo instance id'),
        ]);
    }

    public static function get_progress($edztrackvideoid) {
        global $DB, $USER;
        self::validate_parameters(self::get_progress_parameters(), ['edztrackvideoid' => $edztrackvideoid]);

        $ed = $DB->get_record('edztrackvideo', ['id' => $edztrackvideoid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('edztrackvideo', $ed->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/edztrackvideo:view', $context);

        $pm = new progress_manager($ed, $cm);
        $rec = $pm->get_progress_for_user($USER->id);

        $minposition = 1.0;
        $showresume = (isset($rec->last_position) && (float)$rec->last_position >= $minposition
            && empty($rec->completed) && empty($rec->dismissed));

        return [
            'last_position' => (float)$rec->last_position,
            'maxwatched' => (float)$rec->maxwatched,
            'duration' => (float)$rec->duration,
            'lastupdate' => (int)$rec->lastupdate,
            'last_viewed_on' => (int)($rec->last_viewed_on ?? 0),
            'completed' => (int)$rec->completed,
            'show_resume' => (int)$showresume,
            'no_forward_seek_first_view' => (int)$ed->no_forward_seek_first_view,
        ];
    }

    public static function get_progress_returns() {
        return new external_single_structure([
            'last_position' => new external_value(PARAM_FLOAT, 'last known position'),
            'maxwatched' => new external_value(PARAM_FLOAT, 'max watched'),
            'duration' => new external_value(PARAM_FLOAT, 'duration'),
            'lastupdate' => new external_value(PARAM_INT, 'last update ts'),
            'last_viewed_on' => new external_value(PARAM_INT, 'last viewed timestamp'),
            'completed' => new external_value(PARAM_INT, 'completed flag'),
            'show_resume' => new external_value(PARAM_INT, 'show resume flag'),
            'no_forward_seek_first_view' => new external_value(PARAM_INT, 'first-view forward-seek lock flag'),
        ]);
    }

    // ---- update_progress ----

    public static function update_progress_parameters() {
        return new external_function_parameters([
            'edztrackvideoid' => new external_value(PARAM_INT, 'edztrackvideo instance id'),
            'current_time' => new external_value(PARAM_FLOAT, 'current playback time', VALUE_DEFAULT, 0),
            'duration' => new external_value(PARAM_FLOAT, 'video duration', VALUE_DEFAULT, 0),
        ]);
    }

    public static function update_progress($edztrackvideoid, $current_time = 0, $duration = 0) {
        global $DB, $USER;
        self::validate_parameters(self::update_progress_parameters(), [
            'edztrackvideoid' => $edztrackvideoid,
            'current_time' => $current_time,
            'duration' => $duration,
        ]);

        $ed = $DB->get_record('edztrackvideo', ['id' => $edztrackvideoid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('edztrackvideo', $ed->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/edztrackvideo:view', $context);

        $pm = new progress_manager($ed, $cm);
        $rec = $pm->update_progress([
            'userid' => $USER->id,
            'current_time' => $current_time,
            'duration' => $duration,
        ]);

        return [
            'status' => 'ok',
            'last_position' => (float)$rec->last_position,
            'maxwatched' => (float)$rec->maxwatched,
            'completed' => (int)$rec->completed,
        ];
    }

    public static function update_progress_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'last_position' => new external_value(PARAM_FLOAT, 'last position'),
            'maxwatched' => new external_value(PARAM_FLOAT, 'max watched'),
            'completed' => new external_value(PARAM_INT, 'completed flag'),
        ]);
    }

    // ---- mark_dismissed ----

    public static function mark_dismissed_parameters() {
        return new external_function_parameters([
            'edztrackvideoid' => new external_value(PARAM_INT, 'edztrackvideo instance id'),
        ]);
    }

    public static function mark_dismissed($edztrackvideoid) {
        global $DB, $USER;
        self::validate_parameters(self::mark_dismissed_parameters(), ['edztrackvideoid' => $edztrackvideoid]);

        $ed = $DB->get_record('edztrackvideo', ['id' => $edztrackvideoid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('edztrackvideo', $ed->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/edztrackvideo:view', $context);

        $pm = new progress_manager($ed, $cm);
        $pm->mark_dismissed($USER->id);

        return ['status' => 'ok'];
    }

    public static function mark_dismissed_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
        ]);
    }

    // ---- get_config (numeric-id fallback init path) ----

    public static function get_config_parameters() {
        return new external_function_parameters([
            'edztrackvideoid' => new external_value(PARAM_INT, 'edztrackvideo instance id'),
        ]);
    }

    public static function get_config($edztrackvideoid) {
        global $DB;
        self::validate_parameters(self::get_config_parameters(), ['edztrackvideoid' => $edztrackvideoid]);

        $ed = $DB->get_record('edztrackvideo', ['id' => $edztrackvideoid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('edztrackvideo', $ed->id, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/edztrackvideo:view', $context);

        $config = source_resolver::build_player_config($ed, $cm, $context);

        return [
            'edztrackvideoid' => (int)$config['edztrackvideoid'],
            'cmid' => (int)$config['cmid'],
            'sourcetype' => (string)$config['sourcetype'],
            'videoid' => (string)$config['videoid'],
            'videohash' => (string)$config['videohash'],
            'fileurl' => (string)$config['fileurl'],
            'mimetype' => (string)$config['mimetype'],
            'no_forward_seek_first_view' => (int)$config['no_forward_seek_first_view'],
            'completion_threshold' => (int)$config['completion_threshold'],
            'allow_speed' => (int)$config['allow_speed'],
            'max_speed' => (float)$config['max_speed'],
        ];
    }

    public static function get_config_returns() {
        return new external_single_structure([
            'edztrackvideoid' => new external_value(PARAM_INT, 'instance id'),
            'cmid' => new external_value(PARAM_INT, 'course module id'),
            'sourcetype' => new external_value(PARAM_ALPHA, 'source type'),
            'videoid' => new external_value(PARAM_RAW, 'video id'),
            'videohash' => new external_value(PARAM_RAW, 'vimeo private hash'),
            'fileurl' => new external_value(PARAM_RAW, 'uploaded file url'),
            'mimetype' => new external_value(PARAM_RAW, 'uploaded file mimetype'),
            'no_forward_seek_first_view' => new external_value(PARAM_INT, 'forward-lock flag'),
            'completion_threshold' => new external_value(PARAM_INT, 'completion threshold'),
            'allow_speed' => new external_value(PARAM_INT, 'allow speed flag'),
            'max_speed' => new external_value(PARAM_FLOAT, 'max speed'),
        ]);
    }
}
