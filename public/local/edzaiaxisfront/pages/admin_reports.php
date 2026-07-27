<?php

/**
 * Admin token usage report.
 *
 * Shows a month-by-month, per-user table of AI token consumption.
 * Supports:
 *  - Month picker (defaults to current month)
 *  - Search by name/username/email
 *  - Sortable table with usage % bar
 *  - Click through to per-user daily breakdown
 *  - Export to CSV button
 *
 * @package    local_edzaiaxisfront
 * @copyright  2026 Edz LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_edzaiaxisfront_reports');

require_capability('local/edzaiaxisfront:viewreports', context_system::instance());

$month_year = optional_param('month', date('Y-m'), PARAM_TEXT);
$search     = optional_param('search', '', PARAM_TEXT);

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $month_year)) {
    $month_year = date('Y-m');
}

// CSV export
$export = optional_param('export', 0, PARAM_BOOL);
if ($export) {
    require_sesskey();
    handle_csv_export($month_year, $search, $DB);
    exit;
}

$PAGE->set_url(new moodle_url('/local/edzaiaxisfront/pages/admin_reports.php'));
$PAGE->set_title(get_string('reports_title', 'local_edzaiaxisfront'));
$PAGE->set_heading(get_string('reports_title', 'local_edzaiaxisfront'));

// Load AMD
$PAGE->requires->js_call_amd('local_edzaiaxisfront/admin_reports', 'init', [[
    'page_url'   => (new moodle_url('/local/edzaiaxisfront/pages/admin_reports.php'))->out(false),
    'month_year' => $month_year,
    'search'     => $search,
    'sesskey'    => sesskey(),
]]);

// Build month picker (last 12 months)
$months = [];
for ($i = 0; $i < 12; $i++) {
    $ts       = mktime(0, 0, 0, date('n') - $i, 1);
    $val      = date('Y-m', $ts);
    $months[] = [
        'value'    => $val,
        'label'    => date('F Y', $ts),
        'selected' => $val === $month_year,
    ];
}

$templatectx = [
    'months'     => $months,
    'month_year' => $month_year,
    'search'     => $search,
    'sesskey'    => sesskey(),
    'export_url' => (new moodle_url('/local/edzaiaxisfront/pages/admin_reports.php', [
        'month'  => $month_year,
        'search' => $search,
        'export' => 1,
        'sesskey' => sesskey(),
    ]))->out(false),
    'overrides_url' => (new moodle_url('/local/edzaiaxisfront/pages/user_overrides.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_edzaiaxisfront/admin_reports', $templatectx);
echo $OUTPUT->footer();

// ── CSV export ────────────────────────────────────────────────────────────────

function handle_csv_export(string $month_year, string $search, moodle_database $DB): void
{
    $sql = "SELECT u.username, u.firstname, u.lastname, u.email,
                   SUM(tu.tokens_day) AS tokens_month,
                   ul.token_monthly_limit AS override_limit
            FROM {local_edzaiaxisfront_token_usage} tu
            JOIN {user} u ON u.id = tu.userid
            LEFT JOIN {local_edzaiaxisfront_user_limits} ul ON ul.userid = tu.userid
            WHERE tu.month_year = :month_year
            GROUP BY u.id, u.username, u.firstname, u.lastname, u.email, ul.token_monthly_limit
            ORDER BY tokens_month DESC";

    $rows = $DB->get_records_sql($sql, ['month_year' => $month_year]);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="token_usage_' . $month_year . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Username', 'First Name', 'Last Name', 'Email', 'Tokens Used', 'Override Limit']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row->username,
            $row->firstname,
            $row->lastname,
            $row->email,
            $row->tokens_month,
            $row->override_limit ?? '',
        ]);
    }
    fclose($out);
}
