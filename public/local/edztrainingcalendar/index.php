<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/form/upload_form.php');

require_login();
$context = context_system::instance();
require_capability('local/edztrainingcalendar:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edztrainingcalendar/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_edztrainingcalendar'));
$PAGE->set_heading(get_string('pluginname', 'local_edztrainingcalendar'));

// sample CSV link
$samplecsv = new moodle_url('/local/edztrainingcalendar/sample/sample.csv');

$form = new \local_edztrainingcalendar\form\upload_form(null, ['sampleurl' => $samplecsv->out(false)]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
} else if ($data = $form->get_data()) {
    $fs = get_file_storage();
    $draftitemid = file_get_submitted_draft_itemid('csvfile');

    // Get the uploaded file(s) from the draft area
    $usercontext = context_user::instance($USER->id);
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

    if (empty($files)) {
        print_error('invalidcsv', 'local_edztrainingcalendar');
    }

    // Pick the first uploaded file
    $file = reset($files);

    // Copy its content to Moodle temp dir for preview processing
    $tempdir = make_temp_directory('local_edztrainingcalendar/' . $USER->id);
    $filepath = $tempdir . '/' . $file->get_filename();

    // Important: read the file content and write manually (no create_file_from_storedfile)
    $handle = fopen($filepath, 'w');
    fwrite($handle, $file->get_content());
    fclose($handle);

    // Redirect to preview.php with encoded temp path
    $token = base64_encode($filepath);
    redirect(new moodle_url('/local/edztrainingcalendar/preview.php', [
        't' => $token,
        'h' => intval($data->hasheader)
    ]));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
