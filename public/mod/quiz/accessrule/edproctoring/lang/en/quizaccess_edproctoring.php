<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Language strings for quizaccess_edproctoring (English).
 *
 * @package   quizaccess_edproctoring
 * @copyright 2025 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin identity.
$string['pluginname']              = 'Ed Proctoring';
$string['pluginname_desc']         = 'On-premises AI-assisted quiz proctoring with webcam capture, violation detection, and trust scoring.';

// Settings.
$string['defaultcaptureinterval']      = 'Default capture interval (seconds)';
$string['defaultcaptureinterval_desc'] = 'Seconds between webcam snapshots. Can be overridden per quiz. Minimum 10.';
$string['retentiondays']               = 'Image retention (days)';
$string['retentiondays_desc']          = 'Captured images are automatically deleted after this many days. Set 0 to disable auto-delete.';
$string['faceserviceurl']              = 'Face recognition service URL';
$string['faceserviceurl_desc']         = 'URL of the FastAPI face recognition microservice (e.g. http://localhost:8765). Leave empty to disable face recognition.';
$string['facematchthreshold']          = 'Face match threshold (0.0–1.0)';
$string['facematchthreshold_desc']     = 'Cosine distance threshold for face verification. Lower = stricter. Default 0.60.';
$string['notifythreshold']             = 'Trust score notification threshold';
$string['notifythreshold_desc']        = 'Send teacher notification when trust score falls below this value (0–100).';
$string['blockmobiledefault']          = 'Block mobile by default';
$string['blockmobiledefault_desc']     = 'Block quiz access from mobile/tablet browsers by default. Can be overridden per quiz.';
$string['privacyurl']                  = 'Privacy policy URL';
$string['privacyurl_desc']             = 'URL to your institution\'s privacy policy. Shown on the student consent screen.';

// Per-quiz settings.
$string['enabled']                  = 'Enable proctoring';
$string['captureinterval']          = 'Capture interval (seconds)';
$string['captureintervaltoolow']    = 'Capture interval must be at least 10 seconds.';
$string['requireconsent']           = 'Require student consent';
$string['blockmobile']              = 'Block mobile access';
$string['enablefacedetect']         = 'Enable face detection (browser-side)';
$string['enablefacerecog']          = 'Enable face recognition (requires service URL)';
$string['requirefullscreen']        = 'Require fullscreen (log exit as violation)';
$string['detecttabs']               = 'Detect tab switching';
$string['graceperiod']              = 'Face absent grace period (seconds)';
$string['graceperiodrange']         = 'Grace period must be between 1 and 60 seconds.';
$string['autosubmit']               = 'Auto-submit on critical violations';
$string['autosubmitthreshold']      = 'Auto-submit after N critical violations';
$string['notifyteacher']            = 'Notify teacher on low trust score';

// Consent screen.
$string['consenttitle']             = 'This Quiz is Proctored';
$string['consentintro']             = 'Before starting <strong>{$a}</strong>, please read and agree to the following:';
$string['consentbullet_camera']     = 'Your webcam will be active for the entire duration of the quiz.';
$string['consentbullet_storage']    = 'Images captured during the quiz will be securely stored and automatically deleted after {$a} days.';
$string['consentbullet_access']     = 'Your teacher and authorised administrators can review the images and violation log.';
$string['consentbullet_violations'] = 'Detected violations (face absent, tab switch, etc.) are logged and contribute to a Trust Score for this attempt.';
$string['privacypolicy']            = 'Read our Privacy Policy';
$string['consentdenymessage']       = 'If you do not agree, you will not be able to start this quiz. Please contact your teacher if you have concerns.';
$string['consentchecklabel']        = 'I understand and agree to be monitored by webcam during this quiz.';
$string['mustconsent']              = 'You must agree to the proctoring terms to start this quiz.';
$string['cameracheckrequired']      = 'Please complete the camera check before starting.';

// Mobile blocking.
$string['mobileblockedmessage']     = 'This quiz requires a desktop browser with webcam support. Please use a computer to complete this quiz.';

// Pre-flight.
$string['preflighttitle']           = 'Camera Check';
$string['preflightstep_camera']     = 'Requesting camera access...';
$string['preflightstep_facecheck']  = 'Checking face detection...';
$string['preflightstep_ready']      = 'All checks passed. You may start the quiz.';
$string['preflighterr_denied']      = 'Camera access was denied. This quiz requires webcam access. Please allow camera access and reload the page.';
$string['preflighterr_noface']      = 'No face detected. Please position yourself in front of the camera with good lighting.';
$string['preflighterr_multiface']   = 'Multiple people detected. Please ensure only you are visible to the camera.';
$string['preflighterr_lowlight']    = 'Lighting is too low. Please improve lighting and try again.';

// Violation types (for reports and notifications).
$string['violation_FACE_ABSENT']    = 'Face absent';
$string['violation_MULTIPLE_FACES'] = 'Multiple faces';
$string['violation_FACE_MISMATCH']  = 'Face mismatch (Phase 2)';
$string['violation_TAB_SWITCH']     = 'Tab switch';
$string['violation_IDLE']           = 'Inactivity';
$string['modal_title']              = 'Proctoring warning';
$string['modal_dismiss']            = 'I understand';
$string['warnmsg_FACE_ABSENT']      = 'Your face is not visible to the camera. Please sit in front of your webcam.';
$string['warnmsg_MULTIPLE_FACES']   = 'More than one person is visible to the camera. Only you may be present during the quiz.';
$string['warnmsg_TAB_SWITCH']       = 'You switched away from the quiz tab. Stay on this page until you finish the quiz.';
$string['warnmsg_FULLSCREEN_EXIT']  = 'You left fullscreen mode. Please return to fullscreen to continue.';
$string['warnmsg_COPY_PASTE']       = 'Copy and paste is not allowed during this quiz.';
$string['warnmsg_LOW_LIGHT']        = 'Your room is too dark. Please improve the lighting so your face is visible.';
$string['warnmsg_CAMERA_BLOCKED']   = 'Your camera appears to be blocked or disconnected. Please re-enable it now.';
$string['warnmsg_IDLE']             = 'You have been inactive for a while. Please continue working on your quiz.';
$string['downloadpdf']             = 'Download PDF report';
$string['violation_FULLSCREEN_EXIT']= 'Fullscreen exit';
$string['violation_COPY_PASTE']     = 'Copy/paste attempt';
$string['violation_LOW_LIGHT']      = 'Low light';
$string['violation_CAMERA_BLOCKED'] = 'Camera blocked';

// Trust score bands.
$string['band_low_risk']            = 'Low Risk';
$string['band_review']              = 'Review';
$string['band_high_risk']           = 'High Risk';

// Reports.
$string['reporttitle']              = 'Proctoring Report: {$a}';
$string['student']                  = 'Student';
$string['attempt']                  = 'Attempt';
$string['starttime']                = 'Started';
$string['duration']                 = 'Duration';
$string['trustscore']               = 'Trust Score';
$string['violations']               = 'Violations';
$string['status']                   = 'Status';
$string['viewdetail']               = 'View Detail';
$string['exportcsv']                = 'Export CSV';
$string['noresults']                = 'No proctored attempts found for this quiz.';
$string['viewreport']               = 'View Proctoring Report';
$string['proctoringnotice']         = 'This quiz is proctored: webcam monitoring is active during your attempt.';
$string['weightsheading']           = 'Trust score deductions';
$string['weightsheading_desc']      = 'Every attempt starts with a trust score of 100. Each detected violation subtracts the points configured below (dismissed violations are refunded). Changes apply to violations logged after saving; already-recorded violations keep the weight that applied at the time.';
$string['weightsetting_desc']       = 'Points deducted per occurrence. Default: {$a}. Set to 0 to track this violation without affecting the trust score.';
$string['dismissviolation']         = 'Dismiss (false positive)';
$string['violationtimeline']        = 'Violation Timeline';
$string['imagegallery']             = 'Captured Images';
$string['trustscorebreakdown']      = 'Trust Score Breakdown';
$string['deleteimages']             = 'Delete all images for this session';
$string['deleteimagesconfirm']      = 'Are you sure? This will permanently delete all captured images for this attempt. Violation records will be retained.';

// Notifications.
$string['notifysubject']            = 'Proctoring Alert: {$a->student} — {$a->quiz}';
$string['notifybody']               = 'Student {$a->student} completed the quiz "{$a->quiz}" with a trust score of {$a->score}% ({$a->band}).

Violations: {$a->critical} critical, {$a->warning} warning.

View the full report: {$a->reporturl}';
$string['notifysmall']              = '{$a->student} — Trust Score: {$a->score}%';

// Privacy.
$string['privacy:metadata:sessions']           = 'Proctoring sessions: one per quiz attempt. Contains consent record, status, and trust score.';
$string['privacy:metadata:snaps']              = 'Webcam snapshots captured during quiz attempts.';
$string['privacy:metadata:violations']         = 'Violation events logged during quiz attempts.';
$string['privacy:metadata:baseimages']         = 'Reference face images uploaded for identity verification.';
$string['privacy:metadata:sessions:userid']    = 'The user this session belongs to.';
$string['privacy:metadata:snaps:userid']       = 'The user this snapshot belongs to.';
$string['privacy:metadata:violations:userid']  = 'The user who triggered this violation.';
$string['privacy:metadata:baseimages:userid']  = 'The user this base image belongs to.';

// Capabilities.
$string['quizaccess/edproctoring:viewreport']       = 'View proctoring reports';
$string['quizaccess/edproctoring:manage']           = 'Configure proctoring on a quiz';
$string['quizaccess/edproctoring:viewallreports']   = 'View proctoring reports across all courses';
$string['quizaccess/edproctoring:deleteimages']     = 'Delete captured proctoring images';
$string['quizaccess/edproctoring:uploadbaseimage']  = 'Upload base images for other users';

// Global plugin enable/disable (admin settings page).
$string['plugin_enabled']      = 'Enable Ed Proctoring plugin';
$string['plugin_enabled_desc'] = 'Master switch. When disabled, proctoring will not activate for any quiz on this site regardless of per-quiz settings.';

// Live monitor (Option A snapshot wall).
$string['livemonitor']            = 'Live monitor';
$string['livemonitor_for']        = 'Live monitor: {$a}';
$string['livemonitor_all']        = 'Live proctoring monitor';
$string['livemonitor_active']     = 'active session(s)';
$string['livemonitor_connecting'] = 'Connecting…';
$string['livemonitor_none']       = 'No active proctored attempts right now.';
$string['livemonitor_nojs']       = 'The live monitor needs JavaScript enabled in your browser.';
$string['livemonitor_refresh']    = 'Refresh now';
$string['livemonitor_lost']       = 'Connection lost';
$string['livemonitor_noimage']    = 'No image yet';
$string['livemonitor_updated']    = 'Updated';
$string['livemonitor_error']      = 'Update failed — retrying';
$string['openlivemonitor']        = 'Open live monitor';
$string['liverefresh']            = 'Live monitor refresh interval';
$string['liverefresh_desc']       = 'How often (in seconds) the live proctoring wall reloads active sessions. Minimum 3. Default 9.';
