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
 * Consolidated user report — full report for any user (full mode) or own report
 * (self mode). Supports PDF and CSV export.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_reportpanel\helper\access;
use local_reportpanel\helper\charts;
use local_reportpanel\helper\consolidated;
use local_reportpanel\helper\export;

require_login(null, false);
$context = context_system::instance();
require_capability('local/reportpanel:view', $context);

$requestuserid = optional_param('userid', 0, PARAM_INT);
$exportformat = optional_param('export', '', PARAM_ALPHA);

// In self mode the only viewable user is the viewer; full mode may view anyone.
$targetuserid = access::resolve_target_user($requestuserid);
$fullmode = access::is_full_mode();

// Handle export (read-only download). Require sesskey for safety.
if ($exportformat && $targetuserid) {
    require_sesskey();
    $report = consolidated::build($targetuserid);
    if (!$report['user']) {
        throw new moodle_exception('error_nouser', 'local_reportpanel');
    }
    if ($exportformat === 'pdf') {
        export::pdf($report['user'], $report['rows']);
    } else if ($exportformat === 'csv') {
        export::csv($report['user'], $report['rows']);
    }
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/reportpanel/consolidated.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cons_title', 'local_reportpanel'));
$PAGE->set_heading(get_string('cons_title', 'local_reportpanel'));

$backurl = (new moodle_url('/local/reportpanel/index.php'))->out(false);

$templatecontext = [
    'backurl' => $backurl,
    'fullmode' => $fullmode,
    'intro' => $fullmode
        ? get_string('cons_intro_full', 'local_reportpanel')
        : get_string('cons_intro_self', 'local_reportpanel'),
    'baseurl' => (new moodle_url('/local/reportpanel/consolidated.php'))->out(false),
    'searchlabel' => get_string('cons_searchuser', 'local_reportpanel'),
    'searchplaceholder' => get_string('cons_searchplaceholder', 'local_reportpanel'),
];

if ($targetuserid) {
    $report = consolidated::build($targetuserid);
    if ($report['user']) {
        $user = $report['user'];
        $templatecontext['hasuser'] = true;
        $templatecontext['username'] = fullname($user);
        $templatecontext['useremail'] = $user->email;
        $templatecontext['reportfor'] = get_string('cons_reportfor', 'local_reportpanel',
            fullname($user));
        $templatecontext['rows'] = $report['rows'];
        $templatecontext['hasrows'] = $report['hasrows'];
        $templatecontext['exportpdfurl'] = (new moodle_url('/local/reportpanel/consolidated.php',
            ['userid' => $targetuserid, 'export' => 'pdf', 'sesskey' => sesskey()]))->out(false);
        $templatecontext['exportcsvurl'] = (new moodle_url('/local/reportpanel/consolidated.php',
            ['userid' => $targetuserid, 'export' => 'csv', 'sesskey' => sesskey()]))->out(false);

        // Summary charts (rendered server-side via core/chartjs).
        $sc = $report['summary']['statuscount'];
        $donut = charts::status_doughnut($sc['completed'], $sc['inprogress'], $sc['notstarted']);
        $gradebar = charts::value_bar(
            $report['summary']['gradelabels'],
            $report['summary']['gradevalues'],
            get_string('cons_grade', 'local_reportpanel'));
        $templatecontext['hasdonut'] = (bool)$donut;
        $templatecontext['donutchart'] = $donut ? $OUTPUT->render_chart($donut, false) : '';
        $templatecontext['hasgradebar'] = (bool)$gradebar;
        $templatecontext['gradebarchart'] = $gradebar ? $OUTPUT->render_chart($gradebar, false) : '';
        $templatecontext['haschart'] = ($donut || $gradebar);
    }
} else if (!$fullmode) {
    // Self mode should always resolve to the viewer; guard just in case.
    $templatecontext['hasuser'] = false;
} else {
    $templatecontext['hasuser'] = false;
    $templatecontext['pickuser'] = get_string('cons_pickuser', 'local_reportpanel');
}

if ($fullmode) {
    $baseurljs = json_encode((new moodle_url('/local/reportpanel/consolidated.php'))->out(false));
    $noresultsjs = json_encode(get_string('cons_noresults', 'local_reportpanel'));
    $PAGE->requires->js_amd_inline("
require(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    var input = document.getElementById('reportpanel-usersearch');
    var results = document.getElementById('reportpanel-usersearch-results');
    var spinner = document.getElementById('reportpanel-usersearch-spinner');
    var baseurl = $baseurljs;
    if (!input || !results) { return; }
    var timer = null;
    var busy = function(on) { if (spinner) { spinner.hidden = !on; } };
    var hide = function() { results.hidden = true; results.innerHTML = ''; };
    var render = function(users) {
        results.innerHTML = '';
        if (!users.length) {
            var empty = document.createElement('div');
            empty.className = 'reportpanel-usersearch-empty';
            empty.textContent = $noresultsjs;
            results.appendChild(empty);
            results.hidden = false;
            return;
        }
        users.forEach(function(u) {
            var item = document.createElement('a');
            item.href = baseurl + (baseurl.indexOf('?') === -1 ? '?' : '&') + 'userid=' + u.id;
            item.className = 'list-group-item list-group-item-action reportpanel-usersearch-item';
            var name = document.createElement('span');
            name.className = 'reportpanel-usersearch-name';
            name.textContent = u.fullname;
            var email = document.createElement('small');
            email.className = 'reportpanel-usersearch-email';
            email.textContent = u.email;
            item.appendChild(name);
            item.appendChild(email);
            results.appendChild(item);
        });
        results.hidden = false;
    };
    input.addEventListener('input', function() {
        var query = input.value.trim();
        if (timer) { window.clearTimeout(timer); }
        if (query.length < 2) { busy(false); hide(); return; }
        busy(true);
        timer = window.setTimeout(function() {
            Ajax.call([{methodname: 'local_reportpanel_search_users', args: {query: query}}])[0]
                .then(function(response) { render(response.users); busy(false); return response; })
                .catch(function(e) { busy(false); Notification.exception(e); });
        }, 250);
    });
    document.addEventListener('click', function(e) {
        if (e.target !== input && !results.contains(e.target)) { hide(); }
    });
});
");
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_reportpanel/consolidated', $templatecontext);
echo $OUTPUT->footer();
