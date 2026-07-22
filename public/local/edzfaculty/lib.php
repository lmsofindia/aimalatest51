<?php
// This file is part of the local_edzfaculty plugin for Moodle.

/**
 * Plugin library — navigation hooks.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a "Faculty Dashboard" entry to the flat navigation for teachers.
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_edzfaculty_extend_navigation(global_navigation $navigation) {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return;
    }
    $context = context_system::instance();
    if (!has_capability('local/edzfaculty:view', $context)) {
        return;
    }

    $url  = new moodle_url('/local/edzfaculty/index.php');
    $node = $navigation->add(
        get_string('pluginname', 'local_edzfaculty'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'edzfacultydash',
        new pix_icon('i/dashboard', '')
    );
    $node->showinflatnavigation = true;
}
