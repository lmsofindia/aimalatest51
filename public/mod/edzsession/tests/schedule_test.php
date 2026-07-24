<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession;

use mod_edzsession\local\schedule;

/**
 * Unit tests for the recurrence schedule engine.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_edzsession\local\schedule
 */
final class schedule_test extends \advanced_testcase {

    public function test_single_session_yields_one_occurrence(): void {
        $start = make_timestamp(2026, 8, 3, 10, 0); // A Monday.
        $s = new schedule(['type' => 'single', 'starttime' => $start, 'duration' => 60]);
        $times = $s->occurrence_times();
        $this->assertCount(1, $times);
        $this->assertSame($start, $times[0]);
        $this->assertSame(60, $s->duration_minutes());
    }

    public function test_weekly_count_generates_exact_number(): void {
        $start = make_timestamp(2026, 8, 3, 18, 30); // Monday 18:30.
        $s = new schedule([
            'type' => 'weekly',
            'starttime' => $start,
            'duration' => 90,
            'interval' => 1,
            'weekdays' => [2], // Monday (Sun=1..Sat=7).
            'endmode' => 'count',
            'count' => 6,
        ]);
        $times = $s->occurrence_times();
        $this->assertCount(6, $times);
        // Every occurrence is 7 days apart and preserves the time-of-day.
        for ($i = 1; $i < count($times); $i++) {
            $this->assertSame(7 * DAYSECS, $times[$i] - $times[$i - 1]);
            $this->assertSame(date('H:i', $start), date('H:i', $times[$i]));
        }
    }

    public function test_weekly_until_respects_end_date(): void {
        $start = make_timestamp(2026, 8, 3, 9, 0);
        $until = $start + (3 * WEEKSECS) + DAYSECS; // A little past 3 weeks.
        $s = new schedule([
            'type' => 'weekly',
            'starttime' => $start,
            'duration' => 60,
            'interval' => 1,
            'weekdays' => [2],
            'endmode' => 'until',
            'until' => $until,
        ]);
        $times = $s->occurrence_times();
        $this->assertNotEmpty($times);
        foreach ($times as $t) {
            $this->assertLessThanOrEqual($until, $t);
            $this->assertGreaterThanOrEqual($start, $t);
        }
        // 4 Mondays fit in the window (weeks 0,1,2,3).
        $this->assertCount(4, $times);
    }

    public function test_multiple_weekdays_are_sorted_and_unique(): void {
        $start = make_timestamp(2026, 8, 3, 12, 0); // Monday.
        $s = new schedule([
            'type' => 'weekly',
            'starttime' => $start,
            'duration' => 45,
            'interval' => 1,
            'weekdays' => [2, 5], // Monday + Thursday.
            'endmode' => 'count',
            'count' => 4,
        ]);
        $times = $s->occurrence_times();
        $this->assertCount(4, $times);
        $sorted = $times;
        sort($sorted);
        $this->assertSame($sorted, $times);
        $this->assertSame($times, array_values(array_unique($times)));
    }

    public function test_json_roundtrip_preserves_spec(): void {
        $spec = ['type' => 'weekly', 'starttime' => 100, 'duration' => 30,
            'interval' => 2, 'weekdays' => [3], 'endmode' => 'count', 'count' => 5];
        $json = (new schedule($spec))->to_json();
        $back = schedule::from_json($json)->get_spec();
        $this->assertSame($spec, $back);
    }
}
