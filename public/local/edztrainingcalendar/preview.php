<?php
require_once(__DIR__ . '/../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/edztrainingcalendar:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/edztrainingcalendar/preview.php'));
$PAGE->set_title(get_string('preview', 'local_edztrainingcalendar'));
$PAGE->set_heading(get_string('preview', 'local_edztrainingcalendar'));

global $DB;

// -----------------------------------------------------------
// 1. Handle Confirm Save BEFORE rendering HTML
// -----------------------------------------------------------
$confirm = optional_param('confirm', '', PARAM_RAW);
$token   = optional_param('t', '', PARAM_RAW);
$hasheader = optional_param('h', 1, PARAM_INT);

if ($confirm !== '') {
    // Retrieve decoded temp file path again
    $filepath = base64_decode($token);
    if (!file_exists($filepath)) {
        print_error('invalidcsv', 'local_edztrainingcalendar');
    }

    $handle = fopen($filepath, 'r');
    if ($hasheader) {
        fgetcsv($handle); // skip header
    }

    $timenow = time();
    $insertcount = 0;
    while (($data = fgetcsv($handle)) !== false) {
        $d = [
            'useremail'        => trim($data[0] ?? ''),
            'courseshortname'  => trim($data[1] ?? ''),
            'coursestartdate'  => trim($data[2] ?? ''),
            'expectedcmpldate' => trim($data[3] ?? ''),
            'cohortidentifier' => trim($data[4] ?? '')
        ];
        if (empty($d['useremail']) || empty($d['courseshortname']) || empty($d['coursestartdate'])) {
            continue; // skip incomplete rows
        }

        // find the user id for this email
        $insert_userid = null;
        $user = $DB->get_record('user', ['email' => $d['useremail'], 'deleted' => 0], 'id', IGNORE_MISSING);
        if ($user) {
            $insert_userid = $user->id;
        }

        // check duplicate (userid + timestamp_coursestartdate)
        $timestampstart = strtotime($d['coursestartdate']) ?: 0;
        if ($insert_userid && $DB->record_exists_select(
            'local_edztrainingcalendar_journey',
            'userid = :uid AND timestamp_coursestartdate = :tcsd',
            ['uid' => $insert_userid, 'tcsd' => $timestampstart]
        )) {
            continue;
        }

        $record = new stdClass();
        $record->useremail = $d['useremail'];
        $record->userid = $insert_userid;
        $record->courseshortname = $d['courseshortname'];
        $record->coursestartdate = $d['coursestartdate'];
        $record->timestamp_coursestartdate = $timestampstart;
        $record->expectedcmpldate = $d['expectedcmpldate'];
        $record->timestamp_expectedcmpldate = strtotime($d['expectedcmpldate']) ?: 0;
        $record->cohortidentifier = $d['cohortidentifier'];
        $record->uploadeddate = $timenow;
        $record->uploadedby = $USER->id;
        $record->processed_flag = 0;

        $DB->insert_record('local_edztrainingcalendar_journey', $record);
        $insertcount++;
    }
    fclose($handle);

    // Cleanup temp file
    @unlink($filepath);

    redirect(
        new moodle_url('/local/edztrainingcalendar/list.php'),
        "Uploaded {$insertcount} valid rows successfully."
    );
    exit;
}

// -----------------------------------------------------------
// 2. Preview mode (GET) – parse CSV and show preview table
// -----------------------------------------------------------

$token = required_param('t', PARAM_RAW);
$filepath = base64_decode($token);
if (!file_exists($filepath)) {
    print_error('invalidcsv', 'local_edztrainingcalendar');
}

$rows = [];
$handle = fopen($filepath, 'r');
if ($hasheader) {
    fgetcsv($handle); // skip header
}
while (($data = fgetcsv($handle)) !== false) {
    $rows[] = [
        'useremail'        => trim($data[0] ?? ''),
        'courseshortname'  => trim($data[1] ?? ''),
        'coursestartdate'  => trim($data[2] ?? ''),
        'expectedcmpldate' => trim($data[3] ?? ''),
        'cohortidentifier' => trim($data[4] ?? '')
    ];
}
fclose($handle);

$errors = [];
$seen = [];
$preview = [];

foreach ($rows as $i => $r) {
    $rowno = $i + 1;
    $err = [];

    if (empty($r['useremail']) || !validate_email($r['useremail'])) {
        $err[] = 'invalid email';
    }
    if (empty($r['courseshortname'])) {
        $err[] = 'missing course shortname';
    }
    if (empty($r['coursestartdate'])) {
        $err[] = 'missing start date';
    }

    $key = strtolower($r['useremail'] . '|' . $r['courseshortname'] . '|' . $r['coursestartdate']);
    if (isset($seen[$key])) {
        $err[] = 'duplicate in CSV';
    } else {
        $seen[$key] = true;
    }

    // DB duplicate check
    $user = $DB->get_record('user', ['email' => $r['useremail']], 'id', IGNORE_MISSING);
    $userid = $user ? $user->id : 0;
    $timestamp = strtotime($r['coursestartdate']) ?: 0;

    $exists = $userid && $DB->record_exists_select(
        'local_edztrainingcalendar_journey',
        'userid = :uid AND timestamp_coursestartdate = :tcsd',
        ['uid' => $userid, 'tcsd' => $timestamp]
    );
    if ($exists) {
        $err[] = 'already uploaded';
    }

    $preview[] = ['rowno' => $rowno, 'data' => $r, 'errors' => $err];
}

// -----------------------------------------------------------
// 3. Render the preview table + submit button
// -----------------------------------------------------------

echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo html_writer::tag('h3', get_string('preview', 'local_edztrainingcalendar'));

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/edztrainingcalendar/preview.php')
]);

echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::tag(
    'tr',
    html_writer::tag('th', 'Row') .
        html_writer::tag('th', 'Email') .
        html_writer::tag('th', 'Course shortname') .
        html_writer::tag('th', 'Start date') .
        html_writer::tag('th', 'Expected complete') .
        html_writer::tag('th', 'Cohort') .
        html_writer::tag('th', 'Errors')
);
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

foreach ($preview as $p) {
    $d = $p['data'];
    $err = implode(', ', $p['errors']);
    $rowclass = !empty($p['errors']) ? 'class="error"' : '';
    echo "<tr {$rowclass}>";
    echo html_writer::tag('td', $p['rowno']);
    echo html_writer::tag('td', s($d['useremail']));
    echo html_writer::tag('td', s($d['courseshortname']));
    echo html_writer::tag('td', s($d['coursestartdate']));
    echo html_writer::tag('td', s($d['expectedcmpldate']));
    echo html_writer::tag('td', s($d['cohortidentifier']));
    echo html_writer::tag('td', $err);
    echo '</tr>';
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 't', 'value' => s($token)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'h', 'value' => intval($hasheader)]);

echo html_writer::tag('p', count($preview) . ' rows found.');

$haserrors = false;
foreach ($preview as $p) {
    if (!empty($p['errors'])) {
        $haserrors = true;
        break;
    }
}

if ($haserrors) {
    echo html_writer::tag('div', 'Some rows have errors — fix CSV and re-upload', ['class' => 'warningbox']);
    echo html_writer::link(new moodle_url('/local/edztrainingcalendar/index.php'), 'Back', ['class' => 'btn']);
} else {
    echo html_writer::tag(
        'button',
        get_string('submitupload', 'local_edztrainingcalendar'),
        ['type' => 'submit', 'name' => 'confirm', 'value' => '1', 'class' => 'btn btn-primary']
    );
}

echo html_writer::end_tag('form');
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
