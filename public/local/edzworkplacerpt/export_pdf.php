<?php
require_once(__DIR__ . '/../../config.php');
require_login();
//require_capability('local/edzworkplacerpt:viewreport', context_system::instance());

function dataurl_to_binary($dataurl)
{
    if (strpos($dataurl, 'base64,') === false) {
        return null;
    }
    $parts = explode('base64,', $dataurl);
    if (count($parts) !== 2) {
        return null;
    }
    return base64_decode($parts[1]);
}

$title = optional_param('title', get_string('myteamreport', 'local_edzworkplacerpt'), PARAM_TEXT);
$range = optional_param('range', 'month', PARAM_TEXT);
$use_startdate = optional_param('use_startdate', 0, PARAM_INT);
$use_enddate = optional_param('use_enddate', 0, PARAM_INT);
$startdate = optional_param('startdate', '', PARAM_TEXT);
$enddate = optional_param('enddate', '', PARAM_TEXT);
$tablehtml = optional_param('tablehtml', '', PARAM_RAW);

$pie = optional_param('edz_pie_levels', '', PARAM_RAW);
$bar = optional_param('edz_bar_totals', '', PARAM_RAW);
$line = optional_param('edz_line_ts', '', PARAM_RAW);

$imfiles = [];
foreach (['pie' => $pie, 'bar' => $bar, 'line' => $line] as $k => $durl) {
    if (!empty($durl)) {
        $bin = dataurl_to_binary($durl);
        if ($bin !== null) {
            $tmp = tempnam(sys_get_temp_dir(), 'edzimg_') . '.png';
            file_put_contents($tmp, $bin);
            $imfiles[$k] = $tmp;
        }
    }
}

$tcpdfpath = $CFG->libdir . '/tcpdf/tcpdf.php';
if (!file_exists($tcpdfpath)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "PDF library not found at expected path: {$tcpdfpath}\nPlease ensure TCPDF is available on your Moodle installation.";
    foreach ($imfiles as $f) {
        @unlink($f);
    }
    exit;
}

require_once($tcpdfpath);
$pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Moodle');
$pdf->SetAuthor($USER->firstname . ' ' . $USER->lastname);
$pdf->SetTitle($title);
$pdf->SetAutoPageBreak(true, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, strip_tags($title), 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$meta = 'Range: ' . strtoupper($range);
if (!empty($use_startdate) && !empty($startdate)) {
    $meta .= ' | Start: ' . s($startdate);
}
if (!empty($use_enddate) && !empty($enddate)) {
    $meta .= ' | End: ' . s($enddate);
}
$pdf->Cell(0, 6, $meta, 0, 1, 'C');
$pdf->Ln(4);

$w = 90;
$h = 60;
if (!empty($imfiles['pie'])) {
    $pdf->Image($imfiles['pie'], 15, $pdf->GetY(), $w, $h, 'PNG');
}
if (!empty($imfiles['bar'])) {
    $pdf->Image($imfiles['bar'], 110, $pdf->GetY(), $w, $h, 'PNG');
}
$pdf->Ln($h + 6);

if (!empty($imfiles['line'])) {
    $pdf->Image($imfiles['line'], 20, $pdf->GetY(), 170, 80, 'PNG');
    $pdf->Ln(85);
}

$pdf->Ln(2);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(6);

$pdf->SetFont('helvetica', '', 9);
if (!empty($tablehtml)) {
    $allowed = strip_tags($tablehtml, '<table><thead><tbody><tr><th><td><br><strong><b><em><u>');
    $pdf->writeHTML($allowed, true, false, true, false, '');
} else {
    $pdf->Cell(0, 6, 'No table data available', 0, 1);
}

$filename = 'edz_report_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');

foreach ($imfiles as $f) {
    @unlink($f);
}
exit;
