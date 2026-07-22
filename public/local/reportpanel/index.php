<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Report panel landing page — a grid of report cards.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_reportpanel\helper\access;

require_login(null, false);
$context = context_system::instance();
require_capability('local/reportpanel:view', $context);

$heading = trim((string)get_config('local_reportpanel', 'heading'));
if ($heading === '') {
    $heading = get_string('panelheading', 'local_reportpanel');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reportpanel/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$allcards = access::cards();
$managercards = array_values(array_filter($allcards, fn($c) => $c['group'] === 'manager'));
$selfcards = array_values(array_filter($allcards, fn($c) => $c['group'] === 'self'));

$templatecontext = [
    'hascards' => !empty($allcards),
    'managercards' => $managercards,
    'hasmanager' => !empty($managercards),
    'selfcards' => $selfcards,
    'hasself' => !empty($selfcards),
    'intro' => get_string('panelintro', 'local_reportpanel'),
    'fullmode' => access::is_full_mode(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reportpanel/panel', $templatecontext);
echo $OUTPUT->footer();
