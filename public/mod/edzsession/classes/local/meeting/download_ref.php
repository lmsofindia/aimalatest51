<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\meeting;

/**
 * A tokenised, ready-to-fetch URL for a recording. Handed to the storage
 * provider's pull upload (as upload_request->sourceurl). Never logged.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class download_ref {
    /** @var string Fully-formed URL (token appended if the provider needs it). */
    public string $url;
    /** @var int|null Unix time the token expires (for pull timing risk). */
    public ?int $expiresat;

    public function __construct(string $url, ?int $expiresat = null) {
        $this->url = $url;
        $this->expiresat = $expiresat;
    }
}
