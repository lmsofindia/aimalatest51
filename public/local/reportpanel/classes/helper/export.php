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

namespace local_reportpanel\helper;

/**
 * PDF (TCPDF) and CSV exports of the consolidated user report. Reuses the proven
 * branded-header/footer TCPDF pattern from local_edzteams.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export {

    /**
     * Column headers (in order) shared by both exports.
     *
     * @return array
     */
    protected static function columns(): array {
        return [
            get_string('course', 'local_reportpanel'),
            get_string('cons_completionpct', 'local_reportpanel'),
            get_string('cons_completiondate', 'local_reportpanel'),
            get_string('cons_grade', 'local_reportpanel'),
            get_string('cons_certdate', 'local_reportpanel'),
            get_string('cons_badges', 'local_reportpanel'),
        ];
    }

    /**
     * Stream the consolidated report as a branded PDF, then exit.
     *
     * @param \stdClass $user subject user
     * @param array $rows from consolidated::build()
     * @return void
     */
    public static function pdf(\stdClass $user, array $rows): void {
        global $CFG, $USER, $SITE;
        require_once($CFG->libdir . '/pdflib.php');

        $title = get_string('pdf_title', 'local_reportpanel');

        $doc = new class('L', 'mm', 'A4') extends \pdf {
            /** @var string */
            public $reporttitle = '';
            /** @var string */
            public $brandname = '';

            /**
             * Branded header band repeated on every page.
             *
             * @return void
             */
            public function Header() { // phpcs:ignore moodle.NamingConventions.ValidFunctionName.LowercaseMethod
                $w = $this->getPageWidth();
                $this->SetFillColor(15, 108, 191);
                $this->Rect(0, 0, $w, 15, 'F');
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('helvetica', 'B', 13);
                $this->SetXY(10, 3.5);
                $this->Cell(($w - 20) / 2, 8, $this->brandname, 0, 0, 'L');
                $this->SetFont('helvetica', '', 11);
                $this->Cell(($w - 20) / 2, 8, $this->reporttitle, 0, 0, 'R');
                $this->SetDrawColor(99, 102, 241);
                $this->SetLineWidth(0.8);
                $this->Line(0, 15.8, $w, 15.8);
                $this->SetTextColor(29, 33, 37);
                $this->SetDrawColor(0, 0, 0);
                $this->SetLineWidth(0.2);
            }

            /**
             * Footer rule + brand + page x/y.
             *
             * @return void
             */
            public function Footer() { // phpcs:ignore moodle.NamingConventions.ValidFunctionName.LowercaseMethod
                $w = $this->getPageWidth();
                $this->SetY(-13);
                $this->SetDrawColor(200, 204, 208);
                $this->SetLineWidth(0.3);
                $this->Line(10, $this->GetY(), $w - 10, $this->GetY());
                $this->SetY(-11);
                $this->SetX(10);
                $this->SetFont('helvetica', '', 8);
                $this->SetTextColor(106, 115, 123);
                $this->Cell(($w - 20) / 2, 6, $this->brandname, 0, 0, 'L');
                $this->Cell(($w - 20) / 2, 6,
                    $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $doc->brandname = format_string($SITE->fullname) . ' · ' .
            get_string('pluginname', 'local_reportpanel');
        $doc->reporttitle = $title;
        $doc->SetCreator(get_string('pluginname', 'local_reportpanel'));
        $doc->SetAuthor(fullname($USER));
        $doc->SetTitle($title);
        $doc->setPrintHeader(true);
        $doc->setPrintFooter(true);
        $doc->SetMargins(10, 22, 10);
        $doc->SetAutoPageBreak(true, 17);
        $doc->SetFont('helvetica', '', 9);
        $doc->AddPage();

        // Meta block.
        $generated = userdate(time(), get_string('strftimedatetimeshort', 'langconfig'));
        $html = '<h3 style="color:#1d2125;">' . s($title) . '</h3>';
        $html .= '<table cellpadding="2">';
        $html .= '<tr><td><b>' . s(get_string('name', 'local_reportpanel')) . ':</b> ' .
            s(fullname($user)) . '</td>';
        $html .= '<td><b>' . s(get_string('email', 'local_reportpanel')) . ':</b> ' .
            s($user->email) . '</td></tr>';
        $html .= '<tr><td><b>' . s(get_string('generatedon', 'local_reportpanel')) . ':</b> ' .
            s($generated) . '</td>';
        $html .= '<td><b>' . s(get_string('printedby', 'local_reportpanel')) . ':</b> ' .
            s(fullname($USER)) . '</td></tr>';
        $html .= '</table><hr style="color:#c8ccd0;"><br>';

        // Table.
        $html .= '<table border="0.3" cellpadding="4"><thead><tr style="background-color:#eef1f4;">';
        foreach (self::columns() as $h) {
            $html .= '<th><b>' . s($h) . '</b></th>';
        }
        $html .= '</tr></thead><tbody>';
        if (empty($rows)) {
            $html .= '<tr><td colspan="6">' . s(get_string('cons_nocourses', 'local_reportpanel')) .
                '</td></tr>';
        } else {
            $i = 0;
            foreach ($rows as $r) {
                $bg = (++$i % 2 === 0) ? ' style="background-color:#f7f8f9;"' : '';
                $html .= '<tr' . $bg . '>';
                $html .= '<td>' . s($r['coursename']) . '</td>';
                $html .= '<td>' . s($r['completionpct']) . '</td>';
                $html .= '<td>' . s($r['completiondate']) . '</td>';
                $html .= '<td>' . s($r['grade']) . '</td>';
                $html .= '<td>' . s($r['certdate']) . '</td>';
                $html .= '<td>' . s($r['badges']) . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $doc->writeHTML($html, true, false, true, false, '');
        $doc->Output(clean_filename('consolidated_' . $user->id . '_' . date('Ymd_Hi') . '.pdf'), 'D');
        exit;
    }

    /**
     * Stream the consolidated report as CSV, then exit.
     *
     * @param \stdClass $user subject user
     * @param array $rows from consolidated::build()
     * @return void
     */
    public static function csv(\stdClass $user, array $rows): void {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $csv = new \csv_export_writer();
        $csv->set_filename('consolidated_' . $user->id . '_' . date('Ymd_Hi'));

        // Header: Name + Email then the shared columns.
        $header = array_merge([
            get_string('name', 'local_reportpanel'),
            get_string('email', 'local_reportpanel'),
        ], self::columns());
        $csv->add_data($header);

        $name = fullname($user);
        foreach ($rows as $r) {
            $csv->add_data([
                $name,
                $user->email,
                $r['coursename'],
                $r['completionpct'],
                $r['completiondate'],
                $r['grade'],
                $r['certdate'],
                $r['badges'],
            ]);
        }
        $csv->download_file();
        exit;
    }

    /**
     * Column headers (in order) shared by both course-report exports.
     *
     * @return array
     */
    protected static function course_columns(): array {
        return [
            get_string('name', 'local_reportpanel'),
            get_string('email', 'local_reportpanel'),
            get_string('cons_completionpct', 'local_reportpanel'),
            get_string('coursecons_activities', 'local_reportpanel'),
            get_string('cons_grade', 'local_reportpanel'),
            get_string('cons_completiondate', 'local_reportpanel'),
            get_string('coursecons_status', 'local_reportpanel'),
        ];
    }

    /**
     * Stream the consolidated COURSE report as a branded PDF, then exit.
     *
     * @param \stdClass $course subject course
     * @param array $rows from courseconsolidated::build()
     * @param array $summary from courseconsolidated::build()
     * @return void
     */
    public static function course_pdf(\stdClass $course, array $rows, array $summary): void {
        global $CFG, $USER, $SITE;
        require_once($CFG->libdir . '/pdflib.php');

        $title = get_string('coursecons_title', 'local_reportpanel');

        $doc = new class('L', 'mm', 'A4') extends \pdf {
            /** @var string */
            public $reporttitle = '';
            /** @var string */
            public $brandname = '';

            /**
             * Branded header band repeated on every page.
             *
             * @return void
             */
            public function Header() { // phpcs:ignore moodle.NamingConventions.ValidFunctionName.LowercaseMethod
                $w = $this->getPageWidth();
                $this->SetFillColor(15, 108, 191);
                $this->Rect(0, 0, $w, 15, 'F');
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('helvetica', 'B', 13);
                $this->SetXY(10, 3.5);
                $this->Cell(($w - 20) / 2, 8, $this->brandname, 0, 0, 'L');
                $this->SetFont('helvetica', '', 11);
                $this->Cell(($w - 20) / 2, 8, $this->reporttitle, 0, 0, 'R');
                $this->SetDrawColor(99, 102, 241);
                $this->SetLineWidth(0.8);
                $this->Line(0, 15.8, $w, 15.8);
                $this->SetTextColor(29, 33, 37);
                $this->SetDrawColor(0, 0, 0);
                $this->SetLineWidth(0.2);
            }

            /**
             * Footer rule + brand + page x/y.
             *
             * @return void
             */
            public function Footer() { // phpcs:ignore moodle.NamingConventions.ValidFunctionName.LowercaseMethod
                $w = $this->getPageWidth();
                $this->SetY(-13);
                $this->SetDrawColor(200, 204, 208);
                $this->SetLineWidth(0.3);
                $this->Line(10, $this->GetY(), $w - 10, $this->GetY());
                $this->SetY(-11);
                $this->SetX(10);
                $this->SetFont('helvetica', '', 8);
                $this->SetTextColor(106, 115, 123);
                $this->Cell(($w - 20) / 2, 6, $this->brandname, 0, 0, 'L');
                $this->Cell(($w - 20) / 2, 6,
                    $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $doc->brandname = format_string($SITE->fullname) . ' · ' .
            get_string('pluginname', 'local_reportpanel');
        $doc->reporttitle = $title;
        $doc->SetCreator(get_string('pluginname', 'local_reportpanel'));
        $doc->SetAuthor(fullname($USER));
        $doc->SetTitle($title);
        $doc->setPrintHeader(true);
        $doc->setPrintFooter(true);
        $doc->SetMargins(10, 22, 10);
        $doc->SetAutoPageBreak(true, 17);
        $doc->SetFont('helvetica', '', 9);
        $doc->AddPage();

        // Meta block.
        $generated = userdate(time(), get_string('strftimedatetimeshort', 'langconfig'));
        $html = '<h3 style="color:#1d2125;">' . s(format_string($course->fullname)) . '</h3>';
        $html .= '<table cellpadding="2">';
        $html .= '<tr><td><b>' . s(get_string('generatedon', 'local_reportpanel')) . ':</b> ' .
            s($generated) . '</td>';
        $html .= '<td><b>' . s(get_string('printedby', 'local_reportpanel')) . ':</b> ' .
            s(fullname($USER)) . '</td></tr>';
        $html .= '<tr><td><b>' . s(get_string('stat_enrolled', 'local_reportpanel')) . ':</b> ' .
            (int)$summary['totalusers'] . ' &nbsp; ' .
            '<b>' . s(get_string('status_completed', 'local_reportpanel')) . ':</b> ' .
            (int)$summary['completed'] . ' &nbsp; ' .
            '<b>' . s(get_string('status_inprogress', 'local_reportpanel')) . ':</b> ' .
            (int)$summary['inprogress'] . ' &nbsp; ' .
            '<b>' . s(get_string('status_notstarted', 'local_reportpanel')) . ':</b> ' .
            (int)$summary['notstarted'] . '</td>';
        $html .= '<td><b>' . s(get_string('stat_completionrate', 'local_reportpanel')) . ':</b> ' .
            s($summary['completionrate'] . '%') . ' &nbsp; ' .
            '<b>' . s(get_string('stat_avggrade', 'local_reportpanel')) . ':</b> ' .
            s($summary['avggradelabel']) . '</td></tr>';
        $html .= '</table><hr style="color:#c8ccd0;"><br>';

        // Table.
        $html .= '<table border="0.3" cellpadding="4"><thead><tr style="background-color:#eef1f4;">';
        foreach (self::course_columns() as $h) {
            $html .= '<th><b>' . s($h) . '</b></th>';
        }
        $html .= '</tr></thead><tbody>';
        if (empty($rows)) {
            $html .= '<tr><td colspan="7">' .
                s(get_string('coursecons_noenrol', 'local_reportpanel')) . '</td></tr>';
        } else {
            $i = 0;
            foreach ($rows as $r) {
                $bg = (++$i % 2 === 0) ? ' style="background-color:#f7f8f9;"' : '';
                $html .= '<tr' . $bg . '>';
                $html .= '<td>' . s($r['fullname']) . '</td>';
                $html .= '<td>' . s($r['email']) . '</td>';
                $html .= '<td>' . s($r['completionpct']) . '</td>';
                $html .= '<td>' . s($r['activities']) . '</td>';
                $html .= '<td>' . s($r['grade']) . '</td>';
                $html .= '<td>' . s($r['completiondate']) . '</td>';
                $html .= '<td>' . s($r['status']) . '</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $doc->writeHTML($html, true, false, true, false, '');
        $doc->Output(clean_filename('course_' . $course->id . '_' . date('Ymd_Hi') . '.pdf'), 'D');
        exit;
    }

    /**
     * Stream the consolidated COURSE report as CSV, then exit.
     *
     * @param \stdClass $course subject course
     * @param array $rows from courseconsolidated::build()
     * @return void
     */
    public static function course_csv(\stdClass $course, array $rows): void {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');

        $csv = new \csv_export_writer();
        $csv->set_filename('course_' . $course->id . '_' . date('Ymd_Hi'));
        $csv->add_data(self::course_columns());

        foreach ($rows as $r) {
            $csv->add_data([
                $r['fullname'],
                $r['email'],
                $r['completionpct'],
                $r['activities'],
                $r['grade'],
                $r['completiondate'],
                $r['status'],
            ]);
        }
        $csv->download_file();
        exit;
    }
}
