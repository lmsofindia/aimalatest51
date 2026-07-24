<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

/**
 * English strings for mod_edzsession.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'EDZ Session';
$string['modulename'] = 'EDZ Session';
$string['modulenameplural'] = 'EDZ Sessions';
$string['pluginadministration'] = 'EDZ Session administration';
$string['noinstances'] = 'There are no EDZ Sessions in this course.';

// Capabilities.
$string['edzsession:addinstance'] = 'Add a new EDZ Session activity';
$string['edzsession:view'] = 'View an EDZ Session';
$string['edzsession:reconcile'] = 'Reconcile attendance to users';
$string['edzsession:viewall'] = 'View all participants\' attendance and recordings';
$string['edzsession:host'] = 'Start the meeting as host';
$string['edzsession:manageaccounts'] = 'Manage meeting credential accounts';

// Provider display names.
$string['provider_none'] = 'None (do not offload recordings)';
$string['provider_vimeo'] = 'Vimeo';
$string['provider_vimeo_desc'] = 'Offload recordings to Vimeo. Prefers direct pull upload (zero infrastructure) and locks videos to domain-whitelisted embeds.';
$string['provider_s3'] = 'Amazon S3 (skeleton)';
$string['provider_s3_desc'] = 'S3-compatible object storage. Skeleton provider: configuration and embed shape are live; multipart upload is not yet wired.';
$string['provider_zoom'] = 'Zoom';

// Global provider settings.
$string['settings_providers'] = 'Providers';
$string['settings_providers_desc'] = 'Choose the default meeting platform and recording storage backend. Individual activities can override the storage provider.';
$string['setting_defaultmeeting'] = 'Default meeting provider';
$string['setting_defaultmeeting_desc'] = 'The meeting platform new sessions use by default.';
$string['setting_defaultstorage'] = 'Default storage provider';
$string['setting_defaultstorage_desc'] = 'Where session recordings are stored by default. Choose "None" to keep recordings only on the meeting provider.';

// Attendance settings.
$string['settings_attendance'] = 'Attendance';
$string['setting_attendancebasis'] = 'Attendance basis';
$string['setting_attendancebasis_desc'] = 'Whether attended percentage is measured against the actual meeting duration or the scheduled duration.';
$string['basis_meeting'] = 'Actual meeting duration';
$string['basis_scheduled'] = 'Scheduled duration';
$string['setting_matchstrategy'] = 'Participant matching';
$string['setting_matchstrategy_desc'] = 'How meeting participants are matched to Moodle users.';
$string['match_email'] = 'By email';
$string['match_registrantid'] = 'By registrant id';
$string['match_name'] = 'By name (least reliable)';

// Recording settings.
$string['settings_recording'] = 'Recordings';
$string['setting_deletionpolicy'] = 'Delete source recording';
$string['setting_deletionpolicy_desc'] = 'When to delete the original recording from the meeting provider after offload. Deletion only ever happens after the copy is verified.';
$string['deletion_never'] = 'Never delete the source';
$string['deletion_verified'] = 'Delete after the copy is verified';
$string['deletion_verified_grace'] = 'Delete after verified + grace period';
$string['setting_gracehours'] = 'Grace period (hours)';
$string['setting_gracehours_desc'] = 'Hours to wait after verification before deleting the source recording.';
$string['setting_retentiondays'] = 'Retention (days)';
$string['setting_retentiondays_desc'] = 'Delete stored recordings after this many days. 0 = keep forever.';

// Vimeo provider settings.
$string['vimeo_pat'] = 'Vimeo access token';
$string['vimeo_pat_desc'] = 'Personal access token with upload, edit, delete, video_files and private scopes. Stored encrypted.';
$string['vimeo_defaultfolder'] = 'Default Vimeo folder (project)';
$string['vimeo_defaultfolder_desc'] = 'Name of the Vimeo project new recordings go into unless an activity overrides it.';
$string['vimeo_embeddomain'] = 'Embed domain';
$string['vimeo_embeddomain_desc'] = 'The domain allowed to embed the videos (your Moodle host).';

// S3 provider settings.
$string['s3_region'] = 'Region';
$string['s3_bucket'] = 'Bucket';
$string['s3_accesskey'] = 'Access key';
$string['s3_secretkey'] = 'Secret key';
$string['s3_endpoint'] = 'Endpoint (optional)';
$string['s3_endpoint_desc'] = 'Custom endpoint for S3-compatible stores (e.g. MinIO, Wasabi). Leave blank for AWS.';
$string['s3_cdnbase'] = 'CDN base URL';
$string['s3_cdnbase_desc'] = 'Public/CDN base URL used to build playback links.';

// Account vault.
$string['manageaccounts'] = 'EDZ Session meeting accounts';
$string['account_name'] = 'Account name';
$string['account_accountid'] = 'Account ID';
$string['account_clientid'] = 'Client ID';
$string['account_clientsecret'] = 'Client secret';
$string['account_secret_keepblank'] = 'Leave the secret fields blank to keep the currently stored values.';
$string['account_verificationtoken'] = 'Webhook secret token';
$string['account_verificationtoken_help'] = 'The secret token used to verify incoming webhooks from the meeting provider. Optional until you enable webhooks.';
$string['account_enabled'] = 'Enabled';
$string['account_add'] = 'Add account';
$string['account_edit'] = 'Edit account';
$string['account_none'] = 'No meeting accounts have been added yet.';
$string['account_saved'] = 'Account saved.';
$string['account_deleted'] = 'Account deleted.';
$string['account_confirmdelete'] = 'Delete the meeting account "{$a}"? Activities using it will need a new account selected.';

// Activity form.
$string['sessionname'] = 'Session name';
$string['meetingsettings'] = 'Meeting';
$string['meetingprovider'] = 'Meeting provider';
$string['meetingaccount'] = 'Host account';
$string['noaccounts'] = 'No meeting accounts configured yet. Add one under Site administration before scheduling.';
$string['starttime'] = 'Start time';
$string['duration'] = 'Duration';
$string['autorecord'] = 'Record automatically to the cloud';
$string['recurrencetype'] = 'Session type';
$string['recur_single'] = 'Single session';
$string['recur_weekly'] = 'Weekly recurring';
$string['recur_interval'] = 'Repeat every (weeks)';
$string['recur_weekdays'] = 'On days';
$string['recur_endmode'] = 'Ends';
$string['recur_endcount'] = 'After a number of sessions';
$string['recur_enduntil'] = 'On a date';
$string['recur_count'] = 'Number of sessions';
$string['recur_until'] = 'End date';
$string['storagesettings'] = 'Recording storage';
$string['storageprovider'] = 'Storage provider';
$string['storageprovider_help'] = 'Where this session\'s recordings are stored. Leave as "Use site default" unless this session needs a different backend. New storage backends can be added by an administrator without changing the activity.';
$string['usesitedefault'] = 'Use site default';
$string['storagefolder'] = 'Storage folder';
$string['storagefolder_help'] = 'Optional folder/project/prefix on the storage provider to place this session\'s recordings into.';

// Completion.
$string['completionattendancepercent'] = 'Require attendance percentage';
$string['completionattendancepercentlabel'] = 'Attendance % required';
$string['completionminutes'] = 'Require minutes attended';
$string['completionminuteslabel'] = 'Minutes attended required';
$string['completionsessions'] = 'Require sessions attended';
$string['completionsessionslabel'] = 'Sessions attended required';
$string['completiondetail:percent'] = 'Attend at least {$a}% of a session';
$string['completiondetail:minutes'] = 'Attend at least {$a} minutes in total';
$string['completiondetail:sessions'] = 'Attend at least {$a} sessions';

// View.
$string['nooccurrencesyet'] = 'No sessions have been scheduled yet.';
$string['col_when'] = 'When';
$string['col_status'] = 'Status';
$string['col_join'] = 'Join';
$string['col_recording'] = 'Recording';
$string['join'] = 'Join';
$string['startashost'] = 'Start as host';
$string['host_nomeeting'] = 'No live meeting has been created for this session yet (check the host account on the activity).';
$string['host_startfailed'] = 'Could not start as host: {$a}';
$string['nohoststarturl'] = 'The meeting provider did not return a host start URL.';
$string['recording_pending'] = 'Not available yet';
$string['recording_processing'] = 'Processing…';
$string['recording_failed'] = 'Recording failed';

// Test connections.
$string['testconnections'] = 'Test connections';
$string['testconnections_desc'] = 'Check that each meeting account and storage provider can actually connect with the credentials you have entered.';
$string['test_connection'] = 'Test connection';
$string['test_runall'] = 'Test all connections';
$string['test_runhint'] = 'Click "Test all connections" to run live checks against each configured provider.';
$string['test_col_target'] = 'Target';
$string['test_col_type'] = 'Type';
$string['test_col_status'] = 'Status';
$string['test_col_detail'] = 'Detail';
$string['test_type_meeting'] = 'Meeting account ({$a})';
$string['test_type_storage'] = 'Storage provider';
$string['test_pass'] = 'Connected';
$string['test_fail'] = 'Failed';
$string['test_na'] = 'Not tested';
$string['test_default'] = 'Default';
$string['test_ok_as'] = 'Connected as {$a}';
$string['test_failed'] = 'Connection failed';
$string['test_notconfigured'] = 'Not configured';
$string['test_none'] = 'No storage provider selected — nothing to test.';
$string['test_s3_skeleton'] = 'Settings present; live test not available (S3 skeleton).';
$string['test_vimeo_quota'] = 'Free upload quota: {$a}';
$string['test_nothing'] = 'No meeting accounts or storage providers are configured yet.';
$string['backtoaccounts'] = 'Back to meeting accounts';

// Manage / reconcile.
$string['managelink'] = 'Manage attendance & recordings';
$string['manage_title'] = 'Manage: {$a}';
$string['meeting_error'] = 'The remote meeting could not be created for this activity. Check the selected account credentials, then edit and save the activity to retry.';
$string['meeting_pending'] = 'No meeting account is attached yet, so no live meeting has been created. Occurrences are listed from the schedule.';
$string['noattendanceyet'] = 'No attendance has been recorded for this session yet.';
$string['col_participant'] = 'Reported participant';
$string['col_user'] = 'Moodle user';
$string['col_minutes'] = 'Minutes';
$string['col_percent'] = 'Attended';
$string['col_match'] = 'Match';
$string['unmatched'] = 'Unmatched';
$string['reconcile_assign'] = 'Assign';
$string['reconcile_assigned'] = 'Participant assigned.';
$string['reconcile_notenrolled'] = 'That user is not enrolled in this course.';
$string['reconcile_repoll'] = 'Re-poll attendance';
$string['reconcile_repolled'] = 'Attendance re-polled.';
$string['reconcile_repollfailed'] = 'Re-poll failed: {$a}';

// Tasks.
$string['task_discover'] = 'Discover new session recordings';
$string['task_pipeline'] = 'Process recording offload pipeline';
$string['task_attendance'] = 'Poll session attendance';

// Errors.
$string['vimeoapierror'] = 'Vimeo API error';
$string['vimeouploadfailed'] = 'Vimeo upload failed';
$string['zoomauthfailed'] = 'Could not authenticate with Zoom for this account';
$string['zoomapierror'] = 'Zoom API error';
$string['s3notimplemented'] = 'The S3 upload path is not implemented yet (skeleton provider).';
$string['quotaexceeded'] = 'The storage provider does not have enough free quota for this recording.';
$string['providerprocessingerror'] = 'The storage provider reported an error while processing the recording.';
$string['noviableuploadmethod'] = 'No usable upload method for the selected storage provider and source.';
$string['sourcedownloadfailed'] = 'Could not download the source recording from the meeting provider.';
$string['notimplementedyet'] = 'Not implemented yet';
$string['unknownmeetingprovider'] = 'Unknown meeting provider: {$a}';

// Privacy.
$string['privacy:metadata:attendance'] = 'Attendance recorded for each participant in a session.';
$string['privacy:metadata:attendance:userid'] = 'The user whose attendance is recorded.';
$string['privacy:metadata:attendance:matchedname'] = 'The name reported by the meeting provider.';
$string['privacy:metadata:attendance:matchedemail'] = 'The email reported by the meeting provider.';
$string['privacy:metadata:attendance:joinseconds'] = 'Total seconds the participant was present.';
$string['privacy:metadata:attendance:attendedpercent'] = 'Percentage of the session attended.';
$string['privacy:metadata:meetingprovider'] = 'To read attendance, identifying data is sent to and read from the external meeting provider (e.g. Zoom).';
$string['privacy:metadata:meetingprovider:email'] = 'The user email, used to match attendance records.';
$string['privacy:metadata:meetingprovider:name'] = 'The user display name, used to match attendance records.';
