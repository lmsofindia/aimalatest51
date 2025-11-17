<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/edzworkplacerpt/classes/helper.php');
global $DB;

// Hardcode the "from" user (admin) ID
$fromuserid = 3;

// Hardcode the recipient email
$recipientemail = 'testuser@example.com';

// Find the user ID by email
$touser = $DB->get_record('user', ['email' => $recipientemail, 'deleted' => 0], '*', IGNORE_MISSING);
if (!$touser) {
    die("Recipient not found or deleted.");
}

// Recipient user ID(s)
$touserids = [$touser->id];

// Message details
$subject = "Test message";
$messagehtml = "<p>Hello, this is a test message from Moodle.</p>";

// Send message
$result = \local_edzworkplacerpt\helper::send_message($fromuserid, $touserids, $subject, $messagehtml);

// Output result
print_r($result);
