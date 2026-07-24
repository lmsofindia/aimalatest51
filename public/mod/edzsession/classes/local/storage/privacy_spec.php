<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * How a stored recording should be locked down. Provider-neutral; each driver
 * maps these intents onto its own privacy API.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_spec {
    /** @var bool Only embeddable, not directly viewable on the provider site. */
    public bool $embedonly;
    /** @var string[] Domains allowed to embed. */
    public array $alloweddomains;
    /** @var bool Disallow download of the source file. */
    public bool $disabledownload;

    public function __construct(bool $embedonly = true, array $alloweddomains = [], bool $disabledownload = true) {
        $this->embedonly = $embedonly;
        $this->alloweddomains = $alloweddomains;
        $this->disabledownload = $disabledownload;
    }
}
