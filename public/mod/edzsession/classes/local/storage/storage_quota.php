<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local\storage;

/**
 * Provider quota snapshot. Returned by get_quota(); null when the provider
 * has no quota concept (e.g. S3). The pipeline's quota_checked step uses this.
 *
 * @package mod_edzsession
 * @copyright 2026 EDZLMS
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class storage_quota {
    /** @var int|null Bytes free this period, null if not exposed. */
    public ?int $freebytes;
    /** @var int|null Total bytes for the period. */
    public ?int $totalbytes;
    /** @var string|null Period label, e.g. 'week' | 'total'. */
    public ?string $period;

    public function __construct(?int $freebytes, ?int $totalbytes = null, ?string $period = null) {
        $this->freebytes = $freebytes;
        $this->totalbytes = $totalbytes;
        $this->period = $period;
    }

    /** Can we fit an upload of $sizebytes? Unknown size or unknown free => true. */
    public function can_fit(?int $sizebytes): bool {
        if ($this->freebytes === null || $sizebytes === null) {
            return true;
        }
        return $sizebytes <= $this->freebytes;
    }
}
