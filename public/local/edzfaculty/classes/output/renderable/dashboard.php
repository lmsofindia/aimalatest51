<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\output\renderable;

defined('MOODLE_INTERNAL') || die();

/**
 * Templatable dashboard model.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard implements \renderable, \templatable {

    /** @var array */
    protected $model;

    /**
     * @param array $model assembled by dashboard_helper::get_dashboard()
     */
    public function __construct(array $model) {
        $this->model = $model;
    }

    /**
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        $m = $this->model;

        // Presence flags for the template.
        $m['hasgrading']     = !empty($m['grading']);
        $m['hasdiscussions'] = !empty($m['discussions']);
        $m['hasatrisk']      = !empty($m['atriskrows']);
        $m['hasoverdue']     = !empty($m['overdue']);
        $m['hastimeline']    = !empty($m['timeline']);
        $m['hascourses']     = !empty($m['courses']);
        $m['coursecount']    = count($m['courses']);
        $m['mycourseslabel'] = get_string('mycourses', 'local_edzfaculty', $m['labels']['courses']);

        // Counts for the tab pills.
        $m['count_grading']     = count($m['grading']);
        $m['count_discussions'] = count($m['discussions']);
        $m['count_atrisk']      = count($m['atriskrows']);
        $m['count_overdue']     = count($m['overdue']);

        // Initial engagement tiles (server-rendered; JS re-renders on filter change).
        $m['engagetiles'] = $this->engage_tiles($m['engageall']);

        // Everything the AMD module needs, as one JSON blob.
        $m['datajson'] = json_encode([
            'focusall'  => $m['focusall'],
            'focusmap'  => $m['focusmap'],
            'engageall' => $m['engageall'],
            'engagemap' => $m['engagemap'],
            'chart'     => $m['chart'],
            'showai'    => (bool)$m['showai'],
            'strings'   => [
                'active' => get_string('eng_active', 'local_edzfaculty'),
                'views'  => get_string('eng_views', 'local_edzfaculty'),
                'assess' => get_string('eng_assess', 'local_edzfaculty'),
                'score'  => get_string('eng_score', 'local_edzfaculty'),
                'vslastmonth' => get_string('vslastmonth', 'local_edzfaculty'),
                'flat'   => get_string('flat', 'local_edzfaculty'),
                'aiinsight' => get_string('aiinsight', 'local_edzfaculty'),
                'sectionperf' => get_string('sectionperformance', 'local_edzfaculty'),
                // Student-360 modal + nudge.
                'attendance' => get_string('attendance', 'local_edzfaculty'),
                'avgscore'   => get_string('avgscore', 'local_edzfaculty'),
                'lastlogin'  => get_string('lastlogin', 'local_edzfaculty'),
                'missed'     => get_string('misseddeadlines', 'local_edzfaculty'),
                'forumposts' => get_string('forumposts', 'local_edzfaculty'),
                'scoretrend' => get_string('scoretrend', 'local_edzfaculty'),
                'whyflagged' => get_string('whyflagged', 'local_edzfaculty'),
                'fullrecord' => get_string('fullrecord', 'local_edzfaculty'),
                'sendnudge'  => get_string('nudgesendbtn', 'local_edzfaculty'),
                'nudgeph'    => get_string('nudgeplaceholder', 'local_edzfaculty'),
                'cancel'     => get_string('cancel', 'local_edzfaculty'),
            ],
        ]);

        return $m;
    }

    /**
     * Format the four engagement tiles for initial render.
     *
     * @param array $e
     * @return array
     */
    protected function engage_tiles(array $e): array {
        $map = [
            'active' => ['label' => get_string('eng_active', 'local_edzfaculty'), 'suffix' => '%'],
            'views'  => ['label' => get_string('eng_views', 'local_edzfaculty'), 'suffix' => ''],
            'assess' => ['label' => get_string('eng_assess', 'local_edzfaculty'), 'suffix' => ''],
            'score'  => ['label' => get_string('eng_score', 'local_edzfaculty'), 'suffix' => '%'],
        ];
        $tiles = [];
        foreach ($map as $key => $meta) {
            $val   = $e[$key]['value'] ?? 0;
            $trend = $e[$key]['trend'] ?? 0;
            $tiles[] = [
                'key'        => $key,
                'label'      => $meta['label'],
                'value'      => $meta['suffix'] === '' ? number_format((float)$val) : $val . $meta['suffix'],
                'trendtext'  => $this->trend_text($trend),
                'trendclass' => $this->trend_class($trend),
            ];
        }
        return $tiles;
    }

    /**
     * @param float $t
     * @return string
     */
    protected function trend_text(float $t): string {
        if (abs($t) < 0.5) {
            return '— ' . get_string('flat', 'local_edzfaculty');
        }
        return ($t > 0 ? '▲ ' : '▼ ') . abs($t) . '%';
    }

    /**
     * @param float $t
     * @return string
     */
    protected function trend_class(float $t): string {
        if (abs($t) < 0.5) {
            return 'flat';
        }
        return $t > 0 ? 'up' : 'down';
    }
}
