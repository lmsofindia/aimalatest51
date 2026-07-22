<?php
// This file is part of the local_edzfaculty plugin for Moodle.

namespace local_edzfaculty\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin renderer.
 *
 * @package    local_edzfaculty
 * @copyright  2026 EDZLEARN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Render the faculty dashboard.
     *
     * @param renderable\dashboard $dashboard
     * @return string
     */
    public function render_dashboard(renderable\dashboard $dashboard): string {
        $data = $dashboard->export_for_template($this);
        $this->page->requires->js_call_amd('local_edzfaculty/dashboard', 'init');
        return $this->render_from_template('local_edzfaculty/dashboard', $data);
    }
}
