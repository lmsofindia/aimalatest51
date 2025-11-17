<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add a link under "Site home" navigation for convenience (optional).
 */
function local_coursefilter_extend_navigation(global_navigation $nav)
{
    if (!has_capability('local/coursefilter:view', context_system::instance())) {
        return;
    }
    $node = $nav->add(
        get_string('viewpage', 'local_coursefilter'),
        new moodle_url('/local/coursefilter/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_coursefilter',
        new pix_icon('i/filter', '')
    );
    $node->showinflatnavigation = true;
}


