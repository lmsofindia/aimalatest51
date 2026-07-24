<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\meeting;

/**
 * One raw join/leave segment for a participant. The attendance engine dedupes
 * multiple segments per person and matches to a Moodle user.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participant_record {
    /** @var string Display name reported by the provider. */
    public string $name;
    /** @var string|null Email reported (best matching key). */
    public ?string $email;
    /** @var string|null Registrant id if the meeting used registration. */
    public ?string $registrantid;
    /** @var int Join time (unix). */
    public int $jointime;
    /** @var int Leave time (unix). */
    public int $leavetime;

    public function __construct(
        string $name,
        ?string $email,
        int $jointime,
        int $leavetime,
        ?string $registrantid = null
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->jointime = $jointime;
        $this->leavetime = $leavetime;
        $this->registrantid = $registrantid;
    }

    public function seconds(): int {
        return max(0, $this->leavetime - $this->jointime);
    }
}
