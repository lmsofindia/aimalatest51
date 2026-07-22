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

namespace local_reportpanel\report;

/**
 * Common contract for a report "source". Every detailed report (engagement now;
 * rankings, overview and activity coverage next) implements this so the shared
 * plumbing — export, and later the quick-mailer and scheduler — can treat any report
 * uniformly: ask for its data, its charts and its column headers.
 *
 * A source is stateless w.r.t. the request: pass filters in, get structured data out.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class report_base {

    /**
     * Machine name of the report (used in URLs, export filenames).
     *
     * @return string
     */
    abstract public function get_key(): string;

    /**
     * Human title for headings and export headers.
     *
     * @return string
     */
    abstract public function get_title(): string;

    /**
     * Build the report payload for the given filters.
     *
     * Convention: the returned array carries whatever the page/template needs, but
     * SHOULD include a 'rows' key (list of associative rows keyed by the columns from
     * {@see get_columns()}) so export can serialise it generically.
     *
     * @param array $filters arbitrary filter bag (e.g. from, to, courseid)
     * @return array
     */
    abstract public function get_data(array $filters): array;

    /**
     * Ordered column map for the tabular part: column key => header label.
     * Used by CSV/PDF export to know the header order.
     *
     * @return array
     */
    abstract public function get_columns(): array;

    /**
     * Renderable charts for this report, keyed by a slot name. Values are core chart
     * objects (or null when there is nothing to plot). Default: no charts.
     *
     * @param array $data the payload from {@see get_data()}
     * @return array [slot => \core\chart_base|null]
     */
    public function get_charts(array $data): array {
        return [];
    }
}
