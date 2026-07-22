<?php
/**
 * Student view for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/edztrackvideo/lib.php');
require_once($CFG->dirroot . '/mod/edztrackvideo/classes/source_resolver.php');

$id = required_param('id', PARAM_INT); // Course module id.

$cm = get_coursemodule_from_id('edztrackvideo', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$ed = $DB->get_record('edztrackvideo', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/edztrackvideo:view', $context);

// Trigger course_module_viewed (also satisfies view-based completion).
$event = \mod_edztrackvideo\event\course_module_viewed::create([
    'objectid' => $ed->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('edztrackvideo', $ed);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/edztrackvideo/view.php', ['id' => $id]);
$PAGE->set_title(format_string($ed->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/edztrackvideo/styles.css');

$renderer = $PAGE->get_renderer('mod_edztrackvideo');

echo $OUTPUT->header();
echo $renderer->render_view($ed, $cm, $context);

// Build the player config consumed by the JS core.
$config = \mod_edztrackvideo\source_resolver::build_player_config($ed, $cm, $context);
$config['sesskey'] = sesskey();

// Strings used at runtime by the JS core (M.util.get_string).
$PAGE->requires->strings_for_js([
    'forwardlocked', 'completedmsg', 'resume', 'resumefrom', 'dismiss',
    'play', 'pause', 'mute', 'unmute', 'captions', 'speed', 'fullscreen',
    'videoloaderror', 'novideoconfigured',
], 'mod_edztrackvideo');

$PAGE->requires->js_call_amd('mod_edztrackvideo/player', 'init', [$config]);

echo $OUTPUT->footer();
