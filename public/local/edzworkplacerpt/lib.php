<?php
defined('MOODLE_INTERNAL') || die();

function local_edzworkplacerpt_extend_settings_navigation(settings_navigation $nav) {
    $context = context_system::instance();
    if (has_capability('local/edzworkplacerpt:viewreport', $context)) {
        $node = $nav->add(get_string('pluginname', 'local_edzworkplacerpt'));
        $node->add(get_string('myteamreport', 'local_edzworkplacerpt'), new moodle_url('/local/edzworkplacerpt/index.php'));
    }
}
