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
 * View builder for local_edzallcourse.
 *
 * Produces the drilldown / grid / pager template contexts for a selected
 * category. Shared by the server-side renderable (first paint) and the
 * get_view external endpoint (in-page updates) so both stay identical.
 *
 * @package    local_edzallcourse
 * @copyright  2025 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edzallcourse\helper;

/**
 * Assemble the interactive-region contexts for a category view.
 */
class view {

    /**
     * Build drilldown + grid + pager contexts for a category selection.
     *
     * @param int $categoryid requested category (0 = default to first root)
     * @param string $q search text
     * @param string $sort sort key
     * @param int $page 1-based page
     * @return array ['categoryid','rootid','drilldown','grid','pager']
     */
    public static function build(int $categoryid, string $q, string $sort, int $page): array {
        $q     = trim($q);
        $page  = max(1, $page);
        $selid = catalogue::effective_category_id($categoryid);
        $path  = $selid ? catalogue::path($selid) : [];
        $rootid = !empty($path) ? (int)$path[0]['id'] : 0;

        // Stacked chip rows: one per ancestor level that has children.
        $rows = [];
        $depth = count($path);
        for ($d = 0; $d < $depth; $d++) {
            $parent = $path[$d];
            $children = catalogue::children((int)$parent['id']);
            if (empty($children)) {
                continue;
            }
            $selchild = $path[$d + 1]['id'] ?? null;
            foreach ($children as &$ch) {
                $ch['active'] = ($selchild !== null && (int)$ch['id'] === (int)$selchild);
            }
            unset($ch);
            $rows[] = [
                'level'     => count($rows) + 1,
                'label'     => $parent['name'],
                'parentid'  => (int)$parent['id'],
                'allcount'  => catalogue::count_for((int)$parent['id']),
                'allactive' => ($selchild === null),
                'alllabel'  => get_string('allof', 'local_edzallcourse', $parent['name']),
                'chips'     => $children,
            ];
        }
        $drilldown = ['rows' => $rows, 'hasrows' => !empty($rows)];

        // Course grid.
        $sort   = $sort !== '' ? $sort : catalogue::default_sort();
        $result = $selid
            ? catalogue::courses_page($selid, $q, $sort, $page)
            : ['records' => [], 'total' => 0, 'page' => 1, 'perpage' => catalogue::perpage(),
               'totalpages' => 0, 'from' => 0, 'to' => 0, 'path' => []];

        $opts = catalogue::card_opts();
        $courses = card::build_page($result['records'], $opts);
        $grid = ['courses' => $courses, 'hasresults' => !empty($courses)];

        // Pagination + meta.
        $pages = [];
        for ($i = 1; $i <= $result['totalpages']; $i++) {
            $pages[] = ['number' => $i, 'active' => ($i === $result['page'])];
        }
        $pathtext = implode(' / ', array_map(static function ($p) {
            return $p['name'];
        }, !empty($result['path']) ? $result['path'] : $path));

        $metatext = $result['total'] > 0
            ? get_string('showingcount', 'local_edzallcourse', (object)[
                'from'  => $result['from'],
                'to'    => $result['to'],
                'total' => $result['total'],
            ])
            : '';

        $pager = [
            'hasresults'    => !empty($courses),
            'metatext'      => $metatext,
            'total'         => $result['total'],
            'from'          => $result['from'],
            'to'            => $result['to'],
            'haspagination' => $result['totalpages'] > 1,
            'pages'         => $pages,
            'hasprev'       => $result['page'] > 1,
            'prevpage'      => max(1, $result['page'] - 1),
            'hasnext'       => $result['page'] < $result['totalpages'],
            'nextpage'      => min(max(1, $result['totalpages']), $result['page'] + 1),
            'pathtext'      => $pathtext,
            'recursive'     => catalogue::scope() === 'recursive',
        ];

        return [
            'categoryid' => $selid,
            'rootid'     => $rootid,
            'page'       => $result['page'],
            'drilldown'  => $drilldown,
            'grid'       => $grid,
            'pager'      => $pager,
        ];
    }
}
