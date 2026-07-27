<?php

/**
 * 5-step AI generation wizard page.
 *
 * Step 1: Select Activities (multi-CM selection)
 * Step 2: Select Outputs (features checkboxes, site flags respected)
 * Step 3: Configure Parameters (language, Bloom's sliders, chunk size, etc.)
 * Step 4: Review & Confirm
 * Step 5: Monitor Progress (live polling per CM)
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$courseid = required_param('courseid', PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/edzaiaxisfront:managecourse', $context);

if (!get_config('local_edzaiaxisfront', 'plugin_enabled')) {
    throw new moodle_exception('plugindisabled', 'local_edzaiaxisfront');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edzaiaxisfront/pages/wizard.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('wizard_title', 'local_edzaiaxisfront'));
$PAGE->set_heading($course->fullname . ': ' . get_string('wizard_title', 'local_edzaiaxisfront'));
$PAGE->set_pagelayout('incourse');

// Site-level enabled features
$site_features = [];
foreach (['summary', 'glossary', 'flashcards', 'quiz', 'faq', 'infographic', 'chatbot', 'kb_chat'] as $f) {
    if (get_config('local_edzaiaxisfront', "feature_{$f}")) {
        $site_features[] = $f;
    }
}

// Load wizard AMD module
// $PAGE->requires->js_call_amd(
//     'local_edzaiaxisfront/wizard',
//     'init',
//     [
//         'courseid'      => $courseid,
//         'sesskey'       => sesskey(),
//         'site_features' => $site_features,
//         'dashboard_url' => (new moodle_url('/local/edzaiaxisfront/pages/dashboard.php', ['courseid' => $courseid]))->out(false),
//     ]
// );

$jsconfig = [[
    'courseid'      => (int)$courseid,
    'sesskey'       => sesskey(),
    'site_features' => array_values($site_features), // important
    'dashboard_url' => (new moodle_url('/local/edzaiaxisfront/pages/dashboard.php', ['courseid' => $courseid]))->out(false),
]];

$PAGE->requires->js_call_amd(
    'local_edzaiaxisfront/wizard',
    'init',
    $jsconfig
);

// Build template context
$feature_options = [];
foreach ($site_features as $f) {
    if (in_array($f, ['chatbot', 'kb_chat'])) continue; // Chat is not a generation task
    $feature_options[] = [
        'key'   => $f,
        'label' => get_string("feature_{$f}", 'local_edzaiaxisfront'),
    ];
}

$templatectx = [
    'courseid'       => $courseid,
    'sesskey'        => sesskey(),
    'feature_options' => $feature_options,
    'dashboard_url'  => (new moodle_url('/local/edzaiaxisfront/pages/dashboard.php', ['courseid' => $courseid]))->out(false),
    'steps'          => [
        ['num' => 1, 'label' => get_string('wizard_step1', 'local_edzaiaxisfront'), 'active' => true],
        ['num' => 2, 'label' => get_string('wizard_step2', 'local_edzaiaxisfront'), 'active' => false],
        ['num' => 3, 'label' => get_string('wizard_step3', 'local_edzaiaxisfront'), 'active' => false],
        ['num' => 4, 'label' => get_string('wizard_step4', 'local_edzaiaxisfront'), 'active' => false],
        ['num' => 5, 'label' => get_string('wizard_step5', 'local_edzaiaxisfront'), 'active' => false],
    ],
    'blooms_levels'  => [
        ['key' => 'remember',    'label' => get_string('blooms_remember',    'local_edzaiaxisfront'), 'default' => 20],
        ['key' => 'understand',  'label' => get_string('blooms_understand',  'local_edzaiaxisfront'), 'default' => 25],
        ['key' => 'apply',       'label' => get_string('blooms_apply',       'local_edzaiaxisfront'), 'default' => 25],
        ['key' => 'analyze',     'label' => get_string('blooms_analyze',     'local_edzaiaxisfront'), 'default' => 15],
        ['key' => 'evaluate',    'label' => get_string('blooms_evaluate',    'local_edzaiaxisfront'), 'default' => 10],
        ['key' => 'create',      'label' => get_string('blooms_create',      'local_edzaiaxisfront'), 'default' => 5],
    ],
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_edzaiaxisfront/wizard', $templatectx);
echo $OUTPUT->footer();
