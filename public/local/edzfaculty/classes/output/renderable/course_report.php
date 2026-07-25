<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\output\renderable;

defined('MOODLE_INTERNAL') || die();

/**
 * Templatable course-report model.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_report implements \renderable, \templatable {

    /** @var array */
    protected $model;

    /**
     * @param array $model from course_report::get_report()
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
        $m['hasassessments'] = !empty($m['assessments']);
        $m['hasatrisk']      = !empty($m['atriskrows']);
        $m['hasroster']      = !empty($m['roster']);
        $m['haslive']        = !empty($m['liveclasses']);
        $m['datajson']       = json_encode(['strings' => []]);
        return $m;
    }
}
