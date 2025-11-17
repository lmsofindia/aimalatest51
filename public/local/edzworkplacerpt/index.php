<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/edzworkplacerpt/classes/form/report_filter_form.php');
require_once($CFG->dirroot . '/local/edzworkplacerpt/classes/helper.php');

use local_edzworkplacerpt\form\report_filter_form;
use local_edzworkplacerpt\helper;

$context = context_system::instance();
require_login();
//require_capability('local/edzworkplacerpt:viewreport', $context);

$PAGE->set_url(new moodle_url('/local/edzworkplacerpt/index.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('myteamreport', 'local_edzworkplacerpt'));
$PAGE->set_heading(get_string('myteamreport', 'local_edzworkplacerpt'));


$PAGE->navbar->add(get_string('myteamreport', 'local_edzworkplacerpt'),
    new moodle_url('/local/edzworkplacerpt/index.php'));

// main AMD
$PAGE->requires->js_call_amd('local_edzworkplacerpt/edzworkplacerpt', 'init');


$mform = new report_filter_form();
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
}

// read checkboxes
$use_startdate = optional_param('use_startdate', 0, PARAM_INT);
$use_enddate = optional_param('use_enddate', 0, PARAM_INT);

$filterdata = $mform->get_data();
// echo "rashid";
$filterarray = [];
if ($filterdata) {
    if (!empty($filterdata->startdate)) {
        $filterarray['startdate'] = $filterdata->startdate;
    }
    if (!empty($filterdata->enddate)) {
        $filterarray['enddate'] = $filterdata->enddate;
    }
    if (!empty($filterdata->course)) {
        $filterarray['courseid'] = $filterdata->course;
    }
    if (!empty($filterdata->category)) {
        $filterarray['categoryid'] = $filterdata->category;
    }
    if (!empty($filterdata->department)) {
        $filterarray['department'] = $filterdata->department;
    }
    if (!empty($filterdata->showonly)) {
        $filterarray['showonly'] = $filterdata->showonly;
    }
}

if (!$use_startdate && isset($filterarray['startdate'])) {
    unset($filterarray['startdate']);
}
if (!$use_enddate && isset($filterarray['enddate'])) {
    unset($filterarray['enddate']);
}

$level = $filterdata && isset($filterdata->level) ? $filterdata->level : 1;
if ($level === -1) {
    $level = 0;
}

$subordinates = helper::get_subordinates($USER->id, $level);

// pagination
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
if ($perpage <= 0) {
    $perpage = 20;
}

// charts settings
$cfg = get_config('local_edzworkplacerpt');
$chartdata = null;
if (!empty($cfg->enable_charts)) {
    $start = isset($filterarray['startdate']) ? $filterarray['startdate'] : null;
    $end = isset($filterarray['enddate']) ? $filterarray['enddate'] : null;
    $chartdata = helper::get_chart_data($subordinates, $start, $end, 'day', $use_startdate, $use_enddate);

    // CSS + charts AMD
    $PAGE->requires->css(new moodle_url('/local/edzworkplacerpt/styles/edzworkplacerpt.css'));
    $PAGE->requires->js_call_amd('local_edzworkplacerpt/edzworkplacerpt_charts', 'init');
}

$report = helper::fetch_report_rows($subordinates, $filterarray, $perpage, $page * $perpage);
$renderer = $PAGE->get_renderer('local_edzworkplacerpt');

echo $OUTPUT->header();
//Rashid:Heading add as on 22-10-25
echo html_writer::start_div('container-fluid mt-2 mb-2');
echo html_writer::tag('h2', get_string('myteamreport', 'local_edzworkplacerpt'),
    ['class' => 'fw-bold text-left mb-2']);
echo html_writer::end_div();

// ---- FILTER FORM IN A CARD ----
echo html_writer::start_div('edz-filter-card container-fluid');
// we output the form as before but inside the card; Moodle forms print many wrappers but this keeps styles scoped
$mform->display();
echo html_writer::end_div();

// Charts and rest of page follow exactly as before
if (!empty($chartdata)) {
    echo html_writer::start_div('edz-charts-container', ['id' => 'edz_charts_container']);

    // toolbar single dropdown aligned right
        echo html_writer::start_div('edz-charts-toolbar d-flex justify-content-between align-items-center mb-3 mt-3');

        // Left side: heading
        echo html_writer::tag('h4', get_string('chart_period', 'local_edzworkplacerpt'), ['class' => 'edz-toolbar-label mb-0']);

        // Right side: dropdown
        echo html_writer::select(
            [
                'week' => get_string('chart_range_week', 'local_edzworkplacerpt'),
                'month' => get_string('chart_range_month', 'local_edzworkplacerpt'),
                'year' => get_string('chart_range_year', 'local_edzworkplacerpt'),
                'usefilters' => get_string('chart_period_usefilters', 'local_edzworkplacerpt')
            ],
            'edz_chart_range',
            'month',
            null,
            ['id' => 'edz_chart_range', 'class' => 'form-select edz-period-pill', 'aria-label' => get_string('chart_period', 'local_edzworkplacerpt')]
        );

        echo html_writer::end_div();


    echo html_writer::start_div('edz-chart-row');

    // Pie
    echo html_writer::start_div('edz-chart-col edz-chart-col-pie');
    echo html_writer::tag('div', get_string('chart_levels_title', 'local_edzworkplacerpt'), ['class' => 'edz-chart-title']);
    echo '<canvas id="edz_pie_levels"></canvas>';
    echo html_writer::end_div();

    // Bar
    echo html_writer::start_div('edz-chart-col edz-chart-col-bar');
    echo html_writer::tag('div', get_string('chart_totals_title', 'local_edzworkplacerpt'), ['class' => 'edz-chart-title']);
    echo '<canvas id="edz_bar_totals"></canvas>';
    echo html_writer::end_div();

    // Line
    echo html_writer::start_div('edz-chart-col edz-chart-col-line');
    echo html_writer::tag('div', get_string('chart_timeseries_title', 'local_edzworkplacerpt'), ['class' => 'edz-chart-title']);
    echo '<canvas id="edz_line_ts"></canvas>';
    echo html_writer::end_div();

    echo html_writer::end_div(); // row
    echo html_writer::end_div(); // container

    // inject chart data
    $json = json_encode($chartdata);
    echo html_writer::script("window.edz_workplace_chart_data = $json;");
}

// report table & actions
if ($report['total'] == 0) {
    echo html_writer::tag('p', get_string('noreports', 'local_edzworkplacerpt'));
} else {
    $rendered = $renderer->render_report_table($report['rows']);
    if ($rendered instanceof \html_table) {
        $rendered = html_writer::table($rendered);
    }
    echo '<div id="edz_report_container">' . $rendered . '</div>';

    // action buttons with inline SVG icons
    $exporturl = new moodle_url('/local/edzworkplacerpt/export.php', array_filter([
        'startdate' => isset($filterarray['startdate']) ? $filterarray['startdate'] : null,
        'enddate' => isset($filterarray['enddate']) ? $filterarray['enddate'] : null,
        'level' => isset($level) ? $level : 1,
        'category' => isset($filterarray['categoryid']) ? $filterarray['categoryid'] : null,
        'course' => isset($filterarray['courseid']) ? $filterarray['courseid'] : null,
        'department' => isset($filterarray['department']) ? $filterarray['department'] : null,
        'showonly' => isset($filterarray['showonly']) ? $filterarray['showonly'] : null
    ]));

    // build buttons HTML with SVG icons
    // $csvbtn = '<a class="btn-edz btn-edz-secondary btn-csv" href="' . $exporturl . '">'
    //     . '<svg class="icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 3h18v4H3zM3 11h18v10H3z" fill="currentColor"/></svg>'
    //     . ' ' . get_string('exportcsv', 'local_edzworkplacerpt') . '</a>';


    // CSV Export
    $csvbtn = html_writer::tag(
        'a',
        '<i class="fas fa-file-csv me-1"></i>' . get_string('exportcsv', 'local_edzworkplacerpt'),
        ['href' => $exporturl, 'class' => 'btn btn-secondary btn-edz me-2']
    );

    // Download PDF
    $downloadbtn = html_writer::tag(
        'button',
        '<i class="fas fa-file-pdf me-1"></i>' . get_string('downloadpdf', 'local_edzworkplacerpt'),
        ['type' => 'button', 'id' => 'edz_download_pdf', 'class' => 'btn btn-danger btn-edz me-2']
    );

    // Output buttons
    echo html_writer::div($csvbtn . $downloadbtn, 'edz-actions');

    // paging
    $baseurl = new moodle_url('/local/edzworkplacerpt/index.php', array_filter([
        'startdate' => isset($filterarray['startdate']) ? $filterarray['startdate'] : null,
        'enddate' => isset($filterarray['enddate']) ? $filterarray['enddate'] : null,
        'level' => isset($level) ? $level : 1,
        'category' => isset($filterarray['categoryid']) ? $filterarray['categoryid'] : null,
        'course' => isset($filterarray['courseid']) ? $filterarray['courseid'] : null,
        'department' => isset($filterarray['department']) ? $filterarray['department'] : null,
        'showonly' => isset($filterarray['showonly']) ? $filterarray['showonly'] : null,
        'perpage' => $perpage
    ]));
    echo $OUTPUT->paging_bar($report['total'], $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();

// Inline JS: PDF download remains the same (collects canvas images & table HTML)
$exportpdfurl = new moodle_url('/local/edzworkplacerpt/export_pdf.php');
$script = <<<JS
require(['jquery'], function(\$) {
    \$('#edz_download_pdf').on('click', function() {
        var images = {};
        var canvasIds = ['edz_pie_levels','edz_bar_totals','edz_line_ts'];
        canvasIds.forEach(function(id) {
            var c = document.getElementById(id);
            if (c && c.toDataURL) {
                images[id] = c.toDataURL('image/png', 0.92);
            } else {
                images[id] = '';
            }
        });

        var tableHtml = document.getElementById('edz_report_container') ? document.getElementById('edz_report_container').outerHTML : '';
        var title = document.title || 'Report';
        var range = \$('#edz_chart_range').val() || 'month';
        var useStart = \$('input[name="use_startdate"]').is(':checked') ? '1' : '0';
        var useEnd = \$('input[name="use_enddate"]').is(':checked') ? '1' : '0';
        var startdate = \$('input[name="startdate"]').length ? \$('input[name="startdate"]').val() : '';
        var enddate = \$('input[name="enddate"]').length ? \$('input[name="enddate"]').val() : '';

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '{$exportpdfurl}';
        form.style.display = 'none';
        function createInput(name, value) {
            var i = document.createElement('input');
            i.type = 'hidden';
            i.name = name;
            i.value = typeof value === 'string' ? value : JSON.stringify(value);
            return i;
        }
        form.appendChild(createInput('title', title));
        form.appendChild(createInput('range', range));
        form.appendChild(createInput('use_startdate', useStart));
        form.appendChild(createInput('use_enddate', useEnd));
        form.appendChild(createInput('startdate', startdate));
        form.appendChild(createInput('enddate', enddate));
        form.appendChild(createInput('tablehtml', tableHtml));
        Object.keys(images).forEach(function(k) { form.appendChild(createInput(k, images[k])); });
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });
});
JS;
echo html_writer::script($script);
