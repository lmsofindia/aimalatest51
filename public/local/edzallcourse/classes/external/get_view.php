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
 * External API: rebuild the catalogue interactive regions for a selection.
 *
 * Returns server-rendered HTML for the drilldown, grid and pager regions,
 * using the same templates as the initial page render.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzallcourse\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_edzallcourse\helper\view;
use context_system;

/**
 * Return rendered catalogue regions for a category selection.
 */
class get_view extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Selected category id'),
            'q'          => new external_value(PARAM_TEXT, 'Search text', VALUE_DEFAULT, ''),
            'sort'       => new external_value(PARAM_ALPHA, 'Sort key: popular|new|az|start', VALUE_DEFAULT, ''),
            'page'       => new external_value(PARAM_INT, '1-based page number', VALUE_DEFAULT, 1),
        ]);
    }

    /**
     * Build and render the regions.
     *
     * @param int $categoryid
     * @param string $q
     * @param string $sort
     * @param int $page
     * @return array
     */
    public static function execute(int $categoryid, string $q = '', string $sort = '', int $page = 1): array {
        global $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), [
            'categoryid' => $categoryid, 'q' => $q, 'sort' => $sort, 'page' => $page,
        ]);

        $context = context_system::instance();
        // Public no-login WS: set the page context directly. We must NOT call
        // self::validate_context() here -- in Moodle 5.x it unconditionally calls
        // require_login(), which throws requireloginerror on the no-cookie endpoint.
        $PAGE->set_context($context);
        // Public catalogue — no capability gate; only visible courses are returned.

        if (!get_config('local_edzallcourse', 'enable')) {
            throw new \moodle_exception('disabledpage', 'local_edzallcourse');
        }

        $view = view::build($params['categoryid'], $params['q'], $params['sort'], max(1, $params['page']));

        $renderer = $PAGE->get_renderer('local_edzallcourse');

        return [
            'categoryid'    => $view['categoryid'],
            'rootid'        => $view['rootid'],
            'page'          => $view['page'],
            'drilldownhtml' => $renderer->render_from_template('local_edzallcourse/drilldown', $view['drilldown']),
            'gridhtml'      => $renderer->render_from_template('local_edzallcourse/course_grid', $view['grid']),
            'pagerhtml'     => $renderer->render_from_template('local_edzallcourse/pagination', $view['pager']),
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categoryid'    => new external_value(PARAM_INT, 'Resolved selected category id'),
            'rootid'        => new external_value(PARAM_INT, 'Root ancestor id (for rail highlight)'),
            'page'          => new external_value(PARAM_INT, 'Resolved current page'),
            'drilldownhtml' => new external_value(PARAM_RAW, 'Rendered drilldown region'),
            'gridhtml'      => new external_value(PARAM_RAW, 'Rendered course grid region'),
            'pagerhtml'     => new external_value(PARAM_RAW, 'Rendered pagination region'),
        ]);
    }
}
