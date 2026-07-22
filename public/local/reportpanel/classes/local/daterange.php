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

namespace local_reportpanel\local;

/**
 * Date-range control shared by the time-aware reports (engagement, rankings,
 * overview). Turns a preset key (+ optional custom bounds) into a concrete
 * [from, to] window and provides the option list for a <select>.
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class daterange {

    /** @var string Default preset when none supplied. */
    const DEFAULT_KEY = 'last30';

    /**
     * Selectable presets as key => lang-string label.
     *
     * @return array
     */
    public static function options(): array {
        return [
            'last7'     => get_string('range_last7', 'local_reportpanel'),
            'last30'    => get_string('range_last30', 'local_reportpanel'),
            'last90'    => get_string('range_last90', 'local_reportpanel'),
            'thismonth' => get_string('range_thismonth', 'local_reportpanel'),
            'lastmonth' => get_string('range_lastmonth', 'local_reportpanel'),
            'custom'    => get_string('range_custom', 'local_reportpanel'),
        ];
    }

    /**
     * Resolve a preset (and optional custom bounds) to a concrete window.
     *
     * @param string $key preset key
     * @param int $customfrom unix ts (used only when $key === 'custom'), 0 = unset
     * @param int $customto unix ts (used only when $key === 'custom'), 0 = unset
     * @return \stdClass {key, label, from:int, to:int} — 'to' is exclusive (now + 1)
     */
    public static function resolve(string $key, int $customfrom = 0, int $customto = 0): \stdClass {
        $now = time();
        $options = self::options();
        if (!isset($options[$key])) {
            $key = self::DEFAULT_KEY;
        }

        switch ($key) {
            case 'last7':
                $from = $now - 7 * DAYSECS;
                $to = $now + 1;
                break;
            case 'last90':
                $from = $now - 90 * DAYSECS;
                $to = $now + 1;
                break;
            case 'thismonth':
                $from = strtotime('first day of this month 00:00', $now);
                $to = $now + 1;
                break;
            case 'lastmonth':
                $from = strtotime('first day of last month 00:00', $now);
                $to = strtotime('first day of this month 00:00', $now);
                break;
            case 'custom':
                // Fall back to last30 bounds if either side is missing/invalid.
                $from = $customfrom > 0 ? $customfrom : ($now - 30 * DAYSECS);
                $to = $customto > 0 ? ($customto + DAYSECS) : ($now + 1); // Include the end day.
                if ($to <= $from) {
                    $to = $from + DAYSECS;
                }
                break;
            case 'last30':
            default:
                $key = 'last30';
                $from = $now - 30 * DAYSECS;
                $to = $now + 1;
                break;
        }

        return (object)[
            'key'   => $key,
            'label' => $options[$key],
            'from'  => (int)$from,
            'to'    => (int)$to,
        ];
    }

    /**
     * Build the option list shaped for a Mustache {{#options}} loop, marking the
     * active one selected.
     *
     * @param string $activekey
     * @return array list of {value, label, selected}
     */
    public static function menu(string $activekey): array {
        $out = [];
        foreach (self::options() as $value => $label) {
            $out[] = [
                'value' => $value,
                'label' => $label,
                'selected' => ($value === $activekey),
            ];
        }
        return $out;
    }
}
