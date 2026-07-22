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
 * Chart builders for the report pages. Uses Moodle's native chart API
 * (\core\chart_pie / \core\chart_bar) which renders through core/chartjs — we never
 * bundle our own Chart.js. Each method returns a renderable chart object; the caller
 * turns it into HTML with $OUTPUT->render_chart($chart, false).
 *
 * @package   local_reportpanel
 * @copyright 2026 EDZLMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class charts {

    /** @var string Green — completed. */
    const COLOR_COMPLETED = '#2f9e44';
    /** @var string Amber — in progress. */
    const COLOR_INPROGRESS = '#f59f00';
    /** @var string Grey — not started. */
    const COLOR_NOTSTARTED = '#adb5bd';
    /** @var string Brand blue — bars. */
    const COLOR_BAR = '#0f6cbf';

    /**
     * Doughnut of completion status counts (completed / in progress / not started).
     * Returns null when there is nothing to plot.
     *
     * @param int $completed
     * @param int $inprogress
     * @param int $notstarted
     * @return \core\chart_pie|null
     */
    public static function status_doughnut(int $completed, int $inprogress, int $notstarted): ?\core\chart_pie {
        if (($completed + $inprogress + $notstarted) <= 0) {
            return null;
        }
        $chart = new \core\chart_pie();
        $chart->set_doughnut(true);
        $chart->set_labels([
            get_string('status_completed', 'local_reportpanel'),
            get_string('status_inprogress', 'local_reportpanel'),
            get_string('status_notstarted', 'local_reportpanel'),
        ]);
        $series = new \core\chart_series(
            get_string('chart_users', 'local_reportpanel'),
            [$completed, $inprogress, $notstarted]
        );
        $series->set_colors([
            self::COLOR_COMPLETED,
            self::COLOR_INPROGRESS,
            self::COLOR_NOTSTARTED,
        ]);
        $chart->add_series($series);
        return $chart;
    }

    /**
     * Bar chart of grade distribution buckets (label => count).
     * Returns null when every bucket is empty.
     *
     * @param array $buckets label => count
     * @return \core\chart_bar|null
     */
    public static function grade_bar(array $buckets): ?\core\chart_bar {
        if (array_sum($buckets) <= 0) {
            return null;
        }
        $chart = new \core\chart_bar();
        $chart->set_labels(array_keys($buckets));
        $series = new \core\chart_series(
            get_string('chart_learners', 'local_reportpanel'),
            array_values($buckets)
        );
        // One colour per bar: Chart.js maps the colours array per data point, so a
        // single-element array would colour only the first bar.
        $series->set_colors(array_fill(0, count($buckets), self::COLOR_BAR));
        $chart->add_series($series);
        return $chart;
    }

    /**
     * Horizontal bar of a value per label (e.g. grade % per course). Values are
     * numeric; null entries are plotted as 0. Returns null when there is no data.
     *
     * @param array $labels
     * @param array $values numeric (0–100)
     * @param string $serieslabel
     * @return \core\chart_bar|null
     */
    public static function value_bar(array $labels, array $values, string $serieslabel): ?\core\chart_bar {
        if (empty($labels)) {
            return null;
        }
        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $chart->set_labels($labels);
        $series = new \core\chart_series($serieslabel, array_values($values));
        $series->set_colors(array_fill(0, count($values), self::COLOR_BAR));
        $chart->add_series($series);
        return $chart;
    }

    /** @var string Line/area — time-on-task trend (blue). */
    const COLOR_LINE = '#0f6cbf';
    /** @var string Palette accents. */
    const COLOR_BLUE = '#0f6cbf';
    const COLOR_PURPLE = '#6741d9';
    const COLOR_GREEN = '#2f9e44';
    const COLOR_TEAL = '#0c8599';
    const COLOR_AMBER = '#e8590c';

    /**
     * Line chart of a time-on-task (or any numeric) trend over ordered buckets.
     * Values are typically minutes; the caller formats axis meaning in the label.
     * Returns null when there is no non-zero data point.
     *
     * @param string[] $labels ordered x-axis labels (e.g. "05 Jul")
     * @param array $values numeric y-values aligned to $labels
     * @param string $serieslabel legend/tooltip label
     * @return \core\chart_line|null
     */
    public static function time_line(array $labels, array $values, string $serieslabel,
            string $color = self::COLOR_LINE): ?\core\chart_line {
        if (empty($labels) || array_sum($values) <= 0) {
            return null;
        }
        $chart = new \core\chart_line();
        $chart->set_smooth(true);
        $chart->set_labels($labels);
        $series = new \core\chart_series($serieslabel, array_values($values));
        $series->set_colors([$color]);
        $chart->add_series($series);
        return $chart;
    }
}
