<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\meeting;

/**
 * Provider-neutral description of a meeting to create/update.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting_spec {
    /** @var string Topic/title. */
    public string $topic;
    /** @var int Unix start time. */
    public int $starttime;
    /** @var int Duration minutes. */
    public int $duration;
    /** @var string|null IANA timezone. */
    public ?string $timezone;
    /** @var bool Cloud recording auto-start. */
    public bool $autorecord;
    /** @var array Recurrence spec (freq, interval, count/until, weekdays). */
    public array $recurrence;

    public function __construct(
        string $topic,
        int $starttime,
        int $duration,
        ?string $timezone = null,
        bool $autorecord = true,
        array $recurrence = []
    ) {
        $this->topic = $topic;
        $this->starttime = $starttime;
        $this->duration = $duration;
        $this->timezone = $timezone;
        $this->autorecord = $autorecord;
        $this->recurrence = $recurrence;
    }
}
