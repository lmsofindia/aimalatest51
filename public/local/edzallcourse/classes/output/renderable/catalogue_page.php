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
 * Catalogue page renderable for local_edzallcourse.
 *
 * Assembles the initial (server-rendered) catalogue state: rail roots,
 * stacked drilldown rows for the selected node's path, the first page of
 * course cards, and pagination — using the SAME templates the AMD layer
 * re-renders on interaction.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzallcourse\output\renderable;

use renderable;
use templatable;
use renderer_base;
use local_edzallcourse\helper\catalogue;
use local_edzallcourse\helper\view;

/**
 * Renderable catalogue page.
 */
class catalogue_page implements renderable, templatable {

    /** @var int Requested category id. */
    protected $categoryid;
    /** @var string Search text. */
    protected $q;
    /** @var string Sort key. */
    protected $sort;
    /** @var int Page. */
    protected $page;

    /**
     * Constructor.
     *
     * @param int $categoryid
     * @param string $q
     * @param string $sort
     * @param int $page
     */
    public function __construct(int $categoryid, string $q = '', string $sort = '', int $page = 1) {
        $this->categoryid = $categoryid;
        $this->q = trim($q);
        $this->sort = $sort;
        $this->page = max(1, $page);
    }

    /**
     * Export data for the catalogue template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        // Drilldown + grid + pager (shared with the AJAX endpoint).
        $view = view::build($this->categoryid, $this->q, $this->sort, $this->page);
        $rootid = $view['rootid'];

        // Left rail roots.
        $roots = catalogue::roots();
        foreach ($roots as &$r) {
            $r['active'] = ((int)$r['id'] === (int)$rootid);
        }
        unset($r);

        $drilldown = $view['drilldown'];
        $grid = $view['grid'];
        $pager = $view['pager'];

        // Hero.
        $herotitle = get_config('local_edzallcourse', 'herotitle');
        if ($herotitle === false || trim((string)$herotitle) === '') {
            $herotitle = get_string('herotitle_default', 'local_edzallcourse');
        }
        $herotext = get_config('local_edzallcourse', 'herotext');
        if ($herotext === false || trim((string)$herotext) === '') {
            $herotext = get_string('herotext_default', 'local_edzallcourse');
        }

        // Sort options.
        $cursort = $this->sort !== '' ? $this->sort : catalogue::default_sort();
        $sortoptions = [];
        foreach (['popular', 'new', 'az', 'start'] as $k) {
            $sortoptions[] = [
                'value'    => $k,
                'label'    => get_string('sort_' . $k, 'local_edzallcourse'),
                'selected' => ($k === $cursort),
            ];
        }

        return [
            'herotitle'         => format_string($herotitle),
            'herotext'          => format_text($herotext, FORMAT_PLAIN),
            'browseheading'     => get_string('browseheading', 'local_edzallcourse'),
            'searchplaceholder' => get_string('searchplaceholder_scoped', 'local_edzallcourse'),
            'q'                 => $this->q,
            'roots'             => $roots,
            'hasroots'          => !empty($roots),
            'sortoptions'       => $sortoptions,
            'drilldown'         => $drilldown,
            'grid'              => $grid,
            'pager'             => $pager,
        ];
    }
}
