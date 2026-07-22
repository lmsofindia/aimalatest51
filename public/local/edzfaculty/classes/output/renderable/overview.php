<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\output\renderable;

defined('MOODLE_INTERNAL') || die();

/**
 * Templatable "All Faculty" overview model.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview implements \renderable, \templatable {

    /** @var array */
    protected $model;

    /**
     * @param array $model from overview::get_overview()
     */
    public function __construct(array $model) {
        $this->model = $model;
    }

    /**
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        return $this->model;
    }
}
