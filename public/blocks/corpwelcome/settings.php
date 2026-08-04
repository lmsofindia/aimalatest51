<?php
// This file is part of Moodle - http://moodle.org/
//
// Corporate welcome block — site-level settings.

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // ── Hero background image (shared by every dashboard) ─────────────────────
    // Stored in the SYSTEM context so a single upload applies to the default
    // dashboard AND every user's own dashboard copy. Served by
    // block_corpwelcome_pluginfile() in lib.php.
    $settings->add(new admin_setting_configstoredfile(
        'block_corpwelcome/backgroundimage',
        get_string('configbackgroundimage', 'block_corpwelcome'),
        get_string('configbackgroundimage_help', 'block_corpwelcome'),
        'backgroundimage',
        0,
        ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['web_image']]
    ));

    // ── Overlay darkness (readability scrim over the image) ───────────────────
    $settings->add(new admin_setting_configselect(
        'block_corpwelcome/overlaydarkness',
        get_string('configoverlaydarkness', 'block_corpwelcome'),
        get_string('configoverlaydarkness_help', 'block_corpwelcome'),
        '40',
        [
            '0'  => get_string('overlay0',  'block_corpwelcome'),
            '20' => get_string('overlay20', 'block_corpwelcome'),
            '40' => get_string('overlay40', 'block_corpwelcome'),
            '60' => get_string('overlay60', 'block_corpwelcome'),
            '80' => get_string('overlay80', 'block_corpwelcome'),
        ]
    ));
}
