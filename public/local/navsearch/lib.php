<?php


/**
 * lib information for local_navsearch
 *
 * @package    local_navsearch
 * @copyright  2025 Edz Lms <marketing@edzlms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_navsearch_extend_navigation(\navigation_node $nav) {
    // No need to extend navigation tree; we inject via navbar.
}

function local_navsearch_render_navbar_output($output) {
    global $PAGE, $OUTPUT;

    if (!get_config('local_navsearch', 'enabled')) {
        return '';
    }

    // Search icon toggle button
$searchbox = html_writer::start_div('navsearch-container collapsed');

$searchbox .= html_writer::tag('a', '<i class="fa fa-search"></i>', [
    'href' => '#',
    'class' => 'navsearch-toggle',
    'id' => 'navsearchtoggle',
    'title' => get_string('search')
]);

// Dropdown wrapper
$searchbox .= html_writer::start_div('navsearch-dropdown');

// Input wrapper
$searchbox .= html_writer::start_div('navsearch-input-wrapper');

$searchbox .= html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'navsearchbox',
    'placeholder' => get_string('searchplaceholder', 'local_navsearch'),
    'class' => 'navsearch-input'
]);



$searchbox .= html_writer::tag('span', '&times;', [
    'class' => 'navsearch-clear',
    'id' => 'navsearchclear',
    'title' => get_string('clear', 'local_navsearch')
]);

$searchbox .= html_writer::end_div(); // input wrapper

// Search results container
$searchbox .= html_writer::tag('div', '', [
    'id' => 'navsearchresults',
    'class' => 'navsearch-results'
]);

$searchbox .= html_writer::end_div(); // dropdown
$searchbox .= html_writer::end_div(); // container


    // Load JS
    $PAGE->requires->js_call_amd('local_navsearch/search', 'init');

    return $searchbox;
}
