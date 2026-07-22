<?php
/**
 * Admin settings for mod_edztrackvideo.
 *
 * @package   mod_edztrackvideo
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Base directory on the server where large videos are placed (e.g. via SFTP).
    // When empty, the "Server file" source is hidden in the activity form.
    $settings->add(new admin_setting_configdirectory(
        'mod_edztrackvideo/videobasedir',
        get_string('videobasedir', 'mod_edztrackvideo'),
        get_string('videobasedir_desc', 'mod_edztrackvideo'),
        ''
    ));

    // Whether teachers may use the (less secure) Direct video URL source.
    $settings->add(new admin_setting_configcheckbox(
        'mod_edztrackvideo/enable_directurl',
        get_string('enable_directurl', 'mod_edztrackvideo'),
        get_string('enable_directurl_desc', 'mod_edztrackvideo'),
        0
    ));
}
