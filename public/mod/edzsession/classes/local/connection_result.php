<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify it under
// the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version. See <http://www.gnu.org/licenses/>.

namespace mod_edzsession\local;

/**
 * Result of a provider connection test. Provider-neutral so the admin
 * test-connection UI can render any meeting or storage provider uniformly.
 *
 * @package    mod_edzsession
 * @copyright  2026 EDZLMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connection_result {

    /** @var bool Did the connection succeed? */
    public bool $ok;
    /** @var string Short human-readable outcome. */
    public string $message;
    /** @var string|null Extra detail (account name, quota, error snippet). */
    public ?string $detail;
    /** @var bool True when there is nothing meaningful to test (e.g. 'none'). */
    public bool $notapplicable;

    private function __construct(bool $ok, string $message, ?string $detail, bool $notapplicable) {
        $this->ok = $ok;
        $this->message = $message;
        $this->detail = $detail;
        $this->notapplicable = $notapplicable;
    }

    public static function ok(string $message, ?string $detail = null): connection_result {
        return new self(true, $message, $detail, false);
    }

    public static function fail(string $message, ?string $detail = null): connection_result {
        return new self(false, $message, $detail, false);
    }

    /** Nothing to test (e.g. storage provider 'none', or credentials absent). */
    public static function na(string $message, ?string $detail = null): connection_result {
        return new self(false, $message, $detail, true);
    }
}
