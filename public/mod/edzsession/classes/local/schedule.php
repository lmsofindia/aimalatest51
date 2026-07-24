<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local;

/**
 * Recurrence schedule: parse form data <-> json, and expand into occurrence
 * start times. Provider-neutral; the meeting provider maps the same spec onto
 * its own recurrence API for server-side occurrences.
 *
 * Spec shape (stored in edzsession.schedulejson):
 *   {
 *     "type": "single" | "weekly",
 *     "starttime": <unix>, "duration": <minutes>,
 *     "interval": <weeks>, "weekdays": [1..7 (Sun=1)],
 *     "endmode": "count" | "until", "count": <n>, "until": <unix>
 *   }
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class schedule {

    /** Hard cap so a bad "until" date can never generate unbounded rows. */
    const MAX_OCCURRENCES = 200;

    /** @var array normalised spec. */
    private array $spec;

    public function __construct(array $spec) {
        $this->spec = $spec;
    }

    /** Build from submitted activity form data. */
    public static function from_formdata(\stdClass $data): schedule {
        $type = ($data->recurrencetype ?? 'single') === 'weekly' ? 'weekly' : 'single';
        $duration = (int) round(((int) ($data->duration ?? 3600)) / 60); // seconds -> minutes.
        $spec = [
            'type' => $type,
            'starttime' => (int) ($data->starttime ?? time()),
            'duration' => max(1, $duration),
        ];
        if ($type === 'weekly') {
            $spec['interval'] = max(1, (int) ($data->recurinterval ?? 1));
            $weekdays = [];
            foreach (($data->recurweekdays ?? []) as $day => $on) {
                if ($on) {
                    $weekdays[] = (int) $day;
                }
            }
            // Default to the start day's weekday if none ticked.
            if (empty($weekdays)) {
                $weekdays[] = (int) date('N', $spec['starttime']) % 7 + 1; // ISO Mon=1..Sun=7 -> Sun=1..Sat=7.
            }
            $spec['weekdays'] = $weekdays;
            $spec['endmode'] = ($data->recurendmode ?? 'count') === 'until' ? 'until' : 'count';
            $spec['count'] = max(1, (int) ($data->recurcount ?? 8));
            $spec['until'] = (int) ($data->recuruntil ?? ($spec['starttime'] + 12 * WEEKSECS));
        }
        return new self($spec);
    }

    public function to_json(): string {
        return json_encode($this->spec);
    }

    public function get_spec(): array {
        return $this->spec;
    }

    public static function from_json(?string $json): schedule {
        return new self($json ? (json_decode($json, true) ?: []) : []);
    }

    public function duration_minutes(): int {
        return (int) ($this->spec['duration'] ?? 60);
    }

    /**
     * Expand into a list of occurrence start times (unix), ascending, capped.
     *
     * @return int[]
     */
    public function occurrence_times(): array {
        $start = (int) ($this->spec['starttime'] ?? time());
        if (($this->spec['type'] ?? 'single') === 'single') {
            return [$start];
        }

        $start -= $start % 60; // Floor to the minute (source is minute-precision).
        $interval = max(1, (int) ($this->spec['interval'] ?? 1));
        $weekdays = $this->spec['weekdays'] ?? [(int) date('N', $start) % 7 + 1];
        sort($weekdays); // Count-mode must take the chronologically-first N.
        $endmode = $this->spec['endmode'] ?? 'count';
        $count = max(1, (int) ($this->spec['count'] ?? 8));
        $until = (int) ($this->spec['until'] ?? ($start + 12 * WEEKSECS));

        $hour = (int) date('G', $start);
        $min = (int) date('i', $start);

        // Anchor to the Sunday of the start week (our weekday numbering: Sun=1).
        $weekanchor = strtotime('last sunday', strtotime('tomorrow', $start)); // Sunday <= start.
        if (date('w', $start) == 0) {
            $weekanchor = strtotime('today', $start);
        }

        $times = [];
        $weekindex = 0;
        while (count($times) < self::MAX_OCCURRENCES) {
            $weekstart = strtotime("+" . ($weekindex * $interval) . " weeks", $weekanchor);
            foreach ($weekdays as $wd) {
                // wd: Sun=1..Sat=7  ->  day offset from the week's Sunday.
                $offset = ((int) $wd) - 1;
                $daytime = mktime($hour, $min, 0,
                    (int) date('n', $weekstart),
                    (int) date('j', $weekstart) + $offset,
                    (int) date('Y', $weekstart));
                if ($daytime < $start) {
                    continue; // Skip days before the real start.
                }
                if ($endmode === 'until' && $daytime > $until) {
                    return $this->sorted_unique($times);
                }
                $times[] = $daytime;
                if ($endmode === 'count' && count($times) >= $count) {
                    return $this->sorted_unique($times);
                }
                if (count($times) >= self::MAX_OCCURRENCES) {
                    break 2;
                }
            }
            $weekindex++;
            if ($weekindex > self::MAX_OCCURRENCES) {
                break; // Safety valve.
            }
        }
        return $this->sorted_unique($times);
    }

    /** Provider recurrence payload hint (weekly). */
    public function provider_recurrence(): array {
        if (($this->spec['type'] ?? 'single') !== 'weekly') {
            return [];
        }
        $r = [
            'type' => 2, // weekly.
            'interval' => max(1, (int) ($this->spec['interval'] ?? 1)),
            'weekdays' => implode(',', $this->spec['weekdays'] ?? []),
        ];
        if (($this->spec['endmode'] ?? 'count') === 'until') {
            $r['until'] = (int) ($this->spec['until'] ?? 0);
        } else {
            $r['count'] = (int) ($this->spec['count'] ?? 8);
        }
        return $r;
    }

    private function sorted_unique(array $times): array {
        $times = array_values(array_unique($times));
        sort($times);
        return array_slice($times, 0, self::MAX_OCCURRENCES);
    }
}
