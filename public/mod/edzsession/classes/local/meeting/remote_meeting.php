<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\meeting;

/**
 * A meeting that exists on the provider. Maps to edzsession_occurrence rows.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class remote_meeting {
    /** @var string Provider meeting id. */
    public string $meetingid;
    /** @var string|null Occurrence UUID (per sitting). */
    public ?string $uuid;
    /** @var string Base join url. */
    public string $joinurl;
    /** @var string|null Host/start url. */
    public ?string $starturl;
    /** @var array Raw provider payload for debugging. */
    public array $raw;

    public function __construct(
        string $meetingid,
        string $joinurl,
        ?string $uuid = null,
        ?string $starturl = null,
        array $raw = []
    ) {
        $this->meetingid = $meetingid;
        $this->joinurl = $joinurl;
        $this->uuid = $uuid;
        $this->starturl = $starturl;
        $this->raw = $raw;
    }
}
