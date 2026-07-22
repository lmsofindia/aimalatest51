<?php
/**
 * English strings for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'EDZ Track Video';
$string['modulename'] = 'EDZ Track Video';
$string['modulenameplural'] = 'EDZ Track Videos';
$string['pluginadministration'] = 'EDZ Track Video administration';
$string['edztrackvideo:addinstance'] = 'Add a new EDZ Track Video activity';
$string['edztrackvideo:view'] = 'View EDZ Track Video activity';
$string['edztrackvideo:manage'] = 'Manage EDZ Track Video activity';
$string['noinstances'] = 'There are no EDZ Track Video activities in this course.';

// Source section.
$string['sourceheader'] = 'Video source';
$string['sourcetype'] = 'Video source';
$string['sourcetype_help'] = 'Choose where the video comes from:

* **YouTube** — paste a YouTube URL.
* **Vimeo** — paste a Vimeo URL.
* **Upload a file** — upload a video file stored in Moodle.

Only one source per activity. Locally uploaded video gives the strongest forward-seek lock.';
$string['source_youtube'] = 'YouTube';
$string['source_vimeo'] = 'Vimeo';
$string['source_upload'] = 'Upload a file';
$string['source_serverfile'] = 'Server file (large videos)';
$string['source_directurl'] = 'Direct video URL';

$string['serverpath'] = 'Server video file';
$string['serverpath_help'] = 'Choose a video that an administrator has placed in the server video folder (for files too large to upload through the browser). If the list is empty, ask your administrator to add files to the configured folder.';
$string['serverpath_none'] = 'No video files found in the server folder';
$string['directurl'] = 'Direct video URL';

// Admin settings.
$string['videobasedir'] = 'Server video folder';
$string['videobasedir_desc'] = 'Absolute path to a folder on the server where large video files are stored (for example, files placed via SFTP because they are too big to upload through the browser). When set, teachers can pick from these files using the "Server file" source. Leave empty to disable that source. For security, the plugin only ever serves files inside this folder, and only to users who can view the activity. A location outside the web root is recommended.';
$string['enable_directurl'] = 'Allow Direct video URL source';
$string['enable_directurl_desc'] = 'If enabled, teachers can paste a direct URL to a video file (for example a file in a web-served folder). Note: such files are NOT access-controlled by Moodle — anyone with the link can download them. Leave off unless you understand this trade-off.';

$string['externalurl'] = 'Video URL';
$string['externalurl_help'] = 'Paste the full video URL.

YouTube: watch?v=..., youtu.be/..., embed/..., or shorts/... links are all accepted.

Vimeo: vimeo.com/123456789, vimeo.com/123456789/privatehash, or player.vimeo.com/video/123456789 links are accepted.';
$string['videofile'] = 'Video file';
$string['videofile_help'] = 'Upload one video file (mp4, webm, ogv, mov). The file is stored in Moodle and streamed through the locked player.';

// Playback section.
$string['playbackheader'] = 'Playback controls';
$string['no_forward_seek_first_view'] = 'Do not allow forward seeking in first view';
$string['no_forward_seek_first_view_help'] = 'When enabled, learners cannot skip ahead of the furthest point they have watched, until they meet the completion threshold. Rewinding is always allowed. For uploaded video this lock is enforced strictly; for YouTube and Vimeo forward jumps are detected and pulled back.';
$string['allow_speed'] = 'Allow playback speed control';
$string['allow_speed_help'] = 'When off, playback is locked to normal speed (1x). When on, learners can change speed up to the maximum you set below. Note: allowing faster speeds lets learners effectively fast-forward through content.';
$string['max_speed'] = 'Maximum playback speed';

// Completion.
$string['enablecompletionpercent'] = 'Mark complete when the learner watches at least a set percentage';
$string['completionthreshold'] = 'Required watch percentage';
$string['completionthreshold_help'] = 'Tick the box and enter a percentage (1–100). The activity is marked complete once the learner has watched at least this percentage of the video. Requires automatic completion tracking.';
$string['completion_watchpercent'] = 'Watch at least {$a}% of the video';

// Errors.
$string['error_url_required'] = 'A video URL is required for this source.';
$string['error_youtube_invalid'] = 'That does not look like a valid YouTube URL.';
$string['error_vimeo_invalid'] = 'That does not look like a valid Vimeo URL.';
$string['error_file_required'] = 'Please upload a video file for the upload source.';
$string['error_serverfile_required'] = 'Please choose a server video file.';
$string['error_serverfile_invalid'] = 'That server file could not be found in the configured folder.';
$string['error_directurl_required'] = 'A direct video URL is required.';
$string['error_directurl_disabled'] = 'The Direct video URL source is not enabled on this site.';

// Player UI.
$string['watermark'] = 'EDZ Track Video';
$string['play'] = 'Play';
$string['pause'] = 'Pause';
$string['mute'] = 'Mute';
$string['unmute'] = 'Unmute';
$string['captions'] = 'Captions';
$string['speed'] = 'Speed';
$string['fullscreen'] = 'Fullscreen';
$string['resume'] = 'Resume';
$string['resumefrom'] = 'Resume from {$a}';
$string['dismiss'] = 'Dismiss';
$string['forwardlocked'] = 'Fast-forward is disabled for this lesson.';
$string['novideoconfigured'] = 'No video configured.';
$string['videoloaderror'] = 'The video could not be loaded.';
$string['completedmsg'] = 'Video marked complete.';

// Privacy.
$string['privacy:metadata:edztrackvideo_progress'] = 'Per-user video watching progress.';
$string['privacy:metadata:edztrackvideo_progress:userid'] = 'The user this progress belongs to.';
$string['privacy:metadata:edztrackvideo_progress:edztrackvideoid'] = 'The activity this progress belongs to.';
$string['privacy:metadata:edztrackvideo_progress:maxwatched'] = 'The furthest point (seconds) the user has watched.';
$string['privacy:metadata:edztrackvideo_progress:last_position'] = 'The last playback position (seconds).';
$string['privacy:metadata:edztrackvideo_progress:duration'] = 'The known video duration (seconds).';
$string['privacy:metadata:edztrackvideo_progress:completed'] = 'Whether the user met the watch threshold.';
$string['privacy:metadata:edztrackvideo_progress:last_viewed_on'] = 'When the user last viewed the video.';
