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
 * Small presentation helpers shared across the report pages: thousands-separated
 * numbers and period-over-period deltas for KPI cards.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format {

    /**
     * Thousands-separated integer (locale-agnostic: comma grouping, no decimals).
     *
     * @param int $n
     * @return string
     */
    public static function number(int $n): string {
        return number_format($n);
    }

    /**
     * Build a period-over-period delta descriptor for a KPI, comparing the current
     * value to the same-length previous window.
     *
     * @param int $current
     * @param int $previous
     * @return array {hasdelta:bool, deltadir:'up'|'down'|'flat', deltatext:string}
     */
    public static function delta(int $current, int $previous): array {
        // No prior data at all → nothing meaningful to compare.
        if ($previous === 0 && $current === 0) {
            return ['hasdelta' => false, 'deltadir' => 'flat', 'deltatext' => ''];
        }
        if ($previous === 0) {
            // Grew from nothing.
            return ['hasdelta' => true, 'deltadir' => 'up',
                'deltatext' => get_string('delta_new', 'local_reportpanel')];
        }
        $pct = (int)round(($current - $previous) / $previous * 100);
        $dir = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
        return [
            'hasdelta' => true,
            'deltadir' => $dir,
            'deltatext' => ($pct > 0 ? '+' : '') . $pct . '%',
        ];
    }

    /**
     * Merge a delta descriptor into a KPI card array (keys: deltadir, deltatext, hasdelta).
     *
     * @param array $card
     * @param int $current
     * @param int $previous
     * @return array
     */
    public static function with_delta(array $card, int $current, int $previous): array {
        return array_merge($card, self::delta($current, $previous));
    }
}
